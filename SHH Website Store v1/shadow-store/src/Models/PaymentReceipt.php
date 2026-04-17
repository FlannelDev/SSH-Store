<?php

namespace App\Plugins\ShadowStore\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentReceipt extends Model
{
    protected $table = 'store_payment_receipts';

    protected $fillable = [
        'provider',
        'external_id',
        'type',
        'payload',
        'processed_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'processed_at' => 'datetime',
    ];
}
