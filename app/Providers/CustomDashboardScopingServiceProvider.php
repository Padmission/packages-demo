<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\Team;
use App\Services\TenantAwareUserDashboardAccess;
use Filament\CustomDashboardsPlugin\Models\Dashboard;
use Filament\CustomDashboardsPlugin\UserDashboardAccess;
use Filament\Facades\Filament;
use Illuminate\Support\ServiceProvider;

class CustomDashboardScopingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->scoped(UserDashboardAccess::class, TenantAwareUserDashboardAccess::class);
    }

    public function boot(): void
    {
        Dashboard::created(function (Dashboard $dashboard): void {
            $tenant = Filament::getTenant();

            if (! $tenant instanceof Team) {
                return;
            }

            $alreadyShared = $dashboard->shareables()
                ->where('shareable_type', $tenant->getMorphClass())
                ->where('shareable_id', $tenant->getKey())
                ->exists();

            if ($alreadyShared) {
                return;
            }

            $dashboard->shareables()->create([
                'shareable_type' => $tenant->getMorphClass(),
                'shareable_id' => $tenant->getKey(),
                'role' => 'owner',
            ]);
        });
    }
}
