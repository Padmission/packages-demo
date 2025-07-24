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
        $this->onQueue(config('demo.queue', 'demo'));
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Check current pool size
        $availableCount = User::whereNull('email_verified_at')
            ->where('email', 'like', 'demo_%@demo.padmission.com')
            ->count();

        $targetPoolSize = config('demo.pool_size', 50);
        $needed = max(0, $targetPoolSize - $availableCount);

        if ($needed > 0) {
            Log::info("Demo pool replenishment: Creating {$needed} demo instances");

            // Create the needed demo instances
            $seeder = new DemoSeeder;
            $seeder->run(min($needed, $this->count));
        }
    }
}
