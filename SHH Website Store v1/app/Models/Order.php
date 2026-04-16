<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Order extends Model
{
    protected $fillable = [
        'order_number',
        'user_id',
        'product_id',
        'billing_cycle',
        'amount',
        'currency',
        'status',
        'payment_method',
        'payment_id',
        'transaction_id',
        'customer_email',
        'customer_name',
        'meta',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'meta' => 'array',
            'paid_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public static function generateOrderNumber(): string
    {
        do {
            $number = 'SHH-' . strtoupper(bin2hex(random_bytes(4)));
        } while (static::where('order_number', $number)->exists());

        return $number;
    }
}
