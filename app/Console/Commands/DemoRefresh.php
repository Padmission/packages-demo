<?php

namespace App\Console\Commands;

use App\Models\Team;
use App\Models\User;
use Database\Seeders\DemoSeeder;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Padmission\DataLens\Models\CustomReport;

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
    protected $description = 'Maintain demo system health - delete expired instances and ensure pool availability';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🔄 Demo system maintenance starting...');

        // Step 1: Clean up expired instances
        $this->cleanupExpiredInstances();

        // Step 2: Ensure pool is at target size
        $this->maintainPoolSize();

        // Step 3: Show final status
        $this->showStatus();

        $this->info('✅ Demo system maintenance complete!');

        return 0;
    }

    private function cleanupExpiredInstances(): void
    {
        $ttl = config('demo.ttl', 4); // Hours from config

        DB::transaction(function () use ($ttl) {
            // Find all demo users that have expired (used and past TTL)
            $expiredUsers = User::where('email', 'like', 'demo_%@demo.padmission.com')
                ->whereNotNull('email_verified_at') // Was used at some point
                ->where('email_verified_at', '<', now()->subHours($ttl)) // Past TTL
                ->with('teams')
                ->get();

            if ($expiredUsers->isEmpty()) {
                return;
            }

            $deletedCount = 0;
            $deletedTeams = [];

            foreach ($expiredUsers as $user) {
                // Collect team IDs for explicit data cleanup
                foreach ($user->teams as $team) {
                    $deletedTeams[] = $team->id;
                }
                $deletedCount++;
            }

            if (! empty($deletedTeams)) {
                // Filter to only include teams that actually exist
                $existingTeamIds = Team::whereIn('id', $deletedTeams)->pluck('id')->toArray();
                
                if (! empty($existingTeamIds)) {
                    // Explicit cleanup of all tenant-scoped data before deleting teams
                    $this->cleanupTenantData($existingTeamIds);

                    // Delete teams (this will cascade remaining data via foreign key constraints)
                    Team::whereIn('id', $existingTeamIds)->delete();
                }

                // Delete the expired users regardless of team existence
                User::whereIn('id', $expiredUsers->pluck('id'))->delete();
            }

            if ($deletedCount > 0) {
                $this->info("→ Cleaned up $deletedCount expired demo instances");
            }
        });
    }

    private function cleanupTenantData(array $teamIds): void
    {
        // Clean up Data Lens custom reports (might not have proper cascading)
        if (class_exists(CustomReport::class)) {
            try {
                // Use the configured tenant foreign key column name
                $tenantColumn = config('data-lens.column_names.tenant_foreign_key', 'team_id');
                CustomReport::whereIn($tenantColumn, $teamIds)->delete();
            } catch (Exception $e) {
                // Skip if there are issues with the Data Lens tables
                $this->warn('→ Skipped custom reports cleanup: ' . $e->getMessage());
            }
        }

        // Additional explicit cleanup can be added here for other data
        // that might not cascade properly
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
