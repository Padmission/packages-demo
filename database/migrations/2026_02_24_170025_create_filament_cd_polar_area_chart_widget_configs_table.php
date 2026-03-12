<?php

use Filament\CustomDashboardsPlugin\Attributes\CustomDashboardsPluginMigration;
use Filament\CustomDashboardsPlugin\Enums\MigrationName;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new #[CustomDashboardsPluginMigration(MigrationName::PolarAreaChartWidgetConfigsTable)] class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('filament_cd_polar_area_chart_widget_configs', function (Blueprint $table): void {
            $table->id();
            $table->string('heading');
            $table->string('data_source');
            $table->string('data_label');
            $table->jsonb('slice_colors')->nullable();
            $table->nullableMorphs('data_source_config', indexName: 'filament_cd_polar_area_chart_widgets_data_source_config_index');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('filament_cd_polar_area_chart_widget_configs');
    }
};
