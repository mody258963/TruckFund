<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class ImageStorageService
{
    public const DISK = 'documents';

    /** @return array{path: string, mime: string, bytes: int} */
    public function store(UploadedFile $file, string $directory): array
    {
        $maxBytes = config('truckfund.image_max_kb', 2048) * 1024;

        if ($file->getSize() > $maxBytes) {
            throw ValidationException::withMessages([
                'file' => __('settings.image_too_large', ['max' => config('truckfund.image_max_kb', 2048)]),
            ]);
        }

        if (! $this->isImage($file)) {
            return $this->storeBinary($file, $directory, $maxBytes);
        }

        $optimized = $this->optimizeImage($file, $maxBytes);
        $relativeDir = trim($directory, '/');
        $filename = Str::uuid().'.'.$optimized['extension'];
        $path = $relativeDir.'/'.$filename;

        Storage::disk(self::DISK)->put($path, $optimized['contents']);

        if (strlen($optimized['contents']) > $maxBytes) {
            Storage::disk(self::DISK)->delete($path);
            throw ValidationException::withMessages([
                'file' => __('settings.image_too_large', ['max' => config('truckfund.image_max_kb', 2048)]),
            ]);
        }

        return [
            'path' => $path,
            'mime' => $optimized['mime'],
            'bytes' => strlen($optimized['contents']),
        ];
    }

    public function url(string $path): string
    {
        return route('files.show', ['path' => $this->encodePath($path)]);
    }

    public function isImagePath(string $path): bool
    {
        return (bool) preg_match('/\.(jpe?g|png|webp|gif)$/i', $path);
    }

    public function exists(string $path): bool
    {
        return Storage::disk(self::DISK)->exists($path);
    }

    public function absolutePath(string $path): ?string
    {
        if (! $this->exists($path)) {
            return null;
        }

        return Storage::disk(self::DISK)->path($path);
    }

    public function dataUri(string $path): ?string
    {
        if (! $this->isImagePath($path)) {
            return null;
        }

        $absolute = $this->absolutePath($path);
        if ($absolute === null) {
            return null;
        }

        $mime = match (true) {
            (bool) preg_match('/\.png$/i', $path) => 'image/png',
            (bool) preg_match('/\.webp$/i', $path) => 'image/webp',
            (bool) preg_match('/\.gif$/i', $path) => 'image/gif',
            default => 'image/jpeg',
        };

        return 'data:'.$mime.';base64,'.base64_encode((string) file_get_contents($absolute));
    }

    public function encodePath(string $path): string
    {
        return strtr(base64_encode($path), '+/', '-_');
    }

    public function decodePath(string $encoded): ?string
    {
        $decoded = base64_decode(strtr($encoded, '-_', '+/'), true);

        return is_string($decoded) && $decoded !== '' ? $decoded : null;
    }

    protected function isImage(UploadedFile $file): bool
    {
        return in_array($file->getMimeType(), ['image/jpeg', 'image/png', 'image/webp', 'image/gif'], true);
    }

    /** @return array{path: string, mime: string, bytes: int} */
    protected function storeBinary(UploadedFile $file, string $directory, int $maxBytes): array
    {
        if ($file->getSize() > $maxBytes) {
            throw ValidationException::withMessages([
                'file' => __('settings.file_too_large', ['max' => config('truckfund.image_max_kb', 2048)]),
            ]);
        }

        $path = $file->store(trim($directory, '/'), self::DISK);

        return [
            'path' => $path,
            'mime' => $file->getMimeType() ?? 'application/octet-stream',
            'bytes' => (int) $file->getSize(),
        ];
    }

    /**
     * @return array{contents: string, extension: string, mime: string}
     */
    protected function optimizeImage(UploadedFile $file, int $maxBytes): array
    {
        if (! extension_loaded('gd')) {
            return $this->passthroughImage($file);
        }

        $source = @imagecreatefromstring((string) file_get_contents($file->getRealPath()));
        if ($source === false) {
            throw ValidationException::withMessages(['file' => __('settings.invalid_image')]);
        }

        $width = imagesx($source);
        $height = imagesy($source);
        $maxWidth = config('truckfund.image_max_width', 1920);
        $quality = config('truckfund.image_jpeg_quality', 82);

        if ($width > $maxWidth) {
            $newHeight = (int) round($height * ($maxWidth / $width));
            $resized = imagecreatetruecolor($maxWidth, $newHeight);
            imagecopyresampled($resized, $source, 0, 0, 0, 0, $maxWidth, $newHeight, $width, $height);
            imagedestroy($source);
            $source = $resized;
        }

        ob_start();
        imagejpeg($source, null, $quality);
        $contents = (string) ob_get_clean();
        imagedestroy($source);

        if (strlen($contents) > $maxBytes && $quality > 55) {
            $source = @imagecreatefromstring((string) file_get_contents($file->getRealPath()));
            if ($source !== false) {
                if ($width > $maxWidth) {
                    $newHeight = (int) round($height * ($maxWidth / $width));
                    $resized = imagecreatetruecolor($maxWidth, $newHeight);
                    imagecopyresampled($resized, $source, 0, 0, 0, 0, $maxWidth, $newHeight, $width, $height);
                    imagedestroy($source);
                    $source = $resized;
                }
                ob_start();
                imagejpeg($source, null, 60);
                $contents = (string) ob_get_clean();
                imagedestroy($source);
            }
        }

        return [
            'contents' => $contents,
            'extension' => 'jpg',
            'mime' => 'image/jpeg',
        ];
    }

    /**
     * @return array{contents: string, extension: string, mime: string}
     */
    protected function passthroughImage(UploadedFile $file): array
    {
        $ext = $file->guessExtension() ?: 'bin';

        return [
            'contents' => (string) file_get_contents($file->getRealPath()),
            'extension' => $ext,
            'mime' => $file->getMimeType() ?? 'application/octet-stream',
        ];
    }
}
