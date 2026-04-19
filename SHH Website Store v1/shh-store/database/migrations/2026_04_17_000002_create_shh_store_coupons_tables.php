<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('shh_store_coupons')) {
            Schema::create('shh_store_coupons', function (Blueprint $table) {
                $table->id();
                $table->string('code', 50)->unique();
                $table->string('description')->nullable();
                $table->string('type')->default('percentage');
                $table->decimal('value', 10, 2)->default(0);
                $table->unsignedInteger('max_uses')->nullable();
                $table->unsignedInteger('uses')->default(0);
                $table->unsignedInteger('max_uses_per_user')->default(1);
                $table->decimal('min_order', 10, 2)->nullable();
                $table->timestamp('starts_at')->nullable();
                $table->timestamp('expires_at')->nullable();
                $table->boolean('is_active')->default(true);
                $table->boolean('first_month_only')->default(true);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('shh_store_coupon_redemptions')) {
            Schema::create('shh_store_coupon_redemptions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('coupon_id')->constrained('shh_store_coupons')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('reference')->unique()->nullable();
                $table->timestamps();

                $table->index(['coupon_id', 'user_id']);
            });
        }

        if (!Schema::hasColumn('shh_store_orders', 'coupon_code')) {
            Schema::table('shh_store_orders', function (Blueprint $table) {
                $table->string('coupon_code')->nullable()->after('status');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('shh_store_orders', 'coupon_code')) {
            Schema::table('shh_store_orders', function (Blueprint $table) {
                $table->dropColumn('coupon_code');
            });
        }

        Schema::dropIfExists('shh_store_coupon_redemptions');
        Schema::dropIfExists('shh_store_coupons');
    }
};
