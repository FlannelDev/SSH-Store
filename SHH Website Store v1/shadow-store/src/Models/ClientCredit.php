<?php

namespace App\Plugins\ShadowStore\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClientCredit extends Model
{
    protected $table = 'store_client_credits';

    protected $fillable = [
        'user_id',
        'applied_by',
        'amount',
        'note',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function appliedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'applied_by');
    }

    public static function balanceForUser(int $userId): float
    {
        return (float) static::query()
            ->where('user_id', $userId)
            ->sum('amount');
    }
}
