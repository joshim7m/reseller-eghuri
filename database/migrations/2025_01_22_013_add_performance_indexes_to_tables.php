<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // transactions - CRITICAL (wallet balance calculations + reseller history)
        Schema::table('transactions', function (Blueprint $table) {
            $table->index(['user_id', 'status', 'type'], 'txn_user_status_type_idx');
        });

        // products - HIGH (storefront is the most traffic-heavy area)
        Schema::table('products', function (Blueprint $table) {
            // category browsing + newest sort
            $table->index(['status', 'category_id', 'created_at'], 'prod_status_cat_created_idx');

            // home "featured" sections
            $table->index(['status', 'featured', 'created_at'], 'prod_status_featured_idx');

            // price range filter + price sort
            $table->index(['status', 'sale_price'], 'prod_status_price_idx');

            // new arrivals / latest + "newest" sort
            $table->index(['status', 'created_at'], 'products_status_created_idx');

            // name sort
            $table->index('title', 'products_title_idx');

            // keyword search on title/description
            $table->fullText(['title', 'description'], 'products_title_description_fulltext');
        });

        // users - HIGH (auth + seller/customer management)
        Schema::table('users', function (Blueprint $table) {
            $table->index(['user_type', 'status'], 'users_type_status_idx');
            $table->index(['user_type', 'created_at'], 'users_type_created_idx');
        });

        // product_variants - HIGH (storefront stock filter)
        Schema::table('product_variants', function (Blueprint $table) {
            $table->index('quantity', 'pv_qty_idx');
        });

        // orders - MEDIUM (admin dashboard + customer profile history)
        Schema::table('orders', function (Blueprint $table) {
            // dashboard sales grouped by status + date range
            $table->index(['status', 'created_at'], 'orders_status_created_idx');

            // customer order history sorted by latest
            $table->index(['user_id', 'created_at'], 'orders_user_created_idx');

            // payment status filtering
            $table->index('payment_status', 'orders_payment_status_idx');
        });

        // reseller_orders - MEDIUM (reseller order list + admin history)
        Schema::table('reseller_orders', function (Blueprint $table) {
            $table->index(['user_id', 'created_at'], 'reseller_orders_user_created_idx');
        });

        // invoices - MEDIUM (payment status filtering)
        Schema::table('invoices', function (Blueprint $table) {
            $table->index('payment_status', 'invoices_payment_status_idx');
        });

        // categories - MEDIUM (admin search and sorting)
        Schema::table('categories', function (Blueprint $table) {
            $table->index('name', 'categories_name_idx');
        });

        // user_details - MEDIUM (admin search by phone)
        Schema::table('user_details', function (Blueprint $table) {
            $table->index('mobile', 'udetails_mobile_idx');
        });

        // product_images - MEDIUM (gallery ordering per product)
        Schema::table('product_images', function (Blueprint $table) {
            $table->index(['product_id', 'sort_order'], 'product_images_product_sort_idx');
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex('txn_user_status_type_idx');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex('prod_status_cat_created_idx');
            $table->dropIndex('prod_status_featured_idx');
            $table->dropIndex('prod_status_price_idx');
            $table->dropIndex('products_status_created_idx');
            $table->dropIndex('products_title_idx');
            $table->dropIndex('products_title_description_fulltext');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('users_type_status_idx');
            $table->dropIndex('users_type_created_idx');
        });

        Schema::table('product_variants', function (Blueprint $table) {
            $table->dropIndex('pv_qty_idx');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex('orders_status_created_idx');
            $table->dropIndex('orders_user_created_idx');
            $table->dropIndex('orders_payment_status_idx');
        });

        Schema::table('reseller_orders', function (Blueprint $table) {
            $table->dropIndex('reseller_orders_user_created_idx');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropIndex('invoices_payment_status_idx');
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->dropIndex('categories_name_idx');
        });

        Schema::table('user_details', function (Blueprint $table) {
            $table->dropIndex('udetails_mobile_idx');
        });

        Schema::table('product_images', function (Blueprint $table) {
            $table->dropIndex('product_images_product_sort_idx');
        });
    }
};
