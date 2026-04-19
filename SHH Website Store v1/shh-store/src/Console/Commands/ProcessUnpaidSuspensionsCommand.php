<?php

namespace ShhStore\Console\Commands;

use Illuminate\Console\Command;
use ShhStore\Models\StoreOrder;

class ProcessUnpaidSuspensionsCommand extends Command
{
    protected $signature = 'shh-store:process-unpaid-suspensions';

    protected $description = 'Auto-suspend linked servers for overdue unpaid store orders.';

    public function handle(): int
    {
        $delayDays = StoreOrder::suspensionDelayDays();
        $now = now();

        $markUnpaidOrders = StoreOrder::query()
            ->whereNotNull('bill_due_at')
            ->where('bill_due_at', '<=', $now)
            ->whereNotIn('status', ['cancelled', 'refunded', 'suspended', 'unpaid'])
            ->get();

        $markedUnpaid = 0;

        foreach ($markUnpaidOrders as $order) {
            if ($order->markUnpaidIfPastDue()) {
                $markedUnpaid++;
            }
        }

        $orders = StoreOrder::query()
            ->with('server')
            ->whereNotNull('server_id')
            ->whereNotNull('bill_due_at')
            ->whereNull('suspended_for_nonpayment_at')
            ->where('status', 'unpaid')
            ->get();

        $processed = 0;

        foreach ($orders as $order) {
            if (!$order->isUnpaidForSuspension($delayDays)) {
                continue;
            }

            if ($order->suspendForNonPayment()) {
                $processed++;
            }
        }

        $this->info("Marked {$markedUnpaid} order(s) as unpaid.");
        $this->info("Processed {$processed} unpaid overdue order(s) for suspension (delay: {$delayDays} day(s)).");

        return self::SUCCESS;
    }
}
