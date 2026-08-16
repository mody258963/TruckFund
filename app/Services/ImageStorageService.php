<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ImageStorageService
{
    public const DISK = 'documents';

    /** @return array{path: string, mime: string, bytes: int} */
    public function store(UploadedFile $file, string $directory): array
    {
        $uploadMaxKb = $this->documentMaxKb();

        if ($file->getSize() > $uploadMaxKb * 1024) {
            throw ValidationException::withMessages([
                'file' => __('settings.file_too_large', ['max' => $uploadMaxKb]),
            ]);
        }

        if (! $this->isImage($file)) {
            return $this->storeBinary($file, $directory);
        }

        // Large photos are accepted and downscaled; the stored result is what
        // has to fit within the image budget.
        $maxKb = $this->imageMaxKb();
        $maxBytes = $maxKb * 1024;

        $optimized = $this->optimizeImage($file, $maxBytes);
        $relativeDir = trim($directory, '/');
        $filename = Str::uuid().'.'.$optimized['extension'];
        $path = $relativeDir.'/'.$filename;

        Storage::disk(self::DISK)->put($path, $optimized['contents']);

        if (strlen($optimized['contents']) > $maxBytes) {
            Storage::disk(self::DISK)->delete($path);
            throw ValidationException::withMessages([
                'file' => __('settings.image_too_large', ['max' => $maxKb]),
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
        $path = str_replace('\\', '/', ltrim($path, '/'));

        if (! $this->exists($path)) {
            return null;
        }

        return str_replace('\\', '/', Storage::disk(self::DISK)->path($path));
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

    protected function imageMaxKb(): int
    {
        return (int) config('truckfund.image_max_kb', 2048);
    }

    protected function documentMaxKb(): int
    {
        return (int) config('truckfund.document_max_kb', 10240);
    }

    /** @return array{path: string, mime: string, bytes: int} */
    protected function storeBinary(UploadedFile $file, string $directory): array
    {
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
