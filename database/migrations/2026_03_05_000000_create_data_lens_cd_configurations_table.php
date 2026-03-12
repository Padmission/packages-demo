<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('data_lens_cd_widget_configurations', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('custom_report_id');
            $table->unsignedBigInteger('summary_id')->nullable();
            $table->string('widget_id')->nullable();
            $table->timestamps();

            $table->foreign('custom_report_id')
                ->references('id')
                ->on(config('data-lens.table_names.custom_reports'))
                ->cascadeOnDelete();

            $table->foreign('summary_id')
                ->references('id')
                ->on(config('data-lens.table_names.custom_report_summaries'))
                ->cascadeOnDelete();
        });
    }
};
