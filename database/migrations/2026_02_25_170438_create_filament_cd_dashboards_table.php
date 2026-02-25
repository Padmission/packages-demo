<?php

use Filament\CustomDashboardsPlugin\Attributes\CustomDashboardsPluginMigration;
use Filament\CustomDashboardsPlugin\Enums\MigrationName;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new #[CustomDashboardsPluginMigration(MigrationName::DashboardsTable)] class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('filament_cd_dashboards', function (Blueprint $table): void {
            $table->id();
            $table->string('panel_id');
            $table->string('name')->nullable();
            $table->string('slug')->nullable();
            $table->string('default_role')->nullable();
            $table->string('embedded_component')->nullable();
            $table->string('embedded_position')->nullable();
            $table->boolean('has_navigation_item')->default(false);
            $table->string('navigation_icon')->nullable();
            $table->string('navigation_label')->nullable();
            $table->string('navigation_group')->nullable();
            $table->integer('navigation_sort')->default(999);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('filament_cd_dashboards');
    }
};
