<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Posting extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
    ];

    public function images(): HasMany
    {
        return $this->hasMany(Image::class);
    }

    /**
     * Load posting with images and refresh S3 URLs
     */
    public function loadWithFreshUrls(): self
    {
        $this->load('images');
        Image::refreshUrls($this->images);
        return $this;
    }
}
