<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('store_orders', function (Blueprint $table) {
            if (!Schema::hasColumn('store_orders', 'coupon_code')) {
                $table->string('coupon_code')->nullable()->after('tier_label');
                $table->index('coupon_code');
            }
        });
    }

    public function down(): void
    {
        Schema::table('store_orders', function (Blueprint $table) {
            if (Schema::hasColumn('store_orders', 'coupon_code')) {
                $table->dropIndex(['coupon_code']);
                $table->dropColumn('coupon_code');
            }
        });
    }
};
