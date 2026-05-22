<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shop_orders', function (Blueprint $table): void {
            if (! Schema::hasColumn('shop_orders', 'payeeable_type')) {
                $table->nullableMorphs('payeeable');
            }
        });
    }

    public function down(): void
    {
        Schema::table('shop_orders', function (Blueprint $table): void {
            if (Schema::hasColumn('shop_orders', 'payeeable_type')) {
                $table->dropMorphs('payeeable');
            }
        });
    }
};
