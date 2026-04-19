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

        $orders = StoreOrder::query()
            ->with('server')
            ->whereNotNull('server_id')
            ->whereNotNull('bill_due_at')
            ->whereNull('suspended_for_nonpayment_at')
            ->whereNotIn('status', ['cancelled', 'refunded'])
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

        $this->info("Processed {$processed} overdue order(s) for suspension (delay: {$delayDays} day(s)).");

        return self::SUCCESS;
    }
}
