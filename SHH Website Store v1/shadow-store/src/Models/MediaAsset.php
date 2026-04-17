<?php

namespace App\Plugins\ShadowStore\Models;

use Illuminate\Database\Eloquent\Model;

class MediaAsset extends Model
{
    protected $table = 'store_media_assets';

    protected $fillable = [
        'name',
        'file_path',
        'alt_text',
    ];

    public function getPublicUrlAttribute(): string
    {
        return asset('storage/' . ltrim((string) $this->file_path, '/'));
    }
}