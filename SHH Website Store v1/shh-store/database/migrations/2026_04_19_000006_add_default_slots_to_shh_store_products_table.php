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

        if (!Schema::hasColumn('shh_store_products', 'default_slots')) {
            Schema::table('shh_store_products', function (Blueprint $table) {
                $table->unsignedInteger('default_slots')->nullable()->after('monthly_fee_per_slot');
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('shh_store_products')) {
            return;
        }

        if (Schema::hasColumn('shh_store_products', 'default_slots')) {
            Schema::table('shh_store_products', function (Blueprint $table) {
                $table->dropColumn('default_slots');
            });
        }
    }
};
