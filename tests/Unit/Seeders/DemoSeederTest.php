<?php

namespace Tests\Unit\Seeders;

use App\Models\Blog\Post;
use App\Models\Shop\Customer;
use App\Models\Shop\Order;
use App\Models\Shop\Product;
use App\Models\Team;
use App\Models\User;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['demo.seed' => [
            'shop' => [
                'brands' => 2,
                'categories' => 2,
                'products' => 5,
                'customers' => 10,
                'orders' => 20,
            ],
            'blog' => [
                'authors' => 2,
                'categories' => 3,
                'posts' => 5,
                'comments_per_post' => 2,
            ],
            'tickets' => [
                'per_customer' => [1, 2],
                'statuses' => 3,
                'priorities' => 3,
                'dispositions' => 3,
            ],
            'data_lens' => [
                'reports' => 4,
            ],
        ]]);
    }

    public function test_seeder_creates_demo_user_with_teams()
    {
        $seeder = new DemoSeeder();
        $seeder->run(1);

        // Should have created one demo user
        $demoUsers = User::where('email', 'like', 'demo_%@demo.padmission.com')->get();
        $this->assertCount(1, $demoUsers);

        $demoUser = $demoUsers->first();
        $this->assertNull($demoUser->email_verified_at);
        $this->assertTrue(password_verify(config('demo.password'), $demoUser->password));

        // Should have 2 teams
        $this->assertCount(2, $demoUser->teams);
        $this->assertEquals('Acme Corporation', $demoUser->teams[0]->name);
        $this->assertEquals('TechStart Inc.', $demoUser->teams[1]->name);
    }

    public function test_seeder_creates_isolated_shop_data_per_team()
    {
        $seeder = new DemoSeeder();
        $seeder->run(1);

        $teams = Team::all();
        $this->assertCount(2, $teams);

        foreach ($teams as $team) {
            // Check shop data for this team
            $teamProducts = Product::where('team_id', $team->id)->count();
            $teamCustomers = Customer::where('team_id', $team->id)->count();
            $teamOrders = Order::where('team_id', $team->id)->count();

            $this->assertEquals(5, $teamProducts);
            $this->assertEquals(10, $teamCustomers);
            $this->assertEquals(20, $teamOrders);
        }
    }

    public function test_seeder_creates_isolated_blog_data_per_team()
    {
        $seeder = new DemoSeeder();
        $seeder->run(1);

        $teams = Team::all();
        
        foreach ($teams as $team) {
            $teamPosts = Post::where('team_id', $team->id)->count();
            $this->assertEquals(5, $teamPosts);
        }
    }

    public function test_seeder_creates_multiple_instances()
    {
        $seeder = new DemoSeeder();
        $seeder->run(3);

        // Should have created 3 demo users
        $demoUsers = User::where('email', 'like', 'demo_%@demo.padmission.com')->count();
        $this->assertEquals(3, $demoUsers);

        // Should have 6 teams total (2 per user)
        $this->assertCount(6, Team::all());
    }

    public function test_seeder_assigns_unique_emails()
    {
        $seeder = new DemoSeeder();
        $seeder->run(5);

        $emails = User::where('email', 'like', 'demo_%@demo.padmission.com')
            ->pluck('email')
            ->toArray();

        // All emails should be unique
        $this->assertCount(5, $emails);
        $this->assertCount(5, array_unique($emails));
    }

    public function test_seeder_creates_addresses_for_customers()
    {
        $seeder = new DemoSeeder();
        $seeder->run(1);

        $team = Team::first();
        $customers = Customer::where('team_id', $team->id)->get();

        foreach ($customers as $customer) {
            $this->assertGreaterThan(0, $customer->addresses->count());
        }
    }

    public function test_seeder_creates_order_items_and_payments()
    {
        $seeder = new DemoSeeder();
        $seeder->run(1);

        $team = Team::first();
        $orders = Order::where('team_id', $team->id)->get();

        foreach ($orders as $order) {
            // Each order should have items
            $this->assertGreaterThan(0, $order->items->count());

            // Orders with certain statuses should have payments
            if (in_array($order->status, ['processing', 'shipped', 'delivered'])) {
                $this->assertNotNull($order->payments->first());
            }
        }
    }

    public function test_seeder_respects_configuration()
    {
        // Override configuration
        config(['demo.seed.shop.products' => 10]);
        config(['demo.seed.shop.customers' => 5]);

        $seeder = new DemoSeeder();
        $seeder->run(1);

        $team = Team::first();
        
        $products = Product::where('team_id', $team->id)->count();
        $customers = Customer::where('team_id', $team->id)->count();

        $this->assertEquals(10, $products);
        $this->assertEquals(5, $customers);
    }

    public function test_seeder_handles_missing_plugin_gracefully()
    {
        // This test ensures the seeder doesn't fail if plugins aren't available
        $seeder = new DemoSeeder();
        
        // Should complete without errors even if Tickets/DataLens plugins aren't installed
        $this->expectNotToPerformAssertions();
        $seeder->run(1);
    }
}