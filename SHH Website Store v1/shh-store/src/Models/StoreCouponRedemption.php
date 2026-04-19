<?php

namespace ShhStore\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StoreCouponRedemption extends Model
{
    protected $table = 'shh_store_coupon_redemptions';

    protected $fillable = [
        'coupon_id',
        'user_id',
        'reference',
    ];

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(StoreCoupon::class, 'coupon_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
