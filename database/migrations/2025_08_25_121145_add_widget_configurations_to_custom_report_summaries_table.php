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
        if (Schema::hasTable('custom_report_summaries')) {
            Schema::table('custom_report_summaries', function (Blueprint $table) {
                $table->json('widget_configurations')->nullable();
            });
        }
    }
};
