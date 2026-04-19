<?php

namespace ShhStore\Models;

use App\Models\Node;
use App\Models\Server;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Throwable;

class StoreOrder extends Model
{
    protected $table = 'shh_store_orders';

    protected $fillable = [
        'order_number',
        'user_id',
        'product_id',
        'billing_cycle',
        'slots',
        'amount',
        'currency',
        'status',
        'payment_method',
        'payment_id',
        'transaction_id',
        'customer_email',
        'customer_name',
        'server_id',
        'node_id',
        'meta',
        'paid_at',
        'bill_due_at',
        'suspended_for_nonpayment_at',
        'coupon_code',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'slots' => 'integer',
            'meta' => 'array',
            'paid_at' => 'datetime',
            'bill_due_at' => 'datetime',
            'suspended_for_nonpayment_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (StoreOrder $order): void {
            if (filled($order->user_id)) {
                return;
            }

            $resolvedUserId = $order->resolveLinkedUserId();

            if ($resolvedUserId) {
                $order->user_id = $resolvedUserId;
            }
        });
    }

    protected function resolveLinkedUserId(): ?int
    {
        $normalizedEmail = strtolower(trim((string) $this->customer_email));

        if ($normalizedEmail !== '') {
            return User::query()
                ->whereRaw('LOWER(email) = ?', [$normalizedEmail])
                ->value('id');
        }

        return auth()->check() ? (int) auth()->id() : null;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(StoreProduct::class, 'product_id');
    }

    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class, 'server_id');
    }

    public function node(): BelongsTo
    {
        return $this->belongsTo(Node::class, 'node_id');
    }

    public static function suspensionDelayDays(): int
    {
        return max(0, (int) StoreSetting::getValue('billing_suspend_after_days', (string) config('shh-store.billing.suspend_after_days', 2)));
    }

    public function isUnpaidForSuspension(?int $delayDays = null): bool
    {
        if (!$this->bill_due_at) {
            return false;
        }

        if ($this->status !== 'unpaid') {
            return false;
        }

        if (in_array($this->status, ['cancelled', 'refunded'], true)) {
            return false;
        }

        $days = max(0, $delayDays ?? static::suspensionDelayDays());

        return $this->bill_due_at->lessThanOrEqualTo(now()->subDays($days));
    }

    public function markUnpaidIfPastDue(): bool
    {
        if (!$this->bill_due_at) {
            return false;
        }

        if ($this->bill_due_at->isFuture()) {
            return false;
        }

        if (in_array($this->status, ['cancelled', 'refunded', 'suspended', 'unpaid'], true)) {
            return false;
        }

        $this->update(['status' => 'unpaid']);

        return true;
    }

    public function suspendForNonPayment(bool $force = false): bool
    {
        if (!$this->server_id) {
            return false;
        }

        if (!$force && !$this->isUnpaidForSuspension()) {
            return false;
        }

        $this->loadMissing('server');

        if (!$this->server) {
            return false;
        }

        try {
            if (class_exists(\App\Services\Servers\SuspensionService::class) && class_exists(\App\Enums\SuspendAction::class)) {
                $action = constant(\App\Enums\SuspendAction::class . '::Suspend');

                if (!$this->server->isSuspended()) {
                    app(\App\Services\Servers\SuspensionService::class)->handle($this->server, $action);
                }
            }

            $this->forceFill([
                'status' => 'suspended',
                'suspended_for_nonpayment_at' => now(),
            ])->save();

            return true;
        } catch (Throwable $exception) {
            report($exception);

            return false;
        }
    }

    public function releaseNonPaymentSuspension(): bool
    {
        if (!$this->server_id) {
            return false;
        }

        $this->loadMissing('server');

        if (!$this->server) {
            return false;
        }

        try {
            if (class_exists(\App\Services\Servers\SuspensionService::class) && class_exists(\App\Enums\SuspendAction::class)) {
                $action = constant(\App\Enums\SuspendAction::class . '::Unsuspend');

                if ($this->server->isSuspended()) {
                    app(\App\Services\Servers\SuspensionService::class)->handle($this->server, $action);
                }
            }

            $nextStatus = $this->status === 'suspended' ? 'active' : $this->status;

            $this->forceFill([
                'status' => $nextStatus,
                'suspended_for_nonpayment_at' => null,
            ])->save();

            return true;
        } catch (Throwable $exception) {
            report($exception);

            return false;
        }
    }

    public static function generateOrderNumber(): string
    {
        do {
            $number = 'SHH-' . strtoupper(bin2hex(random_bytes(4)));
        } while (static::where('order_number', $number)->exists());

        return $number;
    }

    public function cancel(): void
    {
        $this->update([
            'status' => 'cancelled',
        ]);
    }

    public function refund(): void
    {
        $this->update([
            'status' => 'refunded',
        ]);
    }
}
