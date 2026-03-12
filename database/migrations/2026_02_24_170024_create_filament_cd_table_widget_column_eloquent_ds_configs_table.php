<?php

use Filament\CustomDashboardsPlugin\Attributes\CustomDashboardsPluginMigration;
use Filament\CustomDashboardsPlugin\Enums\MigrationName;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new #[CustomDashboardsPluginMigration(MigrationName::TableWidgetColumnEloquentDsConfigsTable)] class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('filament_cd_table_widget_column_eloquent_ds_configs', function (Blueprint $table): void {
            $table->id();
            $table->string('attribute');
            $table->string('mode')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('filament_cd_table_widget_column_eloquent_ds_configs');
    }
};
