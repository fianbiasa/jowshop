<?php

namespace Tests\Feature\Funnels;

use App\Enums\AiProvider;
use App\Enums\SalespageStyle;
use App\Models\AiProviderSetting;
use App\Models\Funnel;
use App\Models\Salespage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SalespageManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_salespage_editor(): void
    {
        $funnel = Funnel::factory()->create();

        $response = $this->get(route('funnels.salespage.edit', $funnel));

        $response->assertRedirect(route('login'));
    }

    public function test_salespage_editor_is_displayed(): void
    {
        $user = User::factory()->create();
        $funnel = Funnel::factory()->for($user, 'creator')->create();

        $response = $this->actingAs($user)->get(route('funnels.salespage.edit', $funnel));

        $response->assertOk();
    }

    public function test_salespage_can_be_saved_manually(): void
    {
        $user = User::factory()->create();
        $funnel = Funnel::factory()->for($user, 'creator')->create();

        $response = $this->actingAs($user)->put(route('funnels.salespage.update', $funnel), [
            'title' => 'Kopi Terbaik',
            'content' => [
                ['type' => 'headline', 'data' => ['text' => 'Kopi Terbaik di Kota']],
                ['type' => 'cta', 'data' => ['label' => 'Beli Sekarang']],
            ],
            'style' => 'minimal',
            'is_published' => true,
        ]);

        $response->assertSessionHasNoErrors();

        $salespage = Salespage::query()->where('funnel_id', $funnel->id)->firstOrFail();
        $this->assertSame('Kopi Terbaik', $salespage->title);
        $this->assertSame(SalespageStyle::Minimal, $salespage->style);
        $this->assertFalse($salespage->generated_by_ai);
        $this->assertNotNull($salespage->published_at);
    }

    public function test_manually_saved_content_is_sanitized(): void
    {
        $user = User::factory()->create();
        $funnel = Funnel::factory()->for($user, 'creator')->create();

        $this->actingAs($user)->put(route('funnels.salespage.update', $funnel), [
            'title' => 'Kopi Terbaik',
            'content' => [
                ['type' => 'headline', 'data' => ['text' => '<script>alert(1)</script>Judul']],
            ],
            'style' => 'minimal',
        ]);

        $salespage = Salespage::query()->where('funnel_id', $funnel->id)->firstOrFail();
        $this->assertStringNotContainsString('<script>', $salespage->content[0]['data']['text']);
    }

    public function test_salespage_style_can_be_changed_without_touching_content(): void
    {
        $user = User::factory()->create();
        $funnel = Funnel::factory()->for($user, 'creator')->create();
        $salespage = Salespage::factory()->create([
            'funnel_id' => $funnel->id,
            'style' => SalespageStyle::Minimal,
        ]);

        $response = $this->actingAs($user)->put(route('funnels.salespage.update', $funnel), [
            'title' => $salespage->title,
            'content' => $salespage->content,
            'style' => 'bold',
        ]);

        $response->assertSessionHasNoErrors();

        $salespage->refresh();
        $this->assertSame(SalespageStyle::Bold, $salespage->style);
    }

    public function test_salespage_can_be_generated_by_ai(): void
    {
        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [
                    ['message' => ['content' => json_encode([
                        ['type' => 'headline', 'data' => ['text' => '<b>Kopi</b> Terbaik di Kota']],
                        ['type' => 'cta', 'data' => ['label' => 'Beli Sekarang']],
                    ])]],
                ],
                'usage' => ['prompt_tokens' => 120, 'completion_tokens' => 40],
            ], 200),
        ]);

        $user = User::factory()->create();
        $funnel = Funnel::factory()->for($user, 'creator')->create();
        $setting = AiProviderSetting::factory()->for($user, 'creator')->create();

        $response = $this->actingAs($user)->post(route('funnels.salespage.generate', $funnel), [
            'ai_provider_setting_id' => $setting->id,
            'style' => 'bold',
            'brief' => 'Target audiens pecinta kopi',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('funnels.salespage.edit', $funnel));

        $salespage = Salespage::query()->where('funnel_id', $funnel->id)->firstOrFail();
        $this->assertTrue($salespage->generated_by_ai);
        $this->assertSame(SalespageStyle::Bold, $salespage->style);
        $this->assertStringNotContainsString('<b>', $salespage->content[0]['data']['text']);

        $this->assertDatabaseHas('ai_generation_logs', [
            'ai_provider_setting_id' => $setting->id,
            'salespage_id' => $salespage->id,
            'status' => 'success',
            'tokens_input' => 120,
            'tokens_output' => 40,
        ]);
    }

    public function test_ai_generation_failure_is_logged_and_does_not_overwrite_salespage(): void
    {
        Http::fake([
            'api.openai.com/*' => Http::response(['error' => 'bad request'], 400),
        ]);

        $user = User::factory()->create();
        $funnel = Funnel::factory()->for($user, 'creator')->create();
        $setting = AiProviderSetting::factory()->for($user, 'creator')->create();

        $response = $this->actingAs($user)->post(route('funnels.salespage.generate', $funnel), [
            'ai_provider_setting_id' => $setting->id,
            'style' => 'minimal',
        ]);

        $response->assertRedirect(route('funnels.salespage.edit', $funnel));
        $this->assertDatabaseMissing('salespages', ['funnel_id' => $funnel->id]);
        $this->assertDatabaseHas('ai_generation_logs', [
            'ai_provider_setting_id' => $setting->id,
            'status' => 'failed',
        ]);
    }

    public function test_ai_generation_with_unparsable_response_is_logged_as_failed(): void
    {
        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [
                    ['message' => ['content' => 'Halo! Ini bukan JSON.']],
                ],
                'usage' => ['prompt_tokens' => 10, 'completion_tokens' => 5],
            ], 200),
        ]);

        $user = User::factory()->create();
        $funnel = Funnel::factory()->for($user, 'creator')->create();
        $setting = AiProviderSetting::factory()->for($user, 'creator')->create();

        $this->actingAs($user)->post(route('funnels.salespage.generate', $funnel), [
            'ai_provider_setting_id' => $setting->id,
            'style' => 'minimal',
        ]);

        $this->assertDatabaseMissing('salespages', ['funnel_id' => $funnel->id]);
        $this->assertDatabaseHas('ai_generation_logs', [
            'ai_provider_setting_id' => $setting->id,
            'status' => 'failed',
        ]);
    }

    public function test_failure_log_preserves_the_raw_ai_response_for_debugging(): void
    {
        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [
                    ['message' => ['content' => 'Halo! Ini bukan JSON.']],
                ],
                'usage' => ['prompt_tokens' => 10, 'completion_tokens' => 5],
            ], 200),
        ]);

        $user = User::factory()->create();
        $funnel = Funnel::factory()->for($user, 'creator')->create();
        $setting = AiProviderSetting::factory()->for($user, 'creator')->create();

        $this->actingAs($user)->post(route('funnels.salespage.generate', $funnel), [
            'ai_provider_setting_id' => $setting->id,
            'style' => 'minimal',
        ]);

        $this->assertDatabaseHas('ai_generation_logs', [
            'ai_provider_setting_id' => $setting->id,
            'status' => 'failed',
            'response_excerpt' => 'Halo! Ini bukan JSON.',
        ]);
    }

    public function test_anthropic_generation_skips_thinking_blocks_to_find_the_reply_text(): void
    {
        Http::fake([
            'api.anthropic.com/*' => Http::response([
                'content' => [
                    ['type' => 'thinking', 'thinking' => 'Menyusun salespage...'],
                    ['type' => 'text', 'text' => json_encode([
                        ['type' => 'headline', 'data' => ['text' => 'Kopi Terbaik di Kota']],
                        ['type' => 'cta', 'data' => ['label' => 'Beli Sekarang']],
                    ])],
                ],
                'usage' => ['input_tokens' => 120, 'output_tokens' => 40],
            ], 200),
        ]);

        $user = User::factory()->create();
        $funnel = Funnel::factory()->for($user, 'creator')->create();
        $setting = AiProviderSetting::factory()->for($user, 'creator')->create([
            'provider' => AiProvider::Anthropic,
            'default_model' => 'claude-sonnet-5',
        ]);

        $response = $this->actingAs($user)->post(route('funnels.salespage.generate', $funnel), [
            'ai_provider_setting_id' => $setting->id,
            'style' => 'minimal',
        ]);

        $response->assertSessionHasNoErrors();

        $salespage = Salespage::query()->where('funnel_id', $funnel->id)->firstOrFail();
        $this->assertSame('headline', $salespage->content[0]['type']);
        $this->assertSame('Kopi Terbaik di Kota', $salespage->content[0]['data']['text']);

        $this->assertDatabaseHas('ai_generation_logs', [
            'ai_provider_setting_id' => $setting->id,
            'status' => 'success',
            'tokens_input' => 120,
            'tokens_output' => 40,
        ]);
    }

    public function test_ai_generation_tolerates_prose_and_code_fence_around_the_json(): void
    {
        $rawResponse = <<<'TEXT'
            Tentu, berikut adalah salespage yang saya buat untuk produkmu:

            ```json
            [
                {"type": "headline", "data": {"text": "Kopi Terbaik di Kota"}},
                {"type": "cta", "data": {"label": "Beli Sekarang"}}
            ]
            ```

            Semoga membantu! Beri tahu saya jika ada yang perlu diubah.
            TEXT;

        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [
                    ['message' => ['content' => $rawResponse]],
                ],
                'usage' => ['prompt_tokens' => 120, 'completion_tokens' => 40],
            ], 200),
        ]);

        $user = User::factory()->create();
        $funnel = Funnel::factory()->for($user, 'creator')->create();
        $setting = AiProviderSetting::factory()->for($user, 'creator')->create();

        $response = $this->actingAs($user)->post(route('funnels.salespage.generate', $funnel), [
            'ai_provider_setting_id' => $setting->id,
            'style' => 'minimal',
        ]);

        $response->assertSessionHasNoErrors();

        $salespage = Salespage::query()->where('funnel_id', $funnel->id)->firstOrFail();
        $this->assertSame('headline', $salespage->content[0]['type']);
        $this->assertSame('Kopi Terbaik di Kota', $salespage->content[0]['data']['text']);
        $this->assertSame('cta', $salespage->content[1]['type']);

        $this->assertDatabaseHas('ai_generation_logs', [
            'ai_provider_setting_id' => $setting->id,
            'status' => 'success',
        ]);
    }

    public function test_generate_request_requires_a_valid_style(): void
    {
        $user = User::factory()->create();
        $funnel = Funnel::factory()->for($user, 'creator')->create();
        $setting = AiProviderSetting::factory()->for($user, 'creator')->create();

        $response = $this->actingAs($user)->post(route('funnels.salespage.generate', $funnel), [
            'ai_provider_setting_id' => $setting->id,
            'style' => 'not-a-real-style',
        ]);

        $response->assertSessionHasErrors('style');
    }
}
