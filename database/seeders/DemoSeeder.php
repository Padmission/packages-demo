<?php

// ABOUTME: Seeder that creates demo users with isolated multi-tenant data
// ABOUTME: Generates complete datasets for shop, blog, and data lens per team

namespace Database\Seeders;

use App\Models\Address;
use App\Models\Blog\Author;
use App\Models\Blog\Category as BlogCategory;
use App\Models\Blog\Link;
use App\Models\Blog\Post;
use App\Models\Comment;
use App\Models\Shop\Brand;
use App\Models\Shop\Category as ShopCategory;
use App\Models\Shop\Customer;
use App\Models\Shop\Order;
use App\Models\Shop\OrderItem;
use App\Models\Shop\Payment;
use App\Models\Shop\Product;
use App\Models\Team;
use App\Models\User;
use Exception;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Padmission\DataLens\Models\CustomReport;

class DemoSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(int $count = 1): void
    {
        $config = config('demo.seed');

        for ($i = 0; $i < $count; $i++) {
            $this->createDemoInstance($config);
        }
    }

    /**
     * Create a single demo instance with user, teams, and data.
     */
    private function createDemoInstance(array $config): void
    {
        DB::transaction(function () use ($config) {
            // Create demo user
            $user = User::factory()->create([
                'name' => 'Demo User',
                'email' => 'demo_' . Str::random(8) . '@demo.padmission.com',
                'password' => bcrypt(config('demo.password')),
                'email_verified_at' => null, // Mark as available
            ]);

            // Create one team with a descriptive name
            $team = Team::factory()->create(['name' => 'Acme Corporation']);

            // Attach user to team
            $user->teams()->attach($team, ['role' => 'owner']);

            // Seed data for the team
            // Don't set tenant context during seeding - we'll pass team_id directly
            $this->seedShopData($team, $config['shop']);
            $this->seedBlogData($team, $config['blog']);
            $this->seedDataLensReports($team, $config['data_lens']);
        });
    }

    /**
     * Seed e-commerce shop data for a team.
     */
    private function seedShopData(Team $team, array $config): void
    {
        // Create brands
        $brands = Brand::factory($config['brands'])->create([
            'team_id' => $team->id,
        ]);

        // Create shop categories
        $categories = ShopCategory::factory($config['categories'])->create([
            'team_id' => $team->id,
        ]);

        // Create products
        $products = Product::factory($config['products'])->create([
            'team_id' => $team->id,
            'shop_brand_id' => fn() => $brands->random()->id,
        ])->each(function ($product) use ($categories) {
            $product->categories()->attach($categories->random(rand(1, 3)));
        });

        // Create customers with addresses
        $customers = Customer::factory($config['customers'])->create([
            'team_id' => $team->id,
        ])->each(function ($customer) use ($team) {
            // Create addresses and attach them to the customer via the pivot table
            $addresses = Address::factory(rand(1, 3))->create([
                'team_id' => $team->id,
            ]);

            // Attach addresses to customer through the polymorphic relationship
            foreach ($addresses as $address) {
                $customer->addresses()->attach($address);
            }
        });

        // Create orders
        Order::factory($config['orders'])->create([
            'team_id' => $team->id,
            'shop_customer_id' => fn() => $customers->random()->id,
        ])->each(function ($order) use ($products, $team) {
            // Create order items
            $orderProducts = $products->random(rand(1, 5));
            foreach ($orderProducts as $product) {
                OrderItem::create([
                    'team_id' => $team->id,
                    'shop_order_id' => $order->id,
                    'shop_product_id' => $product->id,
                    'qty' => rand(1, 3),
                    'unit_price' => $product->price,
                ]);
            }

            // Create payment if order is processed or shipped
            if (in_array($order->status, ['processing', 'shipped', 'delivered'])) {
                Payment::factory()->create([
                    'team_id' => $team->id,
                    'shop_order_id' => $order->id,
                    'amount' => $order->items->sum(fn($item) => $item->qty * $item->unit_price),
                ]);
            }
        });
    }

    /**
     * Seed blog data for a team.
     */
    private function seedBlogData(Team $team, array $config): void
    {
        // Create authors
        $authors = Author::factory($config['authors'])->create([
            'team_id' => $team->id,
        ]);

        // Create blog categories
        $categories = BlogCategory::factory($config['categories'])->create([
            'team_id' => $team->id,
        ]);

        // Create blog posts
        Post::factory($config['posts'])->create([
            'team_id' => $team->id,
            'blog_author_id' => fn() => $authors->random()->id,
            'blog_category_id' => fn() => $categories->random()->id,
        ])->each(function ($post) use ($team, $config) {
            // Create comments
            Comment::factory(rand(0, $config['comments_per_post']))->create([
                'team_id' => $team->id,
                'commentable_type' => Post::class,
                'commentable_id' => $post->id,
            ]);
        });

        // Create some blog links
        Link::factory(10)->create([
            'team_id' => $team->id,
        ]);
    }

    /**
     * Seed Data Lens reports for a team.
     */
    private function seedDataLensReports(Team $team, array $config): void
    {
        $reports = [
            [
                'name' => '📊 Sales Dashboard',
                'model' => Order::class,
                'columns' => [
                    ['name' => 'number', 'label' => 'Order #', 'searchable' => true],
                    ['name' => 'customer.name', 'label' => 'Customer', 'searchable' => true],
                    ['name' => 'total_price', 'label' => 'Total', 'type' => 'money'],
                    ['name' => 'status', 'label' => 'Status'],
                    ['name' => 'created_at', 'label' => 'Date', 'type' => 'datetime'],
                ],
                'filters' => [
                    ['field' => 'created_at', 'operator' => '>=', 'value' => now()->subDays(30)->toDateString()],
                ],
                'sorts' => [
                    ['field' => 'created_at', 'direction' => 'desc'],
                ],
            ],
            [
                'name' => '📈 Customer Analytics',
                'model' => Customer::class,
                'columns' => [
                    ['name' => 'name', 'label' => 'Name', 'searchable' => true],
                    ['name' => 'email', 'label' => 'Email', 'searchable' => true],
                    ['name' => 'phone', 'label' => 'Phone'],
                    ['name' => 'birthday', 'label' => 'Birthday', 'type' => 'date'],
                ],
                'filters' => [],
                'sorts' => [
                    ['field' => 'name', 'direction' => 'asc'],
                ],
            ],
            [
                'name' => '📦 Product Inventory',
                'model' => Product::class,
                'columns' => [
                    ['name' => 'name', 'label' => 'Product', 'searchable' => true],
                    ['name' => 'brand.name', 'label' => 'Brand'],
                    ['name' => 'price', 'label' => 'Price', 'type' => 'money'],
                    ['name' => 'sku', 'label' => 'SKU'],
                    ['name' => 'qty', 'label' => 'Stock'],
                ],
                'filters' => [
                    ['field' => 'qty', 'operator' => '>', 'value' => '0'],
                ],
                'sorts' => [
                    ['field' => 'name', 'direction' => 'asc'],
                ],
            ],
            [
                'name' => '📝 Blog Analytics',
                'model' => Post::class,
                'columns' => [
                    ['name' => 'title', 'label' => 'Title', 'searchable' => true],
                    ['name' => 'author.name', 'label' => 'Author'],
                    ['name' => 'category.name', 'label' => 'Category'],
                    ['name' => 'published_at', 'label' => 'Published', 'type' => 'datetime'],
                ],
                'filters' => [
                    ['field' => 'published_at', 'operator' => 'not_null', 'value' => ''],
                ],
                'sorts' => [
                    ['field' => 'published_at', 'direction' => 'desc'],
                ],
            ],
        ];

        foreach (array_slice($reports, 0, $config['reports']) as $reportData) {
            CustomReport::create([
                'tenant_id' => $team->id,
                'creator_id' => 1, // System user
                'name' => $reportData['name'],
                'data_model' => $reportData['model'],
                'columns' => $reportData['columns'],
                'filters' => $reportData['filters'],
                'settings' => [
                    'api' => [
                        'enabled' => false,
                        'auth_type' => 'api_key',
                    ],
                    'filters' => [
                        'global_logic_operator' => 'or',
                    ],
                ],
            ]);
        }
    }
}
