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
        'monthly_fee_per_slot',
        'default_slots',
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
            'monthly_fee_per_slot' => 'decimal:2',
            'default_slots' => 'integer',
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
        $baseMonthly = $this->hasPerSlotFee()
            ? (float) $this->monthly_fee_per_slot
            : (float) $this->price_monthly;

        return match ($billingCycle) {
            'quarterly' => (float) ($this->price_quarterly ?: $baseMonthly * 3),
            'annually' => (float) ($this->price_annually ?: $baseMonthly * 12),
            default => $baseMonthly,
        };
    }

    public function hasPerSlotFee(): bool
    {
        return (float) ($this->monthly_fee_per_slot ?? 0) > 0;
    }

    public function getResolvedDefaultSlots(): int
    {
        $slots = (int) ($this->default_slots ?? 0);

        if ($slots <= 0) {
            return 10;
        }

        return min(128, $slots);
    }

    public function cyclePriceSuffix(string $billingCycle = 'monthly'): string
    {
        if ($this->hasPerSlotFee()) {
            return match ($billingCycle) {
                'quarterly' => '/slot/quarter',
                'annually' => '/slot/year',
                default => '/slot/mo',
            };
        }

        return match ($billingCycle) {
            'quarterly' => '/quarter',
            'annually' => '/year',
            default => '/mo',
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
