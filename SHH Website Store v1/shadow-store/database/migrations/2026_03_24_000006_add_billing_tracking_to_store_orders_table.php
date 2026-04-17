<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('store_orders', function (Blueprint $table) {
            $table->timestamp('bill_due_at')->nullable()->after('expires_at');
            $table->timestamp('due_notice_sent_at')->nullable()->after('bill_due_at');
            $table->timestamp('past_due_notice_sent_at')->nullable()->after('due_notice_sent_at');
            $table->timestamp('suspended_for_nonpayment_at')->nullable()->after('past_due_notice_sent_at');
            $table->timestamp('suspended_notice_sent_at')->nullable()->after('suspended_for_nonpayment_at');

            $table->index('bill_due_at');
            $table->index('suspended_for_nonpayment_at');
        });
    }

    public function down(): void
    {
        Schema::table('store_orders', function (Blueprint $table) {
            $table->dropIndex(['bill_due_at']);
            $table->dropIndex(['suspended_for_nonpayment_at']);

            $table->dropColumn([
                'bill_due_at',
                'due_notice_sent_at',
                'past_due_notice_sent_at',
                'suspended_for_nonpayment_at',
                'suspended_notice_sent_at',
            ]);
        });
    }
};
