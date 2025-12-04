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
}
