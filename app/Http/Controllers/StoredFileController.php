<?php

namespace App\Http\Controllers;

use App\Services\ImageStorageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class StoredFileController extends Controller
{
    public function __construct(protected ImageStorageService $images) {}

    public function show(Request $request, string $path): BinaryFileResponse
    {
        $decoded = $this->images->decodePath($path);
        if (! $decoded || str_contains($decoded, '..')) {
            abort(404);
        }

        $disk = Storage::disk(ImageStorageService::DISK);
        if (! $disk->exists($decoded)) {
            abort(404);
        }

        $absolute = $disk->path($decoded);
        $mime = $disk->mimeType($decoded) ?: 'application/octet-stream';

        return response()->file($absolute, [
            'Content-Type' => $mime,
            'Cache-Control' => 'private, max-age=604800, immutable',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
