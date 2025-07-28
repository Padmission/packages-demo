<?php

namespace App\Jobs;

use App\Models\User;
use Database\Seeders\DemoSeeder;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ReplenishDemoPool implements ShouldQueue
{
    use Queueable;

    /**
     * The number of demo instances to create.
     */
    public int $count;

    /**
     * Create a new job instance.
     */
    public function __construct(int $count = 1)
    {
        $this->count = $count;
        $this->onQueue(config('demo.queue', 'default'));
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Always check current pool size to avoid overshooting target
        $available = User::whereNull('email_verified_at')
            ->where('email', 'like', 'demo_%@demo.padmission.com')
            ->count();

        $target = config('demo.pool_size', 50);
        $needed = max(0, $target - $available);

        // Create only what's actually needed
        $toCreate = min($needed, $this->count);

        if ($toCreate > 0) {
            Log::info("Demo pool replenishment: Creating {$toCreate} demo instances (target: {$target}, available: {$available})");

            $seeder = new DemoSeeder;
            $seeder->run($toCreate);

            Log::info("Demo pool replenishment: Completed. Created {$toCreate} instances.");
        } else {
            Log::info("Demo pool replenishment: Pool is healthy ({$available}/{$target}), no action needed.");
        }
    }
}
