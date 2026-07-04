<?php

namespace Tests\Feature\Funnels;

use App\Enums\FunnelStatus;
use App\Models\Funnel;
use App\Models\Product;
use App\Models\ProductDigitalAsset;
use App\Models\Salespage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FunnelManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_funnels(): void
    {
        $response = $this->get(route('funnels.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_funnel_index_is_displayed(): void
    {
        $user = User::factory()->create();
        Funnel::factory()->for($user, 'creator')->count(2)->create();

        $response = $this->actingAs($user)->get(route('funnels.index'));

        $response->assertOk();
    }

    public function test_funnel_can_be_created_for_a_product(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->for($user, 'creator')->digital()->create();

        $response = $this->actingAs($user)->post(route('funnels.store'), [
            'product_id' => $product->id,
            'name' => 'Funnel Kopi',
            'slug' => 'funnel-kopi',
        ]);

        $response->assertSessionHasNoErrors();

        $funnel = Funnel::query()->where('slug', 'funnel-kopi')->firstOrFail();
        $this->assertSame($user->id, $funnel->created_by);
        $this->assertSame(FunnelStatus::Draft, $funnel->status);
        $response->assertRedirect(route('funnels.edit', $funnel));
    }

    public function test_funnel_slug_must_be_unique(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->for($user, 'creator')->digital()->create();
        Funnel::factory()->for($user, 'creator')->create(['slug' => 'dipakai']);

        $response = $this->actingAs($user)->post(route('funnels.store'), [
            'product_id' => $product->id,
            'name' => 'Funnel Lain',
            'slug' => 'dipakai',
        ]);

        $response->assertSessionHasErrors('slug');
    }

    public function test_funnel_detail_can_be_updated(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->for($user, 'creator')->digital()->create();
        $funnel = Funnel::factory()->for($user, 'creator')->create(['product_id' => $product->id]);

        $response = $this->actingAs($user)->put(route('funnels.update', $funnel), [
            'product_id' => $product->id,
            'name' => 'Nama Baru',
            'slug' => $funnel->slug,
            'status' => FunnelStatus::Draft->value,
            'thank_you_message' => 'Terima kasih!',
            'fb_pixel_id' => '1234567890',
        ]);

        $response->assertSessionHasNoErrors();

        $funnel->refresh();
        $this->assertSame('Nama Baru', $funnel->name);
        $this->assertSame('Terima kasih!', $funnel->thank_you_content['message']);
        $this->assertSame('1234567890', $funnel->pixel_settings['fb_pixel_id']);
    }

    public function test_funnel_cannot_be_published_without_a_salespage(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->for($user, 'creator')->physical()->create();
        $funnel = Funnel::factory()->for($user, 'creator')->create(['product_id' => $product->id]);

        $response = $this->actingAs($user)->put(route('funnels.update', $funnel), [
            'product_id' => $product->id,
            'name' => $funnel->name,
            'slug' => $funnel->slug,
            'status' => FunnelStatus::Published->value,
        ]);

        $response->assertSessionHasErrors('status');
        $this->assertSame(FunnelStatus::Draft, $funnel->fresh()->status);
    }

    public function test_funnel_with_digital_product_cannot_be_published_without_digital_asset(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->for($user, 'creator')->digital()->create();
        $funnel = Funnel::factory()->for($user, 'creator')->create(['product_id' => $product->id]);
        Salespage::factory()->create(['funnel_id' => $funnel->id]);

        $response = $this->actingAs($user)->put(route('funnels.update', $funnel), [
            'product_id' => $product->id,
            'name' => $funnel->name,
            'slug' => $funnel->slug,
            'status' => FunnelStatus::Published->value,
        ]);

        $response->assertSessionHasErrors('status');
        $this->assertSame(FunnelStatus::Draft, $funnel->fresh()->status);
    }

    public function test_funnel_can_be_published_once_requirements_are_met(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->for($user, 'creator')->digital()->create();
        ProductDigitalAsset::factory()->for($product)->create();
        $funnel = Funnel::factory()->for($user, 'creator')->create(['product_id' => $product->id]);
        Salespage::factory()->create(['funnel_id' => $funnel->id]);

        $response = $this->actingAs($user)->put(route('funnels.update', $funnel), [
            'product_id' => $product->id,
            'name' => $funnel->name,
            'slug' => $funnel->slug,
            'status' => FunnelStatus::Published->value,
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertSame(FunnelStatus::Published, $funnel->fresh()->status);
    }

    public function test_funnel_can_be_deleted(): void
    {
        $user = User::factory()->create();
        $funnel = Funnel::factory()->for($user, 'creator')->create();

        $response = $this->actingAs($user)->delete(route('funnels.destroy', $funnel));

        $response->assertRedirect(route('funnels.index'));
        $this->assertDatabaseMissing('funnels', ['id' => $funnel->id]);
    }
}
