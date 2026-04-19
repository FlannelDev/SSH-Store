<?php

namespace ShhStore\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StoreProduct extends Model
{
    protected $table = 'shh_store_products';

    protected $fillable = [
        'category_id',
        'name',
        'slug',
        'description',
        'tier',
        'cpu',
        'ram',
        'storage',
        'price_monthly',
        'price_quarterly',
        'price_annually',
        'is_featured',
        'is_visible',
        'in_stock',
        'sort_order',
        'features',
    ];

    protected function casts(): array
    {
        return [
            'price_monthly' => 'decimal:2',
            'price_quarterly' => 'decimal:2',
            'price_annually' => 'decimal:2',
            'is_featured' => 'boolean',
            'is_visible' => 'boolean',
            'in_stock' => 'boolean',
            'features' => 'array',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(StoreCategory::class, 'category_id');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(StoreOrder::class, 'product_id');
    }

    public function calculatePrice(string $billingCycle = 'monthly'): float
    {
        return match ($billingCycle) {
            'quarterly' => (float) ($this->price_quarterly ?: $this->price_monthly * 3),
            'annually' => (float) ($this->price_annually ?: $this->price_monthly * 12),
            default => (float) $this->price_monthly,
        };
    }

    public function scopeVisible($query)
    {
        return $query->where('is_visible', true);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }
}
