<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('shh_store_orders', 'bill_due_at')) {
            Schema::table('shh_store_orders', function (Blueprint $table) {
                $table->timestamp('bill_due_at')->nullable()->after('paid_at');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('shh_store_orders', 'bill_due_at')) {
            Schema::table('shh_store_orders', function (Blueprint $table) {
                $table->dropColumn('bill_due_at');
            });
        }
    }
};
