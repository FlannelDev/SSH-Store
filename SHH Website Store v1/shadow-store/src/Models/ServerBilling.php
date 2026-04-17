<?php

namespace App\Plugins\ShadowStore\Models;

use App\Models\Server;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServerBilling extends Model
{
    protected $table = 'store_server_billings';

    protected $fillable = [
        'server_id',
        'user_id',
        'billing_amount',
        'node_amount',
        'bill_due_at',
        'due_notice_sent_at',
        'past_due_notice_sent_at',
        'suspended_for_nonpayment_at',
        'suspended_notice_sent_at',
    ];

    protected $casts = [
        'billing_amount' => 'decimal:2',
        'node_amount' => 'decimal:2',
        'bill_due_at' => 'datetime',
        'due_notice_sent_at' => 'datetime',
        'past_due_notice_sent_at' => 'datetime',
        'suspended_for_nonpayment_at' => 'datetime',
        'suspended_notice_sent_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $billing): void {
            if ($billing->isDirty('bill_due_at')) {
                $billing->due_notice_sent_at = null;
                $billing->past_due_notice_sent_at = null;
                $billing->suspended_for_nonpayment_at = null;
                $billing->suspended_notice_sent_at = null;
            }
        });
    }

    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
