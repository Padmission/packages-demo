<?php

use Filament\CustomDashboardsPlugin\Attributes\CustomDashboardsPluginMigration;
use Filament\CustomDashboardsPlugin\Enums\MigrationName;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new #[CustomDashboardsPluginMigration(MigrationName::BarChartWidgetEloquentDsConfigsTable)] class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('filament_cd_bar_chart_widget_eloquent_ds_configs', function (Blueprint $table): void {
            $table->id();
            $table->string('operator');
            $table->string('dimension_attribute');
            $table->string('dimension_mode')->nullable();
            $table->string('measure_attribute')->nullable();
            $table->jsonb('filters')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('filament_cd_bar_chart_widget_eloquent_ds_configs');
    }
};
