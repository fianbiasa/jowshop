<?php

namespace Tests\Feature\Products;

use App\Models\Product;
use App\Models\ProductDigitalAsset;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductDigitalAssetTest extends TestCase
{
    use RefreshDatabase;

    public function test_file_can_be_uploaded_for_digital_product(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $product = Product::factory()->digital()->for($user, 'creator')->create();

        $response = $this->actingAs($user)->post(route('products.digital-assets.store', $product), [
            'file' => UploadedFile::fake()->create('ebook.pdf', 500),
            'license_type' => 'none',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('products.edit', $product));

        $asset = ProductDigitalAsset::query()->where('product_id', $product->id)->firstOrFail();
        $this->assertNotNull($asset->file_path);
        Storage::disk('local')->assertExists($asset->file_path);
    }

    public function test_external_url_can_be_used_instead_of_file(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->digital()->for($user, 'creator')->create();

        $response = $this->actingAs($user)->post(route('products.digital-assets.store', $product), [
            'external_url' => 'https://drive.google.com/file/xyz',
            'license_type' => 'license_key',
            'max_downloads' => 3,
        ]);

        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('product_digital_assets', [
            'product_id' => $product->id,
            'external_url' => 'https://drive.google.com/file/xyz',
            'license_type' => 'license_key',
            'max_downloads' => 3,
        ]);
    }

    public function test_asset_requires_either_file_or_external_url(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->digital()->for($user, 'creator')->create();

        $response = $this->actingAs($user)->post(route('products.digital-assets.store', $product), [
            'license_type' => 'none',
        ]);

        $response->assertSessionHasErrors(['file', 'external_url']);
    }

    public function test_guest_cannot_upload_assets(): void
    {
        $product = Product::factory()->digital()->create();

        $response = $this->post(route('products.digital-assets.store', $product), [
            'external_url' => 'https://drive.google.com/file/xyz',
            'license_type' => 'none',
        ]);

        $response->assertRedirect(route('login'));
    }

    public function test_asset_can_be_deleted(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $product = Product::factory()->digital()->for($user, 'creator')->create();
        $asset = ProductDigitalAsset::factory()->for($product)->create([
            'file_path' => 'digital-assets/1/ebook.pdf',
        ]);
        Storage::disk('local')->put($asset->file_path, 'contents');

        $response = $this->actingAs($user)->delete(route('products.digital-assets.destroy', [$product, $asset]));

        $response->assertRedirect(route('products.edit', $product));
        $this->assertDatabaseMissing('product_digital_assets', ['id' => $asset->id]);
        Storage::disk('local')->assertMissing($asset->file_path);
    }
}
