<?php

namespace ShhStore\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StoreCategory extends Model
{
    protected $table = 'shh_store_categories';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'icon',
        'sort_order',
        'is_visible',
    ];

    protected function casts(): array
    {
        return [
            'is_visible' => 'boolean',
        ];
    }

    public function products(): HasMany
    {
        return $this->hasMany(StoreProduct::class, 'category_id');
    }

    public function visibleProducts(): HasMany
    {
        return $this->hasMany(StoreProduct::class, 'category_id')->where('is_visible', true)->orderBy('sort_order');
    }
}
