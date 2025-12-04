<?php

namespace App\Models;

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
}
