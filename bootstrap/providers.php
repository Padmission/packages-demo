<?php

declare(strict_types=1);
use App\Providers\AppServiceProvider;
use App\Providers\Filament\AdminPanelProvider;
use App\Providers\Filament\AppPanelProvider;
use App\Providers\HorizonServiceProvider;

return [
    AppServiceProvider::class,
//    HorizonServiceProvider::class,
    AppPanelProvider::class,
    AdminPanelProvider::class,
];
