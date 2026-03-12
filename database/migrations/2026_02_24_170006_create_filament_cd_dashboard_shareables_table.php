<?php

use Filament\CustomDashboardsPlugin\Attributes\CustomDashboardsPluginMigration;
use Filament\CustomDashboardsPlugin\Enums\MigrationName;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new #[CustomDashboardsPluginMigration(MigrationName::DashboardShareablesTable)] class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('filament_cd_dashboard_shareables', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('dashboard_id')->constrained('filament_cd_dashboards')->cascadeOnDelete();
            $table->string('shareable_type');
            $table->string('shareable_id');
            $table->string('role');
            $table->timestamps();

            $table->index(['shareable_type', 'shareable_id'], 'cd_shareables_morph_index');
            $table->unique(['dashboard_id', 'shareable_type', 'shareable_id'], 'cd_shareables_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('filament_cd_dashboard_shareables');
    }
};
