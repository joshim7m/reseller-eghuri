<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_image_id')->nullable()->constrained('product_images')->nullOnDelete();
            $table->integer('unit_price')->nullable();
            $table->integer('sale_price')->nullable();
            $table->string('sku')->nullable();
            $table->json('options')->nullable();
            $table->integer('quantity')->default(10);
            $table->timestamps();

            $table->index('product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_variants');
    }
};
