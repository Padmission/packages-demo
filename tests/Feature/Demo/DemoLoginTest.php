<?php

namespace Tests\Feature\Demo;

use App\Models\Team;
use App\Models\User;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoLoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Enable demo mode for tests
        config(['demo.enabled' => true]);
        config(['demo.pool_size' => 5]); // Smaller pool for tests
    }

    public function test_demo_login_page_shows_prefilled_credentials()
    {
        $response = $this->get('/admin/login');

        $response->assertStatus(200);
        $response->assertSee('demo@padmission.com');
        $response->assertSee('demo2024');
    }

    public function test_demo_user_is_assigned_on_login()
    {
        // Create a demo user
        $seeder = new DemoSeeder();
        $seeder->run(1);

        // Get the available demo user
        $demoUser = User::whereNull('email_verified_at')
            ->where('email', 'like', 'demo_%@demo.padmission.com')
            ->first();

        $this->assertNotNull($demoUser);

        // Attempt login with demo credentials
        $response = $this->post('/admin/login', [
            'email' => 'demo@padmission.com',
            'password' => 'demo2024',
            'remember' => false,
        ]);

        // Should redirect to admin panel
        $response->assertRedirect();
        
        // Demo user should now be marked as verified (in use)
        $demoUser->refresh();
        $this->assertNotNull($demoUser->email_verified_at);
        
        // User should be authenticated
        $this->assertAuthenticatedAs($demoUser);
    }

    public function test_demo_user_has_two_teams()
    {
        // Create a demo instance
        $seeder = new DemoSeeder();
        $seeder->run(1);

        $demoUser = User::where('email', 'like', 'demo_%@demo.padmission.com')
            ->with('teams')
            ->first();

        $this->assertCount(2, $demoUser->teams);
        $this->assertEquals('Acme Corporation', $demoUser->teams[0]->name);
        $this->assertEquals('TechStart Inc.', $demoUser->teams[1]->name);
    }

    public function test_emergency_batch_created_when_no_users_available()
    {
        // Ensure no demo users exist
        User::where('email', 'like', 'demo_%@demo.padmission.com')->delete();

        $this->assertDatabaseCount('users', 0);

        // Visit login page - should trigger emergency batch
        $response = $this->get('/admin/login');
        
        $response->assertStatus(200);

        // Should have created emergency batch
        $demoUsers = User::where('email', 'like', 'demo_%@demo.padmission.com')->count();
        $this->assertGreaterThanOrEqual(5, $demoUsers);
    }

    public function test_demo_disabled_shows_admin_credentials()
    {
        config(['demo.enabled' => false]);

        $response = $this->get('/admin/login');

        $response->assertStatus(200);
        $response->assertSee('admin@filamentphp.com');
        $response->assertSee('password');
        $response->assertDontSee('demo@padmission.com');
    }

    public function test_concurrent_demo_users_get_different_accounts()
    {
        // Create multiple demo users
        $seeder = new DemoSeeder();
        $seeder->run(3);

        $emails = [];
        
        // Simulate concurrent requests
        for ($i = 0; $i < 3; $i++) {
            // Get login page to assign user
            $this->get('/admin/login');
            
            // Get the last assigned user
            $assignedUser = User::whereNotNull('email_verified_at')
                ->where('email', 'like', 'demo_%@demo.padmission.com')
                ->latest('email_verified_at')
                ->first();
                
            if ($assignedUser) {
                $emails[] = $assignedUser->email;
            }
        }

        // All emails should be unique
        $this->assertEquals(count($emails), count(array_unique($emails)));
    }
}