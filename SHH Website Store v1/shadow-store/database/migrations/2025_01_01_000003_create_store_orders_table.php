<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('store_orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('store_products');
            $table->foreignId('tier_id')->nullable()->constrained('store_product_tiers');
            
            // Order details
            $table->integer('slots')->nullable();
            $table->string('billing_period')->default('monthly');
            $table->decimal('subtotal', 10, 2);
            $table->decimal('tax', 10, 2)->default(0);
            $table->decimal('total', 10, 2);
            $table->string('currency', 3)->default('USD');
            
            // Payment
            $table->string('payment_method')->nullable(); // stripe, paypal
            $table->string('payment_id')->nullable(); // external payment ID
            $table->string('status')->default('pending'); // pending, paid, failed, refunded, cancelled
            
            // Server details (after provisioning)
            $table->unsignedBigInteger('server_id')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->boolean('auto_renew')->default(true);
            
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index('status');
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('store_orders');
    }
};
