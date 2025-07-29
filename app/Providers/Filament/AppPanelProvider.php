<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Auth\Login;
use App\Http\Middleware\ApplyTenantScopes;
use App\Http\Middleware\HandleMissingTenant;
use App\Models\Team;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Padmission\DataLens\DataLensPlugin;

class AppPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('app')
            ->path('app')
            ->login(Login::class)
            ->passwordReset()
            ->emailVerification()
            ->tenant(Team::class, ownershipRelationship: 'team')
            ->discoverResources(in: app_path('Filament/App/Resources'), for: 'App\\Filament\\App\\Resources')
            ->discoverPages(in: app_path('Filament/App/Pages'), for: 'App\\Filament\\App\\Pages')
            ->discoverWidgets(in: app_path('Filament/App/Widgets'), for: 'App\\Filament\\App\\Widgets')
            ->discoverClusters(in: app_path('Filament/App/Clusters'), for: 'App\\Filament\\App\\Clusters')
            ->databaseNotifications()
            ->navigationGroups([
                'Shop',
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
                HandleMissingTenant::class,
            ])
            ->tenantMiddleware(
                [
                    ApplyTenantScopes::class,
                ],
                isPersistent: true
            )
            ->plugins([
                DataLensPlugin::make()
//                ->modelDirectories([
//                    'app/Models/Shop',
//                ])
            ])
            ->viteTheme('resources/css/filament/app/theme.css');
    }
}
