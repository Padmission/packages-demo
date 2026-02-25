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
        Schema::table('migrations', function (Blueprint $table): void {
            $table->string('filament_cd')->nullable()->after('batch')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('migrations', function (Blueprint $table): void {
            $table->dropColumn('filament_cd');
        });
    }
};
