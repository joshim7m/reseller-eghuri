<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // products - HIGH (new arrivals / home / latest sorting by created_at)
        Schema::table('products', function (Blueprint $table) {
            $table->index(['status', 'created_at'], 'products_status_created_idx');
        });

        // orders - HIGH (dashboard sales grouped by status + date range, status lists)
        Schema::table('orders', function (Blueprint $table) {
            $table->index(['status', 'created_at'], 'orders_status_created_idx');
        });

        // orders - MEDIUM (customer profile order history sorted by latest)
        Schema::table('orders', function (Blueprint $table) {
            $table->index(['user_id', 'created_at'], 'orders_user_created_idx');
        });

        // product_images - MEDIUM (gallery ordering per product)
        Schema::table('product_images', function (Blueprint $table) {
            $table->index(['product_id', 'sort_order'], 'product_images_product_sort_idx');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex('products_status_created_idx');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex('orders_status_created_idx');
            $table->dropIndex('orders_user_created_idx');
        });

        Schema::table('product_images', function (Blueprint $table) {
            $table->dropIndex('product_images_product_sort_idx');
        });
    }
};
