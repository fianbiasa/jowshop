<?php

namespace Tests\Feature\Products;

use App\Enums\ProductStatus;
use App\Enums\ProductType;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_products(): void
    {
        $response = $this->get(route('products.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_product_index_is_displayed(): void
    {
        $user = User::factory()->create();
        Product::factory()->count(3)->for($user, 'creator')->create();

        $response = $this->actingAs($user)->get(route('products.index'));

        $response->assertOk();
    }

    public function test_digital_product_can_be_created(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('products.store'), [
            'name' => 'Ebook Belajar Laravel',
            'slug' => 'ebook-belajar-laravel',
            'type' => ProductType::Digital->value,
            'description' => 'Panduan lengkap Laravel.',
            'price' => 99000,
            'sku' => 'EBOOK-001',
            'status' => ProductStatus::Draft->value,
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('products.index'));

        $this->assertDatabaseHas('products', [
            'name' => 'Ebook Belajar Laravel',
            'slug' => 'ebook-belajar-laravel',
            'type' => ProductType::Digital->value,
            'created_by' => $user->id,
        ]);
    }

    public function test_physical_product_requires_weight_and_stock(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('products.store'), [
            'name' => 'Kopi Robusta 200gr',
            'slug' => 'kopi-robusta-200gr',
            'type' => ProductType::Physical->value,
            'price' => 45000,
            'status' => ProductStatus::Draft->value,
        ]);

        $response->assertSessionHasErrors(['weight_grams', 'stock']);
        $this->assertDatabaseMissing('products', ['slug' => 'kopi-robusta-200gr']);
    }

    public function test_physical_product_can_be_created_with_shipping_details(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('products.store'), [
            'name' => 'Kopi Robusta 200gr',
            'slug' => 'kopi-robusta-200gr',
            'type' => ProductType::Physical->value,
            'price' => 45000,
            'status' => ProductStatus::Draft->value,
            'weight_grams' => 250,
            'stock' => 50,
        ]);

        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('products', [
            'slug' => 'kopi-robusta-200gr',
            'weight_grams' => 250,
            'stock' => 50,
        ]);
    }

    public function test_product_slug_must_be_unique(): void
    {
        $user = User::factory()->create();
        Product::factory()->for($user, 'creator')->create(['slug' => 'kopi-robusta']);

        $response = $this->actingAs($user)->post(route('products.store'), [
            'name' => 'Kopi Robusta Lain',
            'slug' => 'kopi-robusta',
            'type' => ProductType::Digital->value,
            'price' => 45000,
            'status' => ProductStatus::Draft->value,
        ]);

        $response->assertSessionHasErrors('slug');
    }

    public function test_product_can_be_updated(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->digital()->for($user, 'creator')->create();

        $response = $this->actingAs($user)->put(route('products.update', $product), [
            'name' => 'Nama Baru',
            'slug' => $product->slug,
            'type' => ProductType::Digital->value,
            'price' => 150000,
            'status' => ProductStatus::Published->value,
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('products.index'));

        $this->assertSame('Nama Baru', $product->fresh()->name);
        $this->assertSame(ProductStatus::Published, $product->fresh()->status);
    }

    public function test_product_can_be_created_with_a_thumbnail(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('products.store'), [
            'name' => 'Kopi Robusta 200gr',
            'slug' => 'kopi-robusta-200gr',
            'type' => ProductType::Digital->value,
            'price' => 45000,
            'status' => ProductStatus::Draft->value,
            'thumbnail' => UploadedFile::fake()->image('thumbnail.png'),
        ]);

        $response->assertSessionHasNoErrors();

        $product = Product::query()->where('slug', 'kopi-robusta-200gr')->firstOrFail();
        $this->assertNotNull($product->thumbnail_path);
        Storage::disk('public')->assertExists($product->thumbnail_path);
        $this->assertNotNull($product->thumbnail_url);
    }

    public function test_uploading_a_replacement_thumbnail_deletes_the_old_file(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $product = Product::factory()->digital()->for($user, 'creator')->create([
            'thumbnail_path' => 'products/original.png',
        ]);
        Storage::disk('public')->put('products/original.png', 'fake-image-content');

        $response = $this->actingAs($user)->put(route('products.update', $product), [
            'name' => $product->name,
            'slug' => $product->slug,
            'type' => ProductType::Digital->value,
            'price' => $product->price,
            'status' => ProductStatus::Draft->value,
            'thumbnail' => UploadedFile::fake()->image('replacement.png'),
        ]);

        $response->assertSessionHasNoErrors();

        $product->refresh();
        $this->assertNotSame('products/original.png', $product->thumbnail_path);
        Storage::disk('public')->assertMissing('products/original.png');
        Storage::disk('public')->assertExists($product->thumbnail_path);
    }

    public function test_remove_thumbnail_clears_the_thumbnail_path(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $product = Product::factory()->digital()->for($user, 'creator')->create([
            'thumbnail_path' => 'products/original.png',
        ]);
        Storage::disk('public')->put('products/original.png', 'fake-image-content');

        $response = $this->actingAs($user)->put(route('products.update', $product), [
            'name' => $product->name,
            'slug' => $product->slug,
            'type' => ProductType::Digital->value,
            'price' => $product->price,
            'status' => ProductStatus::Draft->value,
            'remove_thumbnail' => 1,
        ]);

        $response->assertSessionHasNoErrors();

        $product->refresh();
        $this->assertNull($product->thumbnail_path);
        Storage::disk('public')->assertMissing('products/original.png');
    }

    public function test_product_can_be_deleted(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->for($user, 'creator')->create();

        $response = $this->actingAs($user)->delete(route('products.destroy', $product));

        $response->assertRedirect(route('products.index'));
        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }
}
