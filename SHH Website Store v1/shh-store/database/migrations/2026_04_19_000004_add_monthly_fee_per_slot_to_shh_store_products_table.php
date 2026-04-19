<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('shh_store_products')) {
            return;
        }

        if (!Schema::hasColumn('shh_store_products', 'monthly_fee_per_slot')) {
            Schema::table('shh_store_products', function (Blueprint $table) {
                $table->decimal('monthly_fee_per_slot', 8, 2)->nullable()->after('price_monthly');
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('shh_store_products')) {
            return;
        }

        if (Schema::hasColumn('shh_store_products', 'monthly_fee_per_slot')) {
            Schema::table('shh_store_products', function (Blueprint $table) {
                $table->dropColumn('monthly_fee_per_slot');
            });
        }
    }
};
