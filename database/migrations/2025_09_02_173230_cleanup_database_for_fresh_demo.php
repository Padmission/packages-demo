<?php

use App\Jobs\ReplenishDemoPool;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Clean up all demo data tables
        if (config('database.default') === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
        } else {
            DB::statement('PRAGMA foreign_keys=OFF');
        }

        // Clear all data from main tables
        DB::table('shop_payments')->delete();
        DB::table('shop_order_items')->delete();
        DB::table('shop_orders')->delete();
        DB::table('shop_customers')->delete();
        DB::table('shop_products')->delete();
        DB::table('shop_brands')->delete();
        DB::table('shop_categories')->delete();
        DB::table('comments')->delete();
        DB::table('blog_posts')->delete();
        DB::table('blog_authors')->delete();
        DB::table('blog_categories')->delete();
        DB::table('addresses')->delete();
        DB::table('users')->delete();
        DB::table('teams')->delete();

        // Clear data lens tables
        DB::table('custom_reports')->delete();
        DB::table('custom_report_summaries')->delete();

        if (config('database.default') === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        } else {
            DB::statement('PRAGMA foreign_keys=ON');
        }

        // Populate fresh demo data using the ReplenishDemoPool job
        ReplenishDemoPool::dispatch();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
