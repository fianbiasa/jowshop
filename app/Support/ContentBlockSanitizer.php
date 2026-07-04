<?php

namespace App\Support;

class ContentBlockSanitizer
{
    /**
     * Recursively strip HTML/script tags from every string leaf value in a
     * salespage content block array, whether it comes from AI generation or
     * manual admin input.
     *
     * @param  array<int|string, mixed>  $blocks
     * @return array<int|string, mixed>
     */
    public static function sanitize(array $blocks): array
    {
        return array_map(self::sanitizeValue(...), $blocks);
    }

    private static function sanitizeValue(mixed $value): mixed
    {
        if (is_string($value)) {
            return strip_tags($value);
        }

        if (is_array($value)) {
            return array_map(self::sanitizeValue(...), $value);
        }

        return $value;
    }
}
