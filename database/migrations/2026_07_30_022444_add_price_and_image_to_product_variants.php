<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            $table->integer('unit_price')->nullable()->after('sku');
            $table->integer('sale_price')->nullable()->after('unit_price');
            $table->foreignId('product_image_id')->nullable()->constrained('product_images')->nullOnDelete()->after('sale_price');
        });
    }

    public function down(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            $table->dropForeign(['product_image_id']);
            $table->dropColumn(['unit_price', 'sale_price', 'product_image_id']);
        });
    }
};
