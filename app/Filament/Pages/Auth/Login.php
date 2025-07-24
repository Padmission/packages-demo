<?php

namespace App\Filament\Pages\Auth;

use App\Http\Responses\DemoLoginResponse;
use App\Jobs\ReplenishDemoPool;
use App\Models\User;
use Database\Seeders\DemoSeeder;
use Filament\Auth\Http\Responses\Contracts\LoginResponse;
use Filament\Auth\Pages\Login as BasePage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;

class Login extends BasePage
{
    public function mount(): void
    {
        parent::mount();

        $this->form->fill([
            'email' => config('demo.display_email'),
            'password' => config('demo.password'),
            'remember' => false,
        ]);
    }

    public function authenticate(): ?LoginResponse
    {
        // Check if this is a demo login
        if ($this->data['email'] === config('demo.display_email') &&
            $this->data['password'] === config('demo.password')) {

            return $this->handleDemoLogin();
        }

        return parent::authenticate();
    }

    private function handleDemoLogin(): LoginResponse
    {
        // Check for existing cookie first
        $existingEmail = Cookie::get('demo_user_email');
        if ($existingEmail && $this->tryExistingUser($existingEmail)) {
            return new DemoLoginResponse;
        }

        // Get or create an available demo user
        $demoUser = $this->getOrCreateDemoUser();

        // Login the user
        Auth::login($demoUser);
        $demoUser->update(['email_verified_at' => now()]);

        // Set cookie for session persistence
        Cookie::queue('demo_user_email', $demoUser->email, config('demo.ttl') * 60);

        // Replenish pool in background if needed
        $this->maybeReplenishPool();

        return new DemoLoginResponse;
    }

    private function tryExistingUser(string $email): bool
    {
        $user = User::where('email', $email)
            ->whereNotNull('email_verified_at')
            ->where('email', 'like', 'demo_%@demo.padmission.com')
            ->first();

        if ($user && $user->email_verified_at->addHours(config('demo.ttl'))->isFuture()) {
            Auth::login($user, false);
            return true;
        }

        return false;
    }

    private function getOrCreateDemoUser(): User
    {
        return DB::transaction(function () {
            // Try to get available user
            $user = User::whereNull('email_verified_at')
                ->where('email', 'like', 'demo_%@demo.padmission.com')
                ->lockForUpdate()
                ->first();

            if ($user) {
                return $user;
            }

            // Create one synchronously if none available
            $seeder = new DemoSeeder;
            $seeder->run(1);

            // Get the newly created user
            return User::whereNull('email_verified_at')
                ->where('email', 'like', 'demo_%@demo.padmission.com')
                ->lockForUpdate()
                ->firstOrFail();
        });
    }

    private function maybeReplenishPool(): void
    {
        $available = User::whereNull('email_verified_at')
            ->where('email', 'like', 'demo_%@demo.padmission.com')
            ->count();

        if ($available < 10) {
            ReplenishDemoPool::dispatch(5);
        }
    }
}
