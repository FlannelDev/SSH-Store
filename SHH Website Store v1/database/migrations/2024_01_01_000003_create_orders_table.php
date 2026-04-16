<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('billing_cycle'); // monthly, quarterly, annually
            $table->decimal('amount', 8, 2);
            $table->string('currency', 3)->default('USD');
            $table->string('status')->default('pending'); // pending, paid, cancelled, refunded
            $table->string('payment_method')->nullable(); // stripe, paypal
            $table->string('payment_id')->nullable(); // Stripe session ID or PayPal order ID
            $table->string('transaction_id')->nullable(); // Stripe payment intent or PayPal capture ID
            $table->string('customer_email');
            $table->string('customer_name')->nullable();
            $table->json('meta')->nullable(); // extra payment gateway data
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
