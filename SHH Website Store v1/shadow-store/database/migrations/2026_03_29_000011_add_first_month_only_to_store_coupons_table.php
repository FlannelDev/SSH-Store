<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('store_coupons', function (Blueprint $table) {
            if (!Schema::hasColumn('store_coupons', 'first_month_only')) {
                $table->boolean('first_month_only')->default(true)->after('is_active');
            }
        });
    }

    public function down(): void
    {
        Schema::table('store_coupons', function (Blueprint $table) {
            if (Schema::hasColumn('store_coupons', 'first_month_only')) {
                $table->dropColumn('first_month_only');
            }
        });
    }
};
