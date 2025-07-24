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
        // Shop Brands
        Schema::table('shop_brands', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->unique(['slug', 'team_id']);
        });

        // Shop Categories
        Schema::table('shop_categories', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->unique(['slug', 'team_id']);
        });

        // Shop Customers
        Schema::table('shop_customers', function (Blueprint $table) {
            $table->dropUnique(['email']);
            $table->unique(['email', 'team_id']);
        });

        // Shop Orders
        Schema::table('shop_orders', function (Blueprint $table) {
            $table->dropUnique(['number']);
            $table->unique(['number', 'team_id']);
        });

        // Shop Products
        Schema::table('shop_products', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->dropUnique(['sku']);
            $table->dropUnique(['barcode']);
            $table->unique(['slug', 'team_id']);
            $table->unique(['sku', 'team_id']);
            $table->unique(['barcode', 'team_id']);
        });

        // Blog Authors
        Schema::table('blog_authors', function (Blueprint $table) {
            $table->dropUnique(['email']);
            $table->unique(['email', 'team_id']);
        });

        // Blog Categories
        Schema::table('blog_categories', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->unique(['slug', 'team_id']);
        });

        // Blog Posts
        Schema::table('blog_posts', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->unique(['slug', 'team_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Shop Brands
        Schema::table('shop_brands', function (Blueprint $table) {
            $table->dropUnique(['slug', 'team_id']);
            $table->unique('slug');
        });

        // Shop Categories
        Schema::table('shop_categories', function (Blueprint $table) {
            $table->dropUnique(['slug', 'team_id']);
            $table->unique('slug');
        });

        // Shop Customers
        Schema::table('shop_customers', function (Blueprint $table) {
            $table->dropUnique(['email', 'team_id']);
            $table->unique('email');
        });

        // Shop Orders
        Schema::table('shop_orders', function (Blueprint $table) {
            $table->dropUnique(['number', 'team_id']);
            $table->unique('number');
        });

        // Shop Products
        Schema::table('shop_products', function (Blueprint $table) {
            $table->dropUnique(['slug', 'team_id']);
            $table->dropUnique(['sku', 'team_id']);
            $table->dropUnique(['barcode', 'team_id']);
            $table->unique('slug');
            $table->unique('sku');
            $table->unique('barcode');
        });

        // Blog Authors
        Schema::table('blog_authors', function (Blueprint $table) {
            $table->dropUnique(['email', 'team_id']);
            $table->unique('email');
        });

        // Blog Categories
        Schema::table('blog_categories', function (Blueprint $table) {
            $table->dropUnique(['slug', 'team_id']);
            $table->unique('slug');
        });

        // Blog Posts
        Schema::table('blog_posts', function (Blueprint $table) {
            $table->dropUnique(['slug', 'team_id']);
            $table->unique('slug');
        });
    }
};
