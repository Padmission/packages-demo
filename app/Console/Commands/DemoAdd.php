<?php

namespace App\Console\Commands;

use App\Jobs\ReplenishDemoPool;
use Database\Seeders\DemoSeeder;
use Illuminate\Console\Command;

class DemoAdd extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'demo:add
                            {count=5 : Number of demo instances to add}
                            {--queue : Add via queue instead of synchronously}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add new demo instances to the pool';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $count = (int) $this->argument('count');

        if ($count < 1 || $count > 100) {
            $this->error('Count must be between 1 and 100.');

            return 1;
        }

        if ($this->option('queue')) {
            $this->info("Dispatching job to create {$count} demo instances...");
            ReplenishDemoPool::dispatch($count);
            $this->info('Job dispatched successfully. Check queue worker for progress.');
        } else {
            $this->info("Creating {$count} demo instances...");

            $this->output->progressStart($count);

            $seeder = new DemoSeeder;
            for ($i = 0; $i < $count; $i++) {
                $seeder->run(1);
                $this->output->progressAdvance();
            }

            $this->output->progressFinish();

            $this->info("Successfully created {$count} demo instances!");
        }

        return 0;
    }
}
