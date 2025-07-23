<?php

namespace Tests\Feature\Demo;

use App\Models\Blog\Post;
use App\Models\Shop\Product;
use App\Models\Team;
use App\Models\User;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MultiTenancyTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Team $team1;
    protected Team $team2;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create user with two teams
        $this->user = User::factory()->create();
        $this->team1 = Team::factory()->create(['name' => 'Team 1']);
        $this->team2 = Team::factory()->create(['name' => 'Team 2']);
        
        $this->user->teams()->attach([$this->team1->id, $this->team2->id]);
    }

    public function test_user_can_access_their_teams()
    {
        $this->assertTrue($this->user->canAccessTenant($this->team1));
        $this->assertTrue($this->user->canAccessTenant($this->team2));
    }

    public function test_user_cannot_access_other_teams()
    {
        $otherTeam = Team::factory()->create(['name' => 'Other Team']);
        
        $this->assertFalse($this->user->canAccessTenant($otherTeam));
    }

    public function test_data_is_isolated_between_teams()
    {
        // Create products for each team
        $this->actingAs($this->user);
        
        // Set team 1 context and create product
        filament()->setTenant($this->team1);
        $product1 = Product::factory()->create([
            'name' => 'Team 1 Product',
            'team_id' => $this->team1->id,
        ]);
        
        // Set team 2 context and create product
        filament()->setTenant($this->team2);
        $product2 = Product::factory()->create([
            'name' => 'Team 2 Product',
            'team_id' => $this->team2->id,
        ]);
        
        // Switch back to team 1 - should only see team 1 product
        filament()->setTenant($this->team1);
        $visibleProducts = Product::all();
        
        $this->assertCount(1, $visibleProducts);
        $this->assertEquals('Team 1 Product', $visibleProducts->first()->name);
        
        // Switch to team 2 - should only see team 2 product
        filament()->setTenant($this->team2);
        $visibleProducts = Product::all();
        
        $this->assertCount(1, $visibleProducts);
        $this->assertEquals('Team 2 Product', $visibleProducts->first()->name);
    }

    public function test_team_scope_filters_data_correctly()
    {
        // Create posts for different teams
        Post::factory()->create([
            'title' => 'Team 1 Post',
            'team_id' => $this->team1->id,
        ]);
        
        Post::factory()->create([
            'title' => 'Team 2 Post', 
            'team_id' => $this->team2->id,
        ]);
        
        Post::factory()->create([
            'title' => 'No Team Post',
            'team_id' => null,
        ]);
        
        // Set tenant and check filtering
        $this->actingAs($this->user);
        filament()->setTenant($this->team1);
        
        $posts = Post::all();
        $this->assertCount(1, $posts);
        $this->assertEquals('Team 1 Post', $posts->first()->title);
    }

    public function test_creating_model_assigns_current_team_id()
    {
        $this->actingAs($this->user);
        filament()->setTenant($this->team1);
        
        // Create product without specifying team_id
        $product = Product::factory()->create([
            'name' => 'Auto Team Product',
            'team_id' => null, // Explicitly set null to test auto-assignment
        ]);
        
        // Should have been assigned to current team
        $this->assertEquals($this->team1->id, $product->fresh()->team_id);
    }

    public function test_demo_seeder_creates_isolated_data()
    {
        // Run demo seeder
        $seeder = new DemoSeeder();
        $seeder->run(1);
        
        // Get the demo user and their teams
        $demoUser = User::where('email', 'like', 'demo_%@demo.padmission.com')
            ->with('teams')
            ->first();
            
        $this->assertCount(2, $demoUser->teams);
        
        $team1 = $demoUser->teams[0];
        $team2 = $demoUser->teams[1];
        
        // Check that each team has its own data
        $this->actingAs($demoUser);
        
        filament()->setTenant($team1);
        $team1Products = Product::count();
        $team1Posts = Post::count();
        
        filament()->setTenant($team2);
        $team2Products = Product::count();
        $team2Posts = Post::count();
        
        // Both teams should have data
        $this->assertGreaterThan(0, $team1Products);
        $this->assertGreaterThan(0, $team1Posts);
        $this->assertGreaterThan(0, $team2Products);
        $this->assertGreaterThan(0, $team2Posts);
        
        // Data counts should be similar (same seeding config)
        $this->assertEquals($team1Products, $team2Products);
        $this->assertEquals($team1Posts, $team2Posts);
    }

    public function test_console_commands_bypass_team_scope()
    {
        // Create data for team
        Product::factory()->create([
            'name' => 'Scoped Product',
            'team_id' => $this->team1->id,
        ]);
        
        // In console, should see all data
        $this->assertTrue(app()->runningInConsole());
        
        $products = Product::all();
        $this->assertCount(1, $products);
    }

    public function test_user_teams_relationship()
    {
        $teams = $this->user->getTenants(filament()->getPanel());
        
        $this->assertCount(2, $teams);
        $this->assertTrue($teams->contains($this->team1));
        $this->assertTrue($teams->contains($this->team2));
    }
}