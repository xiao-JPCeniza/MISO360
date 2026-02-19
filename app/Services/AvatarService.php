<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class AvatarService
{
    private const DISK = 'public';

    private const AVATAR_DIR = 'avatars';

    private const MAX_SIDE = 400;

    /**
     * Store the uploaded avatar for the user and return the storage path.
     * Deletes the previous avatar if present.
     */
    public function storeAvatar(UploadedFile $file, User $user): string
    {
        $disk = Storage::disk(self::DISK);

        if ($user->avatar !== null && $user->avatar !== '') {
            $disk->delete($user->avatar);
        }

        $path = $this->resizeAndStore($file, $user->id);

        return $path;
    }

    /**
     * Resize image proportionally (no crop) and store under avatars/{id}/.
     */
    private function resizeAndStore(UploadedFile $file, int $userId): string
    {
        $mime = $file->getMimeType() ?? '';
        $sourcePath = $file->getRealPath();
        if ($sourcePath === false) {
            return $this->storeOriginal($file, $userId);
        }

        $image = $this->createImageFromPath($sourcePath, $mime);
        if ($image === null) {
            return $this->storeOriginal($file, $userId);
        }

        $width = imagesx($image);
        $height = imagesy($image);
        if ($width <= self::MAX_SIDE && $height <= self::MAX_SIDE) {
            imagedestroy($image);

            return $this->storeOriginal($file, $userId);
        }

        [$targetWidth, $targetHeight] = $this->scaledDimensions($width, $height);

        $resized = imagecreatetruecolor($targetWidth, $targetHeight);
        if ($resized === false) {
            imagedestroy($image);

            return $this->storeOriginal($file, $userId);
        }

        $this->prepareCanvas($resized, $mime);

        imagecopyresampled(
            $resized, $image,
            0, 0, 0, 0,
            $targetWidth, $targetHeight, $width, $height
        );
        imagedestroy($image);

        $filename = 'avatar-'.uniqid('', true).$this->extensionForMime($mime);
        $relativePath = self::AVATAR_DIR.'/'.$userId.'/'.$filename;
        $fullPath = Storage::disk(self::DISK)->path($relativePath);

        $dir = dirname($fullPath);
        if (! is_dir($dir) && ! mkdir($dir, 0755, true) && ! is_dir($dir)) {
            imagedestroy($resized);
            throw new \RuntimeException('Failed to create avatar directory.');
        }

        $saved = $this->writeImage($resized, $fullPath, $mime);
        imagedestroy($resized);

        if (! $saved) {
            return $this->storeOriginal($file, $userId);
        }

        return $relativePath;
    }

    private function storeOriginal(UploadedFile $file, int $userId): string
    {
        $path = $file->store(self::AVATAR_DIR.'/'.$userId, self::DISK);
        if ($path === false) {
            throw new \RuntimeException('Failed to store avatar file.');
        }

        return $path;
    }

    /**
     * @return array{int, int}
     */
    private function scaledDimensions(int $width, int $height): array
    {
        if ($width >= $height) {
            $targetWidth = self::MAX_SIDE;
            $targetHeight = max(1, (int) round(($height / $width) * self::MAX_SIDE));
        } else {
            $targetHeight = self::MAX_SIDE;
            $targetWidth = max(1, (int) round(($width / $height) * self::MAX_SIDE));
        }

        return [$targetWidth, $targetHeight];
    }

    private function prepareCanvas(\GdImage $image, string $mime): void
    {
        if ($mime === 'image/png' || $mime === 'image/webp') {
            imagealphablending($image, false);
            imagesavealpha($image, true);
            $transparent = imagecolorallocatealpha($image, 0, 0, 0, 127);
            imagefill($image, 0, 0, $transparent);

            return;
        }

        $white = imagecolorallocate($image, 255, 255, 255);
        imagefill($image, 0, 0, $white);
    }

    private function extensionForMime(string $mime): string
    {
        return match ($mime) {
            'image/png' => '.png',
            'image/webp' => '.webp',
            default => '.jpg',
        };
    }

    private function writeImage(\GdImage $image, string $path, string $mime): bool
    {
        return match ($mime) {
            'image/png' => imagepng($image, $path, 6),
            'image/webp' => function_exists('imagewebp') ? imagewebp($image, $path, 90) : false,
            default => imagejpeg($image, $path, 90),
        };
    }

    private function createImageFromPath(string $path, string $mime): ?\GdImage
    {
        return match ($mime) {
            'image/jpeg', 'image/jpg' => @imagecreatefromjpeg($path) ?: null,
            'image/png' => @imagecreatefrompng($path) ?: null,
            'image/webp' => @imagecreatefromwebp($path) ?: null,
            default => null,
        };
    }
}
