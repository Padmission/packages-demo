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
        $tables = [
            // Shop domain
            'shop_brands',
            'shop_categories',
            'shop_customers',
            'shop_orders',
            'shop_order_addresses',
            'shop_order_items',
            'shop_payments',
            'shop_products',

            // Blog domain
            'blog_authors',
            'blog_categories',
            'blog_links',
            'blog_posts',

            // Other
            'addresses',
            'comments',
        ];

        foreach ($tables as $table) {
            if (Schema::hasTable($table) && ! Schema::hasColumn($table, 'team_id')) {
                Schema::table($table, function (Blueprint $table) {
                    $table->foreignId('team_id')
                        ->nullable()
                        ->after('id')
                        ->constrained()
                        ->cascadeOnDelete();

                    $table->index('team_id');
                });
            }
        }
    }
};
