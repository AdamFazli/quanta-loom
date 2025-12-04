<?php

namespace App\Services;

use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class FileUploadService
{
    public function upload(UploadedFile $file, string $disk = 'public', string $directory = 'images', ?int $postingId = null): string
    {
        $filename = time() . '_' . $file->getClientOriginalName();

        if ($postingId) {
            $directory = $directory . '/posting-' . $postingId;
        }

        $path = Storage::disk($disk)->putFileAs(
            $directory,
            $file,
            $filename
        );

        return $path;
    }

    public function url(string $path, string $disk = 'public', int $expirationMinutes = 60): string
    {
        if ($disk === 's3') {
            return Storage::disk($disk)->temporaryUrl($path, now()->addMinutes($expirationMinutes));
        }

        return Storage::disk($disk)->url($path);
    }

    public function delete(string $path, string $disk = 'public'): bool
    {
        return Storage::disk($disk)->delete($path);
    }
}