<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('store_server_billings', function (Blueprint $table) {
            $table->decimal('billing_amount', 10, 2)->nullable()->after('user_id');
            $table->decimal('node_amount', 10, 2)->nullable()->after('billing_amount');
        });
    }

    public function down(): void
    {
        Schema::table('store_server_billings', function (Blueprint $table) {
            $table->dropColumn(['billing_amount', 'node_amount']);
        });
    }
};
