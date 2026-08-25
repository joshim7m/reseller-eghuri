<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('reseller_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reseller_order_id')->constrained()->cascadeOnDelete();
            $table->string('product_name');
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->integer('quantity');
            $table->decimal('unit_price', 10, 2);
            $table->decimal('sale_price', 10, 2)->nullable();
            $table->decimal('total', 10, 2);
            $table->foreignId('product_variant_id')->nullable()->constrained()->nullOnDelete();
            $table->string('size')->nullable();
            $table->string('color')->nullable();
            $table->string('purchase_image_path')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reseller_order_items');
    }
};
