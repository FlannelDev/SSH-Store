<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('shh_store_orders')) {
            return;
        }

        Schema::table('shh_store_orders', function (Blueprint $table) {
            if (!Schema::hasColumn('shh_store_orders', 'server_id')) {
                $table->foreignId('server_id')->nullable()->after('product_id')->constrained('servers')->nullOnDelete();
            }

            if (!Schema::hasColumn('shh_store_orders', 'node_id')) {
                $table->foreignId('node_id')->nullable()->after('server_id')->constrained('nodes')->nullOnDelete();
            }

            if (!Schema::hasColumn('shh_store_orders', 'suspended_for_nonpayment_at')) {
                $table->timestamp('suspended_for_nonpayment_at')->nullable()->after('bill_due_at');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('shh_store_orders')) {
            return;
        }

        Schema::table('shh_store_orders', function (Blueprint $table) {
            if (Schema::hasColumn('shh_store_orders', 'node_id')) {
                $table->dropForeign(['node_id']);
                $table->dropColumn('node_id');
            }

            if (Schema::hasColumn('shh_store_orders', 'server_id')) {
                $table->dropForeign(['server_id']);
                $table->dropColumn('server_id');
            }

            if (Schema::hasColumn('shh_store_orders', 'suspended_for_nonpayment_at')) {
                $table->dropColumn('suspended_for_nonpayment_at');
            }
        });
    }
};
