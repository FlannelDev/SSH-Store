<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('store_payment_receipts', function (Blueprint $table) {
            $table->id();
            $table->string('provider', 32);
            $table->string('external_id')->unique();
            $table->string('type', 64)->nullable();
            $table->json('payload')->nullable();
            $table->timestamp('processed_at');
            $table->timestamps();

            $table->index(['provider', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('store_payment_receipts');
    }
};
