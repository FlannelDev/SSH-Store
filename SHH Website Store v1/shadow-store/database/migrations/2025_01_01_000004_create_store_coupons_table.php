<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('store_coupons', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('description')->nullable();
            $table->string('type')->default('percentage'); // percentage, fixed
            $table->decimal('value', 10, 2); // percentage or fixed amount
            $table->integer('max_uses')->nullable(); // null = unlimited
            $table->integer('uses')->default(0);
            $table->integer('max_uses_per_user')->default(1);
            $table->decimal('min_order', 10, 2)->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('store_coupons');
    }
};
