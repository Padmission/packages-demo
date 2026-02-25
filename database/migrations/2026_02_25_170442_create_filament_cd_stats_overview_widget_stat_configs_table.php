<?php

use Filament\CustomDashboardsPlugin\Attributes\CustomDashboardsPluginMigration;
use Filament\CustomDashboardsPlugin\Enums\MigrationName;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new #[CustomDashboardsPluginMigration(MigrationName::StatsOverviewWidgetStatConfigsTable)] class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('filament_cd_stats_overview_widget_stat_configs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('stats_overview_widget_config_id')->constrained('filament_cd_stats_overview_widget_configs', indexName: 'filament_cd_stats_overview_widget_config_id_foreign')->cascadeOnDelete();
            $table->string('label');
            $table->string('data_source');
            $table->nullableMorphs('data_source_config', indexName: 'filament_cd_stats_overview_widget_stats_data_source_config_index');
            $table->unsignedInteger('sort');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('filament_cd_stats_overview_widget_stat_configs');
    }
};
