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

        if (!Schema::hasColumn('shh_store_orders', 'slots')) {
            Schema::table('shh_store_orders', function (Blueprint $table) {
                $table->unsignedInteger('slots')->nullable()->after('billing_cycle');
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('shh_store_orders')) {
            return;
        }

        if (Schema::hasColumn('shh_store_orders', 'slots')) {
            Schema::table('shh_store_orders', function (Blueprint $table) {
                $table->dropColumn('slots');
            });
        }
    }
};
