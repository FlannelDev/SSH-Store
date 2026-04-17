<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('store_server_billings', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('server_id')->unique();
            $table->unsignedInteger('user_id');
            $table->timestamp('bill_due_at')->nullable();
            $table->timestamp('due_notice_sent_at')->nullable();
            $table->timestamp('past_due_notice_sent_at')->nullable();
            $table->timestamp('suspended_for_nonpayment_at')->nullable();
            $table->timestamp('suspended_notice_sent_at')->nullable();
            $table->timestamps();

            $table->foreign('server_id')->references('id')->on('servers')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->index('bill_due_at');
            $table->index('suspended_for_nonpayment_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('store_server_billings');
    }
};
