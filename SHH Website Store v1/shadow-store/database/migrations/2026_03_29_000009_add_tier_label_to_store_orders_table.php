<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('store_orders', function (Blueprint $table) {
            if (!Schema::hasColumn('store_orders', 'tier_label')) {
                $table->string('tier_label')->nullable()->after('tier_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('store_orders', function (Blueprint $table) {
            if (Schema::hasColumn('store_orders', 'tier_label')) {
                $table->dropColumn('tier_label');
            }
        });
    }
};
