<?php

// ABOUTME: Custom login response that redirects demo users to the app panel
// ABOUTME: Implements Filament's LoginResponse contract to handle tenant-scoped redirects

namespace App\Http\Responses;

use Filament\Auth\Http\Responses\Contracts\LoginResponse;
use Illuminate\Http\RedirectResponse;
use Livewire\Features\SupportRedirects\Redirector;

class DemoLoginResponse implements LoginResponse
{
    public function toResponse($request): RedirectResponse | Redirector
    {
        return redirect('/');
    }
}
