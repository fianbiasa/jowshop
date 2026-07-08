<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

class ImageOptimizer
{
    private readonly ImageManager $manager;

    public function __construct()
    {
        $this->manager = new ImageManager(new Driver);
    }

    /**
     * Downscale (never upscale) and re-encode an uploaded image as WebP
     * before storing it on the `public` disk. A raw multi-megabyte camera
     * photo dropped into a salespage/product/logo field would otherwise be
     * served as-is, which is what tanks Largest Contentful Paint on mobile.
     *
     * @return string The stored path, relative to the disk root.
     */
    public function store(UploadedFile $file, string $directory, int $maxWidth = 1600, int $quality = 80): string
    {
        return $this->process($file->getRealPath(), $directory, $maxWidth, $quality);
    }

    /**
     * Re-optimize a file that's already on the `public` disk (used by
     * images:optimize-existing to clean up uploads made before this
     * pipeline existed) and delete the original.
     *
     * @return string The new stored path.
     */
    public function reoptimize(string $existingPath, string $directory, int $maxWidth = 1600, int $quality = 80): string
    {
        $newPath = $this->process(
            Storage::disk('public')->path($existingPath),
            $directory,
            $maxWidth,
            $quality,
        );

        Storage::disk('public')->delete($existingPath);

        return $newPath;
    }

    private function process(string $absolutePath, string $directory, int $maxWidth, int $quality): string
    {
        $image = $this->manager
            ->read($absolutePath)
            ->scaleDown(width: $maxWidth);

        $path = trim($directory, '/').'/'.Str::uuid().'.webp';

        Storage::disk('public')->put($path, (string) $image->toWebp($quality));

        return $path;
    }
}
