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
     * Store the uploaded avatar for the user (resized to square) and return the storage path.
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
     * Resize image to a square (center crop) and store under avatars/{id}/.
     */
    private function resizeAndStore(UploadedFile $file, int $userId): string
    {
        $mime = $file->getMimeType();
        $sourcePath = $file->getRealPath();

        $image = $this->createImageFromPath($sourcePath, $mime);
        if ($image === null) {
            $path = $file->store(self::AVATAR_DIR.'/'.$userId, self::DISK);
            if ($path === false) {
                throw new \RuntimeException('Failed to store avatar file.');
            }

            return $path;
        }

        $width = imagesx($image);
        $height = imagesy($image);
        $size = min($width, $height, self::MAX_SIDE);
        $srcX = (int) floor(($width - $size) / 2);
        $srcY = (int) floor(($height - $size) / 2);

        $resized = imagecreatetruecolor($size, $size);
        if ($resized === false) {
            imagedestroy($image);
            $path = $file->store(self::AVATAR_DIR.'/'.$userId, self::DISK);
            if ($path === false) {
                throw new \RuntimeException('Failed to store avatar file.');
            }

            return $path;
        }

        imagecopyresampled(
            $resized, $image,
            0, 0, $srcX, $srcY,
            $size, $size, $size, $size
        );
        imagedestroy($image);

        $filename = 'avatar-'.uniqid('', true).'.jpg';
        $relativePath = self::AVATAR_DIR.'/'.$userId.'/'.$filename;
        $fullPath = Storage::disk(self::DISK)->path($relativePath);

        $dir = dirname($fullPath);
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        imagejpeg($resized, $fullPath, 90);
        imagedestroy($resized);

        return $relativePath;
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
