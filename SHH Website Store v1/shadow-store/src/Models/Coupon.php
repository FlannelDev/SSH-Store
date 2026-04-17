<?php

namespace App\Plugins\ShadowStore\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema as SchemaFacade;

class Coupon extends Model
{
    protected $table = 'store_coupons';

    protected $fillable = [
        'code', 'description', 'type', 'value', 'max_uses', 'uses',
        'max_uses_per_user', 'min_order', 'starts_at', 'expires_at', 'is_active',
        'first_month_only',
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'min_order' => 'decimal:2',
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
        'first_month_only' => 'boolean',
    ];

    public function isValid(): bool
    {
        if (!$this->is_active) return false;
        if ($this->max_uses && $this->uses >= $this->max_uses) return false;
        if ($this->starts_at && $this->starts_at->isFuture()) return false;
        if ($this->expires_at && $this->expires_at->isPast()) return false;
        
        return true;
    }

    public function calculateDiscount(float $subtotal): float
    {
        if ($this->type === 'affiliate') {
            return 0;
        }

        if ($this->min_order && $subtotal < $this->min_order) {
            return 0;
        }

        if ($this->type === 'percentage') {
            return $subtotal * ($this->value / 100);
        }

        return min($this->value, $subtotal);
    }

    public function isUsableByUser(int $userId): bool
    {
        if ($this->max_uses_per_user <= 0) {
            return true;
        }

        if (!SchemaFacade::hasTable('store_coupon_redemptions')) {
            // If the table is not available yet, fail open to avoid checkout outage.
            return true;
        }

        $redemptions = CouponRedemption::query()
            ->where('coupon_id', $this->id)
            ->where('user_id', $userId)
            ->count();

        return $redemptions < $this->max_uses_per_user;
    }
}
