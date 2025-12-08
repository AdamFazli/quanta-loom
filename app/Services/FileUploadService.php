<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class FileUploadService
{
    /**
     * Upload a file to the specified disk
     *
     * @param UploadedFile $file
     * @param string $disk
     * @param string $directory
     * @param int|null $postingId
     * @return string The path to the stored file
     */
    public function upload(UploadedFile $file, string $disk = 'public', string $directory = 'images', ?int $postingId = null): string
    {
        $filename = time() . '_' . $file->getClientOriginalName();

        if ($postingId) {
            $directory = $directory . '/posting-' . $postingId;
        }

        return Storage::disk($disk)->putFileAs(
            $directory,
            $file,
            $filename
        );
    }

    /**
     * Get the URL for a file
     * For S3, returns a temporary URL. For local/public, returns a permanent URL.
     *
     * @param string $path
     * @param string $disk
     * @param int $expirationMinutes
     * @return string
     */
    public function url(string $path, string $disk = 'public', int $expirationMinutes = 1440): string
    {
        if ($disk === 's3') {
            return Storage::disk($disk)->temporaryUrl($path, now()->addMinutes($expirationMinutes));
        }

        return Storage::disk($disk)->url($path);
    }

    /**
     * Delete a file from storage
     *
     * @param string $path
     * @param string $disk
     * @return bool
     */
    public function delete(string $path, string $disk = 'public'): bool
    {
        return Storage::disk($disk)->delete($path);
    }

    /**
     * Delete multiple files from storage
     *
     * @param array $paths
     * @param string $disk
     * @return bool
     */
    public function deleteMany(array $paths, string $disk = 'public'): bool
    {
        return Storage::disk($disk)->delete($paths);
    }
}
