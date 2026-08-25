<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // transactions - CRITICAL (wallet balance queries)
        Schema::table('transactions', function (Blueprint $table) {
            $table->index(['user_id', 'status', 'type'], 'txn_user_status_type_idx');
        });

        // products - HIGH (public storefront is most traffic-heavy)
        Schema::table('products', function (Blueprint $table) {
            $table->index(['status', 'category_id', 'created_at'], 'prod_status_cat_created_idx');
            $table->index(['status', 'featured', 'created_at'], 'prod_status_featured_idx');
            $table->index(['status', 'sale_price'], 'prod_status_price_idx');
            $table->index('title', 'products_title_idx');
            $table->fullText(['title', 'description'], 'products_title_description_fulltext');
        });

        // users - HIGH (auth + seller/customer management)
        Schema::table('users', function (Blueprint $table) {
            $table->index(['user_type', 'status'], 'users_type_status_idx');
            $table->index(['user_type', 'created_at'], 'users_type_created_idx');
        });

        // product_variants - HIGH (storefront filter options)
        Schema::table('product_variants', function (Blueprint $table) {
            $table->index(['size', 'quantity'], 'pv_size_qty_idx');
            $table->index(['color', 'quantity'], 'pv_color_qty_idx');
        });

        // orders - MEDIUM (payment status filtering)
        Schema::table('orders', function (Blueprint $table) {
            $table->index('payment_status', 'orders_payment_status_idx');
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
            $table->dropIndex('products_title_idx');
            $table->dropIndex('products_title_description_fulltext');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('users_type_status_idx');
            $table->dropIndex('users_type_created_idx');
        });

        Schema::table('product_variants', function (Blueprint $table) {
            $table->dropIndex('pv_size_qty_idx');
            $table->dropIndex('pv_color_qty_idx');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex('orders_payment_status_idx');
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
    }
};
