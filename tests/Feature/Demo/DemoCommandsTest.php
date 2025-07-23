<?php

namespace Tests\Feature\Demo;

use App\Models\User;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class DemoCommandsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Enable demo mode for tests
        config(['demo.enabled' => true]);
        config(['demo.pool_size' => 10]);
    }

    public function test_demo_add_command_creates_instances()
    {
        $this->artisan('demo:add', ['count' => 3])
            ->expectsOutput('Creating 3 demo instances...')
            ->expectsOutput('Successfully created 3 demo instances!')
            ->assertSuccessful();

        // Check that 3 demo users were created
        $demoUsers = User::where('email', 'like', 'demo_%@demo.padmission.com')->count();
        $this->assertEquals(3, $demoUsers);
    }

    public function test_demo_add_command_with_queue_option()
    {
        Queue::fake();

        $this->artisan('demo:add', ['count' => 5, '--queue' => true])
            ->expectsOutput('Dispatching job to create 5 demo instances...')
            ->expectsOutput('Job dispatched successfully. Check queue worker for progress.')
            ->assertSuccessful();

        // Check that job was dispatched
        Queue::assertPushed(\App\Jobs\ReplenishDemoPool::class, function ($job) {
            return $job->count === 5;
        });
    }

    public function test_demo_add_command_validates_count()
    {
        $this->artisan('demo:add', ['count' => 0])
            ->expectsOutput('Count must be between 1 and 100.')
            ->assertFailed();

        $this->artisan('demo:add', ['count' => 101])
            ->expectsOutput('Count must be between 1 and 100.')
            ->assertFailed();
    }

    public function test_demo_add_fails_when_demo_disabled()
    {
        config(['demo.enabled' => false]);

        $this->artisan('demo:add')
            ->expectsOutput('Demo system is disabled.')
            ->assertFailed();
    }

    public function test_demo_refresh_command_cleans_expired_users()
    {
        // Create some demo users with different states
        User::factory()->create([
            'email' => 'demo_expired@demo.padmission.com',
            'email_verified_at' => now()->subHours(5), // Expired
        ]);

        User::factory()->create([
            'email' => 'demo_active@demo.padmission.com',
            'email_verified_at' => now()->subHours(1), // Still active
        ]);

        User::factory()->create([
            'email' => 'demo_available@demo.padmission.com',
            'email_verified_at' => null, // Available
        ]);

        $this->artisan('demo:refresh')
            ->expectsOutput('Starting demo refresh...')
            ->expectsOutput('Cleaning up expired demo users...')
            ->expectsOutput('Released 1 inactive demo users.')
            ->assertSuccessful();

        // Check that expired user was released
        $expired = User::where('email', 'demo_expired@demo.padmission.com')->first();
        $this->assertNull($expired->email_verified_at);

        // Active user should still be verified
        $active = User::where('email', 'demo_active@demo.padmission.com')->first();
        $this->assertNotNull($active->email_verified_at);
    }

    public function test_demo_refresh_command_deletes_old_unused_users()
    {
        // Create an old unused demo user
        $oldUser = User::factory()->create([
            'email' => 'demo_old@demo.padmission.com',
            'email_verified_at' => null,
            'created_at' => now()->subHours(25), // Older than data_ttl
        ]);

        $this->artisan('demo:refresh')
            ->expectsOutput('Deleted 1 old demo users.')
            ->assertSuccessful();

        // Old user should be deleted
        $this->assertDatabaseMissing('users', ['email' => 'demo_old@demo.padmission.com']);
    }

    public function test_demo_refresh_command_replenishes_pool()
    {
        // Create fewer users than pool size
        User::factory(3)->create([
            'email' => fn() => 'demo_' . uniqid() . '@demo.padmission.com',
            'email_verified_at' => null,
        ]);

        $this->artisan('demo:refresh')
            ->expectsOutput('Checking demo pool size...')
            ->expectsOutput('Need to create 7 demo instances.')
            ->assertSuccessful();

        // Should have created more users to reach pool size
        $totalUsers = User::where('email', 'like', 'demo_%@demo.padmission.com')
            ->whereNull('email_verified_at')
            ->count();
        
        $this->assertEquals(10, $totalUsers); // Pool size is 10 in setUp
    }

    public function test_demo_refresh_force_option()
    {
        // Create some data
        User::factory()->create(['email' => 'test@example.com']);
        
        $this->artisan('demo:refresh', ['--force' => true])
            ->expectsConfirmation('This will delete ALL data and start fresh. Are you sure?', 'yes')
            ->expectsOutput('Performing full database refresh...')
            ->expectsOutput('Database refreshed.')
            ->expectsOutput('Seeding admin user...')
            ->expectsOutput('Creating demo pool...')
            ->assertSuccessful();

        // Should have admin user
        $this->assertDatabaseHas('users', ['email' => 'admin@filamentphp.com']);
        
        // Should have demo pool
        $demoUsers = User::where('email', 'like', 'demo_%@demo.padmission.com')->count();
        $this->assertEquals(10, $demoUsers);
    }

    public function test_demo_refresh_force_skip_seed()
    {
        $this->artisan('demo:refresh', ['--force' => true, '--skip-seed' => true])
            ->expectsConfirmation('This will delete ALL data and start fresh. Are you sure?', 'yes')
            ->expectsOutput('Database refreshed.')
            ->assertSuccessful();

        // Should have no users
        $this->assertDatabaseCount('users', 0);
    }

    public function test_demo_refresh_fails_when_demo_disabled()
    {
        config(['demo.enabled' => false]);

        $this->artisan('demo:refresh')
            ->expectsOutput('Demo system is disabled.')
            ->assertFailed();
    }

    public function test_demo_commands_use_correct_queue()
    {
        Queue::fake();
        config(['demo.queue' => 'special-queue']);

        $this->artisan('demo:add', ['count' => 5, '--queue' => true])
            ->assertSuccessful();

        Queue::assertPushedOn('special-queue', \App\Jobs\ReplenishDemoPool::class);
    }
}