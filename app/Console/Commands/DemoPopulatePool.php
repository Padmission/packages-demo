<?php
// ABOUTME: Command to pre-populate the demo user pool before app launch
// ABOUTME: Creates initial batch of demo users with isolated data for each

namespace App\Console\Commands;

use App\Jobs\ReplenishDemoPool;
use Database\Seeders\DemoSeeder;
use Illuminate\Console\Command;

class DemoPopulatePool extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'demo:populate {count? : Number of demo users to create}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Pre-populate the demo user pool';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if (!config('demo.enabled', true)) {
            $this->error('Demo mode is disabled');
            return Command::FAILURE;
        }

        $count = $this->argument('count') ?? config('demo.pool_size', 50);

        $this->info("Creating {$count} demo users...");

        $bar = $this->output->createProgressBar($count);
        $bar->start();

        $seeder = new DemoSeeder();
        
        for ($i = 0; $i < $count; $i++) {
            $seeder->run(1);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        $this->info('Demo pool populated successfully!');

        return Command::SUCCESS;
    }
}