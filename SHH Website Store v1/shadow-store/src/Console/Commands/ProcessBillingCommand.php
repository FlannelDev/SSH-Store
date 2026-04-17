<?php

namespace App\Plugins\ShadowStore\Console\Commands;

use App\Enums\SuspendAction;
use App\Models\Server;
use App\Models\User;
use App\Plugins\ShadowStore\Models\Order;
use App\Plugins\ShadowStore\Models\ServerBilling;
use App\Plugins\ShadowStore\Notifications\BillingStatusNotification;
use App\Services\Servers\SuspensionService;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema as SchemaFacade;
use Throwable;

class ProcessBillingCommand extends Command
{
    protected $signature = 'shadow-store:process-billing';

    protected $description = 'Send due/past-due billing emails and auto-suspend overdue servers.';

    public function handle(SuspensionService $suspensionService): int
    {
        $now = now();

        $processed = 0;
        $processed += $this->processOrderBilling($now, $suspensionService);
        $processed += $this->processServerBilling($now, $suspensionService);

        $this->info('Processed ' . $processed . ' billing records.');

        return self::SUCCESS;
    }

    protected function processOrderBilling(Carbon $now, SuspensionService $suspensionService): int
    {
        if (!SchemaFacade::hasColumn('store_orders', 'bill_due_at')) {
            return 0;
        }

        $count = 0;

        $orders = Order::query()
            ->with(['user', 'product', 'server'])
            ->whereNotNull('bill_due_at')
            ->whereNotIn('status', ['cancelled', 'refunded', 'failed'])
            ->get();

        foreach ($orders as $order) {
            $count += $this->processBillableRecord(
                billable: $order,
                dueAt: $order->bill_due_at,
                user: $order->user,
                server: $order->server,
                serverName: $order->server?->name ?? ($order->product?->name ?? 'Server Hosting'),
                productName: $order->product?->name,
                orderNumber: $order->order_number,
                noticeState: [
                    'due' => $order->due_notice_sent_at,
                    'past_due' => $order->past_due_notice_sent_at,
                    'suspended' => $order->suspended_notice_sent_at,
                    'suspended_at' => $order->suspended_for_nonpayment_at,
                ],
                persist: function (string $field, ?Carbon $value) use ($order): void {
                    $order->{$field} = $value;
                    $order->save();
                },
                now: $now,
                suspensionService: $suspensionService,
            );
        }

        return $count;
    }

    protected function processServerBilling(Carbon $now, SuspensionService $suspensionService): int
    {
        if (!SchemaFacade::hasTable('store_server_billings')) {
            return 0;
        }

        $count = 0;

        $billings = ServerBilling::query()
            ->with(['user', 'server'])
            ->whereNotNull('bill_due_at')
            ->get();

        foreach ($billings as $billing) {
            $count += $this->processBillableRecord(
                billable: $billing,
                dueAt: $billing->bill_due_at,
                user: $billing->user,
                server: $billing->server,
                serverName: $billing->server?->name ?? 'Server',
                productName: null,
                orderNumber: null,
                noticeState: [
                    'due' => $billing->due_notice_sent_at,
                    'past_due' => $billing->past_due_notice_sent_at,
                    'suspended' => $billing->suspended_notice_sent_at,
                    'suspended_at' => $billing->suspended_for_nonpayment_at,
                ],
                persist: function (string $field, ?Carbon $value) use ($billing): void {
                    $billing->{$field} = $value;
                    $billing->save();
                },
                now: $now,
                suspensionService: $suspensionService,
            );
        }

        return $count;
    }

    protected function processBillableRecord(
        Model $billable,
        ?Carbon $dueAt,
        ?User $user,
        ?Server $server,
        string $serverName,
        ?string $productName,
        ?string $orderNumber,
        array $noticeState,
        callable $persist,
        Carbon $now,
        SuspensionService $suspensionService,
    ): int {
        if (!$user || !$dueAt) {
            return 0;
        }

        $processed = 0;

        if (is_null($noticeState['due']) && $dueAt->isFuture() && $dueAt->lessThanOrEqualTo($now->copy()->addDay())) {
            $this->sendTemplateNotification($user, 'due', $serverName, $productName, $orderNumber, $dueAt);
            $persist('due_notice_sent_at', $now);
            $processed++;
        }

        if (is_null($noticeState['past_due']) && $dueAt->lessThanOrEqualTo($now)) {
            $this->sendTemplateNotification($user, 'past_due', $serverName, $productName, $orderNumber, $dueAt);
            $persist('past_due_notice_sent_at', $now);
            $processed++;
        }

        if (
            $server
            && is_null($noticeState['suspended_at'])
            && $dueAt->lessThanOrEqualTo($now->copy()->subDays(2))
        ) {
            try {
                if (!$server->isSuspended()) {
                    $suspensionService->handle($server, SuspendAction::Suspend);
                }

                $persist('suspended_for_nonpayment_at', $now);

                if (is_null($noticeState['suspended'])) {
                    $this->sendTemplateNotification($user, 'suspended', $serverName, $productName, $orderNumber, $dueAt);
                    $persist('suspended_notice_sent_at', $now);
                }

                $processed++;
            } catch (Throwable $exception) {
                report($exception);
                $this->error('Failed to suspend server ' . $server->id . ': ' . $exception->getMessage());
            }
        }

        return $processed;
    }

    protected function sendTemplateNotification(
        User $user,
        string $type,
        string $serverName,
        ?string $productName,
        ?string $orderNumber,
        Carbon $dueAt,
    ): void {
        $template = config('shadow-store.billing_notifications.templates.' . $type, []);

        $subject = (string) ($template['subject'] ?? 'Billing Update: {server_name}');
        $body = (string) ($template['body'] ?? 'Hello {client_name}, please review billing for {server_name}.');

        $replacements = [
            '{client_name}' => $user->username,
            '{server_name}' => $serverName,
            '{product_name}' => $productName ?? $serverName,
            '{order_number}' => $orderNumber ?? 'N/A',
            '{due_date}' => $dueAt->toDayDateTimeString(),
            '{panel_url}' => url('/'),
        ];

        $user->notify(new BillingStatusNotification(
            subject: strtr($subject, $replacements),
            body: strtr($body, $replacements),
            actionUrl: url('/'),
        ));
    }
}
