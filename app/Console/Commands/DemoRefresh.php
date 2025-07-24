<?php

namespace App\Console\Commands;

use App\Jobs\ReplenishDemoPool;
use App\Models\User;
use Database\Seeders\DemoSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

class DemoRefresh extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'demo:refresh
                            {--force : Force a full database refresh}
                            {--skip-seed : Skip seeding after refresh}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Refresh demo data and manage demo user pool';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting demo refresh...');

        // Clean up expired demo users
        $this->cleanupExpiredDemos();

        // Replenish the pool
        $this->replenishDemoPool();

        // Optional: Full refresh
        if ($this->option('force')) {
            if ($this->confirm('This will delete ALL data and start fresh. Are you sure?')) {
                $this->fullRefresh();
            }
        }

        $this->info('Demo refresh completed!');

        return 0;
    }

    /**
     * Clean up expired demo instances.
     */
    private function cleanupExpiredDemos(): void
    {
        $this->info('Cleaning up expired demo users...');

        // Release users inactive for session_ttl hours
        $sessionTtl = config('demo.session_ttl', 4);
        $released = User::where('email', 'like', 'demo_%@demo.padmission.com')
            ->whereNotNull('email_verified_at')
            ->where('email_verified_at', '<', now()->subHours($sessionTtl))
            ->update(['email_verified_at' => null]);

        $this->info("Released {$released} inactive demo users.");

        // Delete old unused demo users (data_ttl hours old)
        $dataTtl = config('demo.data_ttl', 24);
        $deleted = User::where('email', 'like', 'demo_%@demo.padmission.com')
            ->whereNull('email_verified_at')
            ->where('created_at', '<', now()->subHours($dataTtl))
            ->delete();

        $this->info("Deleted {$deleted} old demo users.");
    }

    /**
     * Replenish the demo user pool.
     */
    private function replenishDemoPool(): void
    {
        $this->info('Checking demo pool size...');

        $availableCount = User::whereNull('email_verified_at')
            ->where('email', 'like', 'demo_%@demo.padmission.com')
            ->count();

        $targetPoolSize = config('demo.pool_size', 50);
        $needed = max(0, $targetPoolSize - $availableCount);

        if ($needed > 0) {
            $this->info("Need to create {$needed} demo instances.");

            if ($needed <= 5) {
                // Create synchronously for small numbers
                $this->info('Creating demo instances synchronously...');
                $seeder = new DemoSeeder;
                $seeder->run($needed);
            } else {
                // Dispatch job for large numbers
                $this->info('Dispatching job to create demo instances...');
                ReplenishDemoPool::dispatch($needed);
            }
        } else {
            $this->info("Demo pool is full ({$availableCount}/{$targetPoolSize}).");
        }
    }

    /**
     * Perform a full database refresh.
     */
    private function fullRefresh(): void
    {
        $this->warn('Performing full database refresh...');

        // Run migration fresh
        Artisan::call('migrate:fresh', ['--force' => true]);
        $this->info('Database refreshed.');

        if (! $this->option('skip-seed')) {
            // Seed admin user
            $this->info('Seeding admin user...');
            DB::table('users')->insert([
                'name' => 'Admin',
                'email' => 'admin@filamentphp.com',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Create demo pool
            $this->info('Creating demo pool...');
            $seeder = new DemoSeeder;
            $seeder->run(config('demo.pool_size', 50));
        }
    }
}
