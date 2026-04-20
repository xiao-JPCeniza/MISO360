<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

final class EquipmentImageUrls
{
    /**
     * Build absolute or site-root URLs for equipment photos stored on the public disk.
     *
     * @param  array<int, string>|null  $images
     * @return array<int, string>
     */
    public static function publicUrls(?array $images, ?string $legacySingle): array
    {
        $candidates = [];
        if (is_array($images) && $images !== []) {
            $candidates = $images;
        } elseif ($legacySingle !== null && $legacySingle !== '') {
            $candidates = [$legacySingle];
        }

        $urls = [];
        foreach ($candidates as $path) {
            if (! is_string($path)) {
                continue;
            }

            $url = self::resolve($path);
            if ($url !== '') {
                $urls[] = $url;
            }
        }

        return array_values(array_unique($urls));
    }

    public static function resolve(string $path): string
    {
        $path = trim($path);
        if ($path === '') {
            return '';
        }

        if (preg_match('#^https?://#i', $path) === 1) {
            return $path;
        }

        if (str_starts_with($path, '/storage/')) {
            return url($path);
        }

        $relative = ltrim($path, '/');
        if (str_starts_with($relative, 'storage/')) {
            $relative = substr($relative, strlen('storage/'));
        }

        return Storage::disk('public')->url($relative);
    }
}
