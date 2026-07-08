<?php

namespace Tests\Feature;

use App\Models\BrandingSetting;
use App\Models\Funnel;
use App\Models\Product;
use App\Models\Salespage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Testing\File;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OptimizeExistingImagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_reoptimizes_an_unoptimized_product_thumbnail(): void
    {
        Storage::fake('public');
        $path = Storage::disk('public')->putFile('products', File::image('camera.jpg', 3000, 2000));
        $product = Product::factory()->create(['thumbnail_path' => $path]);

        $this->artisan('images:optimize-existing')->assertExitCode(0);

        $product->refresh();
        $this->assertStringEndsWith('.webp', $product->thumbnail_path);
        $this->assertNotSame($path, $product->thumbnail_path);
        Storage::disk('public')->assertMissing($path);
        Storage::disk('public')->assertExists($product->thumbnail_path);
    }

    public function test_it_skips_a_product_thumbnail_that_is_already_webp(): void
    {
        Storage::fake('public');
        $path = 'products/already-optimized.webp';
        Storage::disk('public')->put($path, 'fake-webp-bytes');
        $product = Product::factory()->create(['thumbnail_path' => $path]);

        $this->artisan('images:optimize-existing')->assertExitCode(0);

        $this->assertSame($path, $product->fresh()->thumbnail_path);
        Storage::disk('public')->assertExists($path);
    }

    public function test_it_reoptimizes_the_branding_logo(): void
    {
        Storage::fake('public');
        $path = Storage::disk('public')->putFile('branding', File::image('logo.png', 2000, 800));
        BrandingSetting::factory()->create(['logo_path' => $path]);

        $this->artisan('images:optimize-existing')->assertExitCode(0);

        $setting = BrandingSetting::query()->firstOrFail();
        $this->assertStringEndsWith('.webp', $setting->logo_path);
        Storage::disk('public')->assertMissing($path);
        Storage::disk('public')->assertExists($setting->logo_path);
    }

    public function test_it_reoptimizes_salespage_image_blocks_and_leaves_external_urls_alone(): void
    {
        Storage::fake('public');
        $path = Storage::disk('public')->putFile('salespage-media', File::image('banner.jpg', 3000, 2000));
        $funnel = Funnel::factory()->create();
        $salespage = Salespage::factory()->for($funnel)->create([
            'content' => [
                ['type' => 'headline', 'data' => ['text' => 'Judul']],
                ['type' => 'image', 'data' => [
                    'url' => Storage::disk('public')->url($path),
                    'alt' => 'Banner',
                ]],
                ['type' => 'image', 'data' => [
                    'url' => 'https://cdn.example.com/external-banner.jpg',
                    'alt' => 'External',
                ]],
            ],
        ]);

        $this->artisan('images:optimize-existing')->assertExitCode(0);

        $salespage->refresh();
        $localImageUrl = $salespage->content[1]['data']['url'];
        $externalImageUrl = $salespage->content[2]['data']['url'];

        $this->assertStringContainsString('.webp', $localImageUrl);
        $this->assertStringNotContainsString($path, $localImageUrl);
        $this->assertSame('https://cdn.example.com/external-banner.jpg', $externalImageUrl);
        Storage::disk('public')->assertMissing($path);
    }
}
