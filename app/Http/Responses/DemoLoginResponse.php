<?php

// ABOUTME: Custom login response that redirects demo users to the app panel
// ABOUTME: Implements Filament's LoginResponse contract to handle tenant-scoped redirects

namespace App\Http\Responses;

use Filament\Http\Responses\Auth\LoginResponse;
use Illuminate\Http\RedirectResponse;
use Livewire\Features\SupportRedirects\Redirector;

class DemoLoginResponse extends LoginResponse
{
    public function toResponse($request): RedirectResponse | Redirector
    {
        return redirect('/');
    }
}
