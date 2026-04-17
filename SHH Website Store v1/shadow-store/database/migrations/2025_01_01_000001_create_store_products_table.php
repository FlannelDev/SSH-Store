<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('store_products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->text('features')->nullable(); // JSON array of features
            $table->string('image')->nullable();
            $table->string('category')->default('game-server'); // game-server, dedicated, vps, other
            $table->string('game')->nullable(); // minecraft, arma-reforger, rust, etc.
            $table->unsignedBigInteger('egg_id')->nullable();
            $table->unsignedBigInteger('nest_id')->nullable();
            $table->unsignedBigInteger('node_id')->nullable();
            
            // Pricing
            $table->string('billing_type')->default('monthly'); // monthly, quarterly, yearly, onetime, slots
            $table->decimal('base_price', 10, 2)->default(0);
            $table->decimal('price_per_slot', 10, 2)->nullable(); // For slot-based pricing
            $table->integer('min_slots')->nullable();
            $table->integer('max_slots')->nullable();
            $table->integer('slot_increment')->default(1);
            
            // Resources (for fixed plans)
            $table->integer('memory')->nullable(); // MB
            $table->integer('disk')->nullable(); // MB
            $table->integer('cpu')->nullable(); // percentage
            $table->integer('databases')->default(0);
            $table->integer('backups')->default(0);
            $table->integer('allocations')->default(1);
            
            // Options
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->integer('sort_order')->default(0);
            $table->integer('stock')->nullable(); // null = unlimited
            
            $table->timestamps();
            
            $table->index('category');
            $table->index('game');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('store_products');
    }
};
