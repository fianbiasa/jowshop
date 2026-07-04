<?php

namespace Tests\Feature\Settings;

use App\Enums\AiProvider;
use App\Models\AiProviderSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AiProviderSettingTest extends TestCase
{
    use RefreshDatabase;

    private function fakeSuccessfulOpenAiPing(): void
    {
        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [['message' => ['content' => 'OK']]],
                'usage' => ['prompt_tokens' => 10, 'completion_tokens' => 1],
            ], 200),
        ]);
    }

    private function fakeSuccessfulAnthropicPing(): void
    {
        Http::fake([
            'api.anthropic.com/*' => Http::response([
                'content' => [['text' => 'OK']],
                'usage' => ['input_tokens' => 10, 'output_tokens' => 1],
            ], 200),
        ]);
    }

    public function test_guest_cannot_access_ai_provider_settings(): void
    {
        $response = $this->get(route('ai-providers.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_ai_provider_settings_page_is_displayed(): void
    {
        $user = User::factory()->create();
        AiProviderSetting::factory()->for($user, 'creator')->create();

        $response = $this->actingAs($user)->get(route('ai-providers.index'));

        $response->assertOk();
    }

    public function test_ai_provider_can_be_added_with_encrypted_api_key(): void
    {
        $this->fakeSuccessfulOpenAiPing();
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('ai-providers.store'), [
            'provider' => AiProvider::OpenAi->value,
            'label' => 'OpenAI Utama',
            'api_key' => 'sk-super-secret-key',
            'default_model' => 'gpt-4.1',
            'is_default' => 1,
            'is_active' => 1,
        ]);

        $response->assertSessionHasNoErrors();

        $setting = AiProviderSetting::query()->where('label', 'OpenAI Utama')->firstOrFail();
        $this->assertSame('sk-super-secret-key', $setting->api_key);

        $rawColumn = DB::table('ai_provider_settings')->where('id', $setting->id)->value('api_key');
        $this->assertStringNotContainsString('sk-super-secret-key', $rawColumn);
    }

    public function test_setting_a_new_default_provider_unsets_the_previous_default(): void
    {
        $this->fakeSuccessfulAnthropicPing();
        $user = User::factory()->create();
        $existingDefault = AiProviderSetting::factory()->for($user, 'creator')->create(['is_default' => true]);

        $this->actingAs($user)->post(route('ai-providers.store'), [
            'provider' => AiProvider::Anthropic->value,
            'label' => 'Anthropic Baru',
            'api_key' => 'sk-ant-secret',
            'default_model' => 'claude-sonnet-5',
            'is_default' => 1,
            'is_active' => 1,
        ]);

        $this->assertFalse($existingDefault->fresh()->is_default);
        $this->assertTrue(
            AiProviderSetting::query()->where('label', 'Anthropic Baru')->firstOrFail()->is_default
        );
    }

    public function test_ai_provider_is_not_saved_if_test_connection_fails(): void
    {
        Http::fake(['api.openai.com/*' => Http::response(['error' => 'invalid api key'], 401)]);
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('ai-providers.store'), [
            'provider' => AiProvider::OpenAi->value,
            'label' => 'OpenAI Salah',
            'api_key' => 'sk-invalid-key',
            'default_model' => 'gpt-4.1',
            'is_default' => 1,
            'is_active' => 1,
        ]);

        $response->assertSessionHasErrors('api_key');
        $this->assertDatabaseMissing('ai_provider_settings', ['label' => 'OpenAI Salah']);
    }

    public function test_ai_provider_can_be_deleted(): void
    {
        $user = User::factory()->create();
        $setting = AiProviderSetting::factory()->for($user, 'creator')->create();

        $response = $this->actingAs($user)->delete(route('ai-providers.destroy', $setting));

        $response->assertRedirect(route('ai-providers.index'));
        $this->assertDatabaseMissing('ai_provider_settings', ['id' => $setting->id]);
    }
}
