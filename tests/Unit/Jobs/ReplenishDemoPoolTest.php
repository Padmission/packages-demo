<?php

namespace Tests\Unit\Jobs;

use App\Jobs\ReplenishDemoPool;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class ReplenishDemoPoolTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['demo.enabled' => true]);
        config(['demo.pool_size' => 10]);
    }

    public function test_job_creates_demo_instances_when_needed()
    {
        // Start with empty database
        $this->assertDatabaseCount('users', 0);

        // Run the job
        $job = new ReplenishDemoPool(5);
        $job->handle();

        // Should have created 5 demo users
        $demoUsers = User::where('email', 'like', 'demo_%@demo.padmission.com')->count();
        $this->assertEquals(5, $demoUsers);
    }

    public function test_job_respects_pool_size_limit()
    {
        // Create 8 available demo users
        User::factory(8)->create([
            'email' => fn() => 'demo_' . uniqid() . '@demo.padmission.com',
            'email_verified_at' => null,
        ]);

        // Pool size is 10, so should only create 2 more
        $job = new ReplenishDemoPool(5);
        $job->handle();

        $totalUsers = User::where('email', 'like', 'demo_%@demo.padmission.com')->count();
        $this->assertEquals(10, $totalUsers);
    }

    public function test_job_does_nothing_when_pool_is_full()
    {
        // Create 10 available demo users (pool is full)
        User::factory(10)->create([
            'email' => fn() => 'demo_' . uniqid() . '@demo.padmission.com',
            'email_verified_at' => null,
        ]);

        // Run the job
        $job = new ReplenishDemoPool(5);
        $job->handle();

        // Should still have exactly 10 users
        $totalUsers = User::where('email', 'like', 'demo_%@demo.padmission.com')->count();
        $this->assertEquals(10, $totalUsers);
    }

    public function test_job_only_counts_available_users()
    {
        // Create 5 available and 5 in-use demo users
        User::factory(5)->create([
            'email' => fn() => 'demo_' . uniqid() . '@demo.padmission.com',
            'email_verified_at' => null,
        ]);
        
        User::factory(5)->create([
            'email' => fn() => 'demo_' . uniqid() . '@demo.padmission.com',
            'email_verified_at' => now(), // In use
        ]);

        // Should create 5 more to reach pool size of 10 available
        $job = new ReplenishDemoPool(10);
        $job->handle();

        $availableUsers = User::whereNull('email_verified_at')
            ->where('email', 'like', 'demo_%@demo.padmission.com')
            ->count();
        
        $this->assertEquals(10, $availableUsers);
    }

    public function test_job_does_nothing_when_demo_disabled()
    {
        config(['demo.enabled' => false]);

        $job = new ReplenishDemoPool(5);
        $job->handle();

        $this->assertDatabaseCount('users', 0);
    }

    public function test_job_uses_configured_queue()
    {
        config(['demo.queue' => 'special-queue']);

        $job = new ReplenishDemoPool(5);
        
        $this->assertEquals('special-queue', $job->queue);
    }

    public function test_job_logs_when_creating_instances()
    {
        Log::shouldReceive('info')
            ->once()
            ->with('Demo pool replenishment: Creating 5 demo instances');

        $job = new ReplenishDemoPool(5);
        $job->handle();
    }

    public function test_job_respects_count_parameter()
    {
        // With empty pool, job should create exactly the requested count
        $job = new ReplenishDemoPool(3);
        $job->handle();

        $users = User::where('email', 'like', 'demo_%@demo.padmission.com')->count();
        $this->assertEquals(3, $users);
    }
}