<?php

namespace App\Providers;

use App\Policies\CustomReportPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Padmission\DataLens\Models\CustomReport;

class AuthServiceProvider extends ServiceProvider
{
    /** @var array<class-string, class-string> */
    protected $policies = [
        CustomReport::class => CustomReportPolicy::class,
    ];

    public function boot(): void
    {
        //
    }
}
