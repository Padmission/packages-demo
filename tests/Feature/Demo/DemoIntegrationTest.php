<?php

namespace Tests\Feature\Demo;

use App\Jobs\ReplenishDemoPool;
use App\Models\Shop\Product;
use App\Models\Team;
use App\Models\User;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class DemoIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Enable demo mode
        config(['demo.enabled' => true]);
        config(['demo.pool_size' => 10]);
        config(['demo.session_ttl' => 4]);
        config(['demo.data_ttl' => 24]);
    }

    public function test_complete_demo_flow()
    {
        Queue::fake();

        // 1. Visit login page as new visitor
        $response = $this->get('/admin/login');
        $response->assertStatus(200);

        // 2. Should trigger emergency batch creation
        $demoUsers = User::where('email', 'like', 'demo_%@demo.padmission.com')->get();
        $this->assertGreaterThanOrEqual(5, $demoUsers->count());

        // 3. Get the first available demo user
        $demoUser = $demoUsers->whereNull('email_verified_at')->first();
        $initialEmail = $demoUser->email;

        // 4. Login with demo credentials
        $response = $this->post('/admin/login', [
            'email' => 'demo@padmission.com',
            'password' => 'demo2024',
            'remember' => false,
        ]);

        // 5. Should be redirected to team dashboard
        $response->assertRedirect();
        $this->assertAuthenticatedAs($demoUser);

        // 6. Demo user should be marked as in use
        $demoUser->refresh();
        $this->assertNotNull($demoUser->email_verified_at);

        // 7. Should have dispatched replenishment job
        Queue::assertPushed(ReplenishDemoPool::class);

        // 8. User should have access to both teams
        $teams = $demoUser->teams;
        $this->assertCount(2, $teams);

        // 9. Test tenant switching and data isolation
        $team1 = $teams[0];
        $team2 = $teams[1];

        // Visit team 1
        $response = $this->get("/admin/{$team1->id}");
        $response->assertStatus(200);

        // Create product in team 1
        filament()->setTenant($team1);
        $product1 = Product::factory()->create([
            'name' => 'Team 1 Product',
            'team_id' => $team1->id,
        ]);

        // Switch to team 2
        $response = $this->get("/admin/{$team2->id}");
        $response->assertStatus(200);

        // Verify product from team 1 is not visible
        filament()->setTenant($team2);
        $visibleProducts = Product::where('name', 'Team 1 Product')->get();
        $this->assertCount(0, $visibleProducts);

        // 10. Test logout
        $this->post('/admin/logout');
        $this->assertGuest();
    }

    public function test_demo_refresh_cycle()
    {
        // Create demo users in various states
        $expiredUser = User::factory()->create([
            'email' => 'demo_expired@demo.padmission.com',
            'email_verified_at' => now()->subHours(5),
        ]);

        $activeUser = User::factory()->create([
            'email' => 'demo_active@demo.padmission.com',
            'email_verified_at' => now()->subHours(1),
        ]);

        $oldUnusedUser = User::factory()->create([
            'email' => 'demo_old@demo.padmission.com',
            'email_verified_at' => null,
            'created_at' => now()->subHours(25),
        ]);

        // Run refresh command
        $this->artisan('demo:refresh')
            ->assertSuccessful();

        // Expired user should be released
        $expiredUser->refresh();
        $this->assertNull($expiredUser->email_verified_at);

        // Active user should remain active
        $activeUser->refresh();
        $this->assertNotNull($activeUser->email_verified_at);

        // Old unused user should be deleted
        $this->assertDatabaseMissing('users', ['email' => 'demo_old@demo.padmission.com']);
    }

    public function test_missing_tenant_handling()
    {
        // Create and login as demo user
        $seeder = new DemoSeeder();
        $seeder->run(1);

        $demoUser = User::where('email', 'like', 'demo_%@demo.padmission.com')->first();
        $this->actingAs($demoUser);

        $team = $demoUser->teams->first();

        // Visit valid team URL
        $response = $this->get("/admin/{$team->id}");
        $response->assertStatus(200);

        // Delete the team to simulate expired session
        $team->delete();

        // Try to visit the deleted team URL
        $response = $this->get("/admin/{$team->id}");
        
        // Should redirect to login with message
        $response->assertRedirect('/admin/login');
        $response->assertSessionHas('error', 'Your demo session has expired. Please login again to continue.');
    }

    public function test_concurrent_user_scenario()
    {
        Queue::fake();

        // Create initial pool
        $seeder = new DemoSeeder();
        $seeder->run(3);

        $assignedEmails = [];

        // Simulate 3 concurrent users
        for ($i = 0; $i < 3; $i++) {
            // Clear session between users
            $this->refreshApplication();
            
            // Visit login page
            $this->get('/admin/login');

            // Login
            $response = $this->post('/admin/login', [
                'email' => 'demo@padmission.com',
                'password' => 'demo2024',
                'remember' => false,
            ]);

            $response->assertRedirect();

            // Get authenticated user
            $user = auth()->user();
            $this->assertNotNull($user);
            $this->assertStringContainsString('demo_', $user->email);
            
            $assignedEmails[] = $user->email;

            // Logout for next iteration
            $this->post('/admin/logout');
        }

        // All users should have different emails
        $this->assertCount(3, array_unique($assignedEmails));

        // Should have dispatched replenishment jobs
        Queue::assertPushed(ReplenishDemoPool::class, 3);
    }

    public function test_data_lens_reports_per_team()
    {
        // Skip if Data Lens not available
        if (!class_exists('Padmission\DataLens\Models\CustomReport')) {
            $this->markTestSkipped('Data Lens plugin not available');
        }

        // Create demo instance
        $seeder = new DemoSeeder();
        $seeder->run(1);

        $demoUser = User::where('email', 'like', 'demo_%@demo.padmission.com')->first();
        $teams = $demoUser->teams;

        // Each team should have demo reports
        foreach ($teams as $team) {
            $reports = \Padmission\DataLens\Models\CustomReport::where('team_id', $team->id)->get();
            $this->assertCount(4, $reports);
            
            // Check report names
            $reportNames = $reports->pluck('name')->toArray();
            $this->assertContains('📊 Sales Dashboard', $reportNames);
            $this->assertContains('📈 Customer Analytics', $reportNames);
        }
    }

    public function test_full_demo_lifecycle()
    {
        Queue::fake();

        // 1. Initial state - no demo users
        $this->assertDatabaseCount('users', 0);

        // 2. First visitor triggers emergency batch
        $this->get('/admin/login');
        $this->assertGreaterThan(0, User::where('email', 'like', 'demo_%@demo.padmission.com')->count());

        // 3. Multiple users login
        for ($i = 0; $i < 3; $i++) {
            $this->post('/admin/login', [
                'email' => 'demo@padmission.com',
                'password' => 'demo2024',
            ]);
            $this->post('/admin/logout');
        }

        // 4. Simulate time passing
        User::where('email', 'like', 'demo_%@demo.padmission.com')
            ->whereNotNull('email_verified_at')
            ->update(['email_verified_at' => now()->subHours(5)]);

        // 5. Run refresh to clean up
        $this->artisan('demo:refresh')->assertSuccessful();

        // 6. Users should be released
        $releasedUsers = User::where('email', 'like', 'demo_%@demo.padmission.com')
            ->whereNull('email_verified_at')
            ->count();
        
        $this->assertGreaterThan(0, $releasedUsers);
    }
}