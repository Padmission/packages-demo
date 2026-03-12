<?php

use Filament\CustomDashboardsPlugin\Attributes\CustomDashboardsPluginMigration;
use Filament\CustomDashboardsPlugin\Enums\MigrationName;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new #[CustomDashboardsPluginMigration(MigrationName::StatsOverviewWidgetStatEloquentDsConfigsTable)] class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('filament_cd_stats_overview_widget_stat_eloquent_ds_configs', function (Blueprint $table): void {
            $table->id();
            $table->string('operator');
            $table->string('attribute')->nullable();
            $table->jsonb('filters')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('filament_cd_stats_overview_widget_stat_eloquent_ds_configs');
    }
};
