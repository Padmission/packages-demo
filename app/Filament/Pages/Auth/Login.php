<?php

namespace App\Filament\Pages\Auth;

use App\Http\Responses\DemoLoginResponse;
use App\Jobs\ReplenishDemoPool;
use App\Models\User;
use Filament\Auth\Http\Responses\Contracts\LoginResponse;
use Filament\Auth\Pages\Login as BasePage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class Login extends BasePage
{
    public ?string $assignedEmail = null;

    public function mount(): void
    {
        parent::mount();

        $this->form->fill([
            'email' => config('demo.display_email', 'demo@padmission.com'),
            'password' => config('demo.password', 'demo2024'),
            'remember' => false,
        ]);
    }

    public function authenticate(): ?LoginResponse
    {
        // Check if this is a demo login attempt
        if ($this->data['email'] === config('demo.display_email', 'demo@padmission.com') &&
            $this->data['password'] === config('demo.password', 'demo2024')) {

            Log::info('Demo login: Demo credentials detected, assigning user...');

            // Assign an available demo user
            $this->assignDemoUser();

            if ($this->assignedEmail) {
                // Get the demo user
                $demoUser = User::where('email', $this->assignedEmail)->first();

                // Manually authenticate the user without changing form data
                Auth::login($demoUser, $this->data['remember'] ?? false);

                // Mark user as verified (in use)
                $demoUser->update(['email_verified_at' => now()]);
                Log::info('Demo login: Logged in and marked user as verified', ['email' => $this->assignedEmail]);

                // Return custom response that redirects demo users to the app panel
                return new DemoLoginResponse;
            } else {
                // No demo users available
                $this->addError('email', 'Demo system is preparing. Please try again in a moment.');

                return null;
            }
        }

        // For non-demo logins, use parent authentication
        return parent::authenticate();
    }

    private function assignDemoUser(): void
    {
        Log::info('Demo login: Assigning demo user...');

        DB::transaction(function () {
            // First, check if user has an existing demo user cookie
            $existingDemoEmail = Cookie::get('demo_user_email');

            Log::info('Demo login: Checking for existing demo cookie', [
                'existing_email' => $existingDemoEmail,
                'has_cookie' => ! is_null($existingDemoEmail),
            ]);

            if ($existingDemoEmail) {
                // Try to get this demo user if they're still active
                $existingDemoUser = User::where('email', $existingDemoEmail)
                    ->whereNotNull('email_verified_at')
                    ->where('email', 'like', 'demo_%@demo.padmission.com')
                    ->with('teams')
                    ->first();

                if ($existingDemoUser) {
                    // Check if session hasn't expired
                    $ttl = config('demo.ttl', 4); // Hours from config
                    $expiresAt = $existingDemoUser->email_verified_at->addHours($ttl);

                    if (now()->lessThan($expiresAt)) {
                        $this->assignedEmail = $existingDemoUser->email;
                        Log::info('Demo login: Reassigned existing demo user from cookie', ['email' => $this->assignedEmail]);

                        return;
                    }
                }
            }

            // No existing cookie or expired, assign a new demo user
            $demoUser = $this->getAvailableDemoUser();

            if ($demoUser) {
                $this->assignedEmail = $demoUser->email;
                Log::info('Demo login: Assigned new demo user', ['email' => $this->assignedEmail]);

                // Store in cookie for persistence
                $hours = config('demo.ttl', 4);
                $minutes = $hours * 60; // Convert hours to minutes for cookie
                Cookie::queue('demo_user_email', $demoUser->email, $minutes);

                Log::info('Demo login: Queued cookie for demo user', [
                    'email' => $demoUser->email,
                    'minutes' => $minutes,
                ]);

                // Dispatch a job to replenish pool
                ReplenishDemoPool::dispatch(1);
            } else {
                // No available users, dispatch a job to create more
                Log::warning('No demo users available, dispatching job to create pool');

                // Dispatch job to create more asynchronously
                ReplenishDemoPool::dispatch(config('demo.pool_size', 50));

                // Show a message to the user
                session()->flash('demo_pool_empty', 'Demo system is preparing. Please try again in a moment.');
            }
        });
    }

    private function getAvailableDemoUser(): ?User
    {
        return User::whereNull('email_verified_at')
            ->where('email', 'like', 'demo_%@demo.padmission.com')
            ->with('teams')
            ->lockForUpdate()
            ->first();
    }
}
