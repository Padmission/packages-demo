<?php

namespace App\Console\Commands;

use App\Models\Team;
use App\Models\User;
use Database\Seeders\DemoSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DemoRefresh extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'demo:refresh';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Maintain demo system health - cleanup expired data and ensure pool availability';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🔄 Demo system maintenance starting...');

        // Step 1: Release expired sessions
        $this->releaseExpiredSessions();

        // Step 2: Clean up old data
        $this->cleanupOldData();

        // Step 3: Ensure pool is at target size
        $this->maintainPoolSize();

        // Step 4: Show final status
        $this->showStatus();

        $this->info('✅ Demo system maintenance complete!');

        return 0;
    }

    private function releaseExpiredSessions(): void
    {
        $sessionTtl = config('demo.session_ttl', 240); // Minutes, consistent with Login.php

        $released = User::where('email', 'like', 'demo_%@demo.padmission.com')
            ->whereNotNull('email_verified_at')
            ->where('email_verified_at', '<', now()->subMinutes($sessionTtl))
            ->update(['email_verified_at' => null]);

        if ($released > 0) {
            $this->info("→ Released $released expired sessions");
        }
    }

    private function cleanupOldData(): void
    {
        $dataTtl = config('demo.data_ttl', 24);

        DB::transaction(function () use ($dataTtl) {
            // Find demo users that were used but expired long ago
            // We keep unused users (email_verified_at = null) indefinitely in the pool
            $expiredUsers = User::where('email', 'like', 'demo_%@demo.padmission.com')
                ->whereNotNull('email_verified_at') // Was used at some point
                ->where('email_verified_at', '<', now()->subHours($dataTtl)) // But expired long ago
                ->with('teams')
                ->get();

            if ($expiredUsers->isEmpty()) {
                return;
            }

            $deletedCount = 0;

            foreach ($expiredUsers as $user) {
                // Delete each user's teams (this will cascade delete all tenant-scoped data)
                foreach ($user->teams as $team) {
                    $team->delete(); // Cascades to all shop/blog data via foreign key constraints
                }

                // Delete the user
                $user->delete();
                $deletedCount++;
            }

            $this->info("→ Cleaned up $deletedCount expired demo instances and their data");
        });
    }

    private function maintainPoolSize(): void
    {
        $target = config('demo.pool_size', 50);
        $available = $this->getAvailableCount();
        $needed = max(0, $target - $available);

        if ($needed === 0) {
            return;
        }

        $this->info("→ Creating $needed new instances...");

        $seeder = new DemoSeeder;
        $seeder->run((int) $needed);

        $this->info('→ Pool replenished');
    }

    private function showStatus(): void
    {
        $available = $this->getAvailableCount();
        $active = User::where('email', 'like', 'demo_%@demo.padmission.com')
            ->whereNotNull('email_verified_at')
            ->count();
        $target = config('demo.pool_size', 50);

        $this->info('');
        $this->info('📊 Demo System Status');
        $this->info("├─ Available: $available");
        $this->info("├─ Active: $active");
        $this->info("├─ Target: $target");
        $this->info('└─ Total: ' . ($available + $active));
    }

    private function getAvailableCount(): int
    {
        return User::where('email', 'like', 'demo_%@demo.padmission.com')
            ->whereNull('email_verified_at')
            ->count();
    }
}
