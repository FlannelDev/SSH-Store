<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('store_product_tiers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('store_products')->cascadeOnDelete();
            $table->string('name'); // Basic, Standard, Premium, etc.
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2);
            $table->string('billing_period')->default('monthly'); // monthly, quarterly, yearly
            
            // Resources
            $table->integer('memory'); // MB
            $table->integer('disk'); // MB
            $table->integer('cpu'); // percentage
            $table->integer('databases')->default(0);
            $table->integer('backups')->default(0);
            $table->integer('allocations')->default(1);
            
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_popular')->default(false);
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('store_product_tiers');
    }
};
