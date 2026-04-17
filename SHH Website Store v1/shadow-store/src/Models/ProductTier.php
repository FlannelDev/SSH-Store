<?php

namespace App\Plugins\ShadowStore\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductTier extends Model
{
    protected $table = 'store_product_tiers';

    protected $fillable = [
        'product_id', 'name', 'description', 'price', 'billing_period',
        'memory', 'disk', 'cpu', 'databases', 'backups', 'allocations',
        'sort_order', 'is_active', 'is_popular'
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'is_active' => 'boolean',
        'is_popular' => 'boolean',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function getFormattedMemoryAttribute(): string
    {
        if ($this->memory >= 1024) {
            return round($this->memory / 1024, 1) . ' GB';
        }
        return $this->memory . ' MB';
    }

    public function getFormattedDiskAttribute(): string
    {
        if ($this->disk >= 1024) {
            return round($this->disk / 1024, 1) . ' GB';
        }
        return $this->disk . ' MB';
    }
}
