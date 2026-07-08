<?php

namespace App\Console\Commands;

use App\Models\BrandingSetting;
use App\Models\Product;
use App\Models\Salespage;
use App\Services\ImageOptimizer;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

#[Signature('images:optimize-existing')]
#[Description('Downscale and convert to WebP every already-uploaded product thumbnail, the branding logo, and salespage images — a one-off cleanup for uploads made before automatic optimization existed.')]
class OptimizeExistingImages extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(ImageOptimizer $optimizer): int
    {
        $this->optimizeProductThumbnails($optimizer);
        $this->optimizeBrandingLogo($optimizer);
        $this->optimizeSalespageImages($optimizer);

        $this->info('Done.');

        return self::SUCCESS;
    }

    private function optimizeProductThumbnails(ImageOptimizer $optimizer): void
    {
        Product::query()->whereNotNull('thumbnail_path')->each(function (Product $product) use ($optimizer): void {
            if (str_ends_with((string) $product->thumbnail_path, '.webp')) {
                return;
            }

            $product->thumbnail_path = $optimizer->reoptimize($product->thumbnail_path, 'products', 800);
            $product->save();

            $this->line("Optimized thumbnail for product #{$product->id} ({$product->name}).");
        });
    }

    private function optimizeBrandingLogo(ImageOptimizer $optimizer): void
    {
        $setting = BrandingSetting::query()->whereNotNull('logo_path')->first();

        if ($setting === null || str_ends_with((string) $setting->logo_path, '.webp')) {
            return;
        }

        $setting->logo_path = $optimizer->reoptimize($setting->logo_path, 'branding', 600);
        $setting->save();

        $this->line('Optimized branding logo.');
    }

    private function optimizeSalespageImages(ImageOptimizer $optimizer): void
    {
        Salespage::query()->each(function (Salespage $salespage) use ($optimizer): void {
            $content = $salespage->content;
            $changed = false;

            foreach ($content as &$block) {
                if (($block['type'] ?? null) !== 'image') {
                    continue;
                }

                $url = $block['data']['url'] ?? null;

                if (! is_string($url)) {
                    continue;
                }

                $urlPath = (string) parse_url($url, PHP_URL_PATH);

                if (! str_contains($urlPath, '/storage/')) {
                    continue;
                }

                $relativePath = Str::after($urlPath, '/storage/');

                if (str_ends_with($relativePath, '.webp') || ! Storage::disk('public')->exists($relativePath)) {
                    continue;
                }

                $newPath = $optimizer->reoptimize($relativePath, 'salespage-media', 1600);
                $block['data']['url'] = Storage::disk('public')->url($newPath);
                $changed = true;
            }
            unset($block);

            if ($changed) {
                $salespage->content = $content;
                $salespage->save();

                $this->line("Optimized image(s) for salespage #{$salespage->id}.");
            }
        });
    }
}
