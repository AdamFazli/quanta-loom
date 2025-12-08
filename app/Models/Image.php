<?php

namespace App\Models;

use App\Services\FileUploadService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Image extends Model
{
    use HasFactory;

    protected $fillable = [
        'posting_id',
        'title',
        'description',
        'path',
        'url',
        'original_name',
        'mime_type',
        'size',
    ];

    public function posting(): BelongsTo
    {
        return $this->belongsTo(Posting::class);
    }

    /**
     * Get a fresh S3 URL if the current URL is expired or from S3
     */
    public function getFreshUrlAttribute(): string
    {
        if ($this->path && (str_contains($this->url, 's3.amazonaws.com') || str_contains($this->url, 's3.'))) {
            $fileUploadService = new FileUploadService();
            return $fileUploadService->url($this->path, 's3', 1440); // 24 hours expiration
        }
        return $this->url;
    }

    /**
     * Refresh URLs for a collection of images
     */
    public static function refreshUrls($images): void
    {
        $fileUploadService = new FileUploadService();
        foreach ($images as $image) {
            if ($image->path && (str_contains($image->url, 's3.amazonaws.com') || str_contains($image->url, 's3.'))) {
                $image->url = $fileUploadService->url($image->path, 's3', 1440);
            }
        }
    }
}
