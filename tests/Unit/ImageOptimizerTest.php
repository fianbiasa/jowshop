<?php

namespace Tests\Unit;

use App\Services\ImageOptimizer;
use Illuminate\Http\Testing\File;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use Tests\TestCase;

class ImageOptimizerTest extends TestCase
{
    public function test_large_images_are_downscaled_and_converted_to_webp(): void
    {
        Storage::fake('public');

        $file = File::image('camera-photo.jpg', 3000, 2000);

        $path = (new ImageOptimizer)->store($file, 'salespage-media', maxWidth: 1600);

        $this->assertStringEndsWith('.webp', $path);
        Storage::disk('public')->assertExists($path);

        $stored = (new ImageManager(new Driver))->read(
            Storage::disk('public')->path($path),
        );

        $this->assertSame(1600, $stored->width());
        $this->assertSame(1067, $stored->height());
    }

    public function test_small_images_are_not_upscaled(): void
    {
        Storage::fake('public');

        $file = File::image('icon.jpg', 200, 200);

        $path = (new ImageOptimizer)->store($file, 'branding', maxWidth: 600);

        $stored = (new ImageManager(new Driver))->read(
            Storage::disk('public')->path($path),
        );

        $this->assertSame(200, $stored->width());
        $this->assertSame(200, $stored->height());
    }
}
