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
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
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
            $this->seedDataLensReports($team, $user);
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
            'shop_brand_id' => fn () => $brands->random()->id,
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

        // Create orders with better distribution
        $orders = Order::factory($config['orders'])->create([
            'team_id' => $team->id,
            'shop_customer_id' => fn () => $customers->random()->id,
        ]);

        // Ensure some customers have multiple orders for better filtering
        $repeatCustomers = $customers->random(min(10, $customers->count()));
        foreach ($repeatCustomers as $customer) {
            Order::factory(rand(2, 4))->create([
                'team_id' => $team->id,
                'shop_customer_id' => $customer->id,
            ]);
        }

        // Get all orders (original + repeat customer orders)
        $allOrders = Order::where('team_id', $team->id)->get();

        $allOrders->each(function ($order) use ($products, $team) {
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
                    'amount' => $order->items->sum(fn ($item) => $item->qty * $item->unit_price),
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

        // Create blog posts with better author distribution
        $posts = Post::factory($config['posts'])->create([
            'team_id' => $team->id,
            'blog_author_id' => fn () => $authors->random()->id,
            'blog_category_id' => fn () => $categories->random()->id,
        ]);

        // Ensure some authors have additional posts for filtering
        $productiveAuthors = $authors->random(min(2, $authors->count()));
        foreach ($productiveAuthors as $author) {
            Post::factory(rand(3, 6))->create([
                'team_id' => $team->id,
                'blog_author_id' => $author->id,
                'blog_category_id' => fn () => $categories->random()->id,
            ]);
        }

        // Get all posts and add comments
        $allPosts = Post::where('team_id', $team->id)->get();
        $allPosts->each(function ($post) use ($team, $config) {
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
    private function seedDataLensReports(Team $team, User $user): void
    {
        $reports = [
            [
                'name' => '📊 Sales Dashboard with Analytics',
                'model' => Order::class,
                'columns' => [
                    ['field' => 'number', 'label' => 'Order #', 'type' => 'text', 'classification' => 'simple'],
                    ['field' => 'name', 'label' => 'Customer', 'type' => 'text', 'relationship' => 'customer', 'classification' => 'simple'],
                    ['field' => 'status', 'label' => 'Status', 'type' => 'badge', 'classification' => 'simple'],
                    ['field' => 'created_at', 'label' => 'Date', 'type' => 'datetime', 'classification' => 'simple'],
                    // Aggregate columns
                    ['field' => 'id', 'label' => 'Items Count', 'type' => 'number', 'classification' => 'aggregate', 'aggregate_function' => 'count', 'relationship' => 'items'],
                    ['field' => 'qty', 'label' => 'Total Quantity', 'type' => 'number', 'classification' => 'aggregate', 'aggregate_function' => 'sum', 'relationship' => 'items'],
                    ['field' => 'amount', 'label' => 'Total Paid', 'type' => 'money', 'classification' => 'aggregate', 'aggregate_function' => 'sum', 'relationship' => 'payments'],
                ],
                'filters' => [
                    [
                        'type' => 'filter_group',
                        'data' => [
                            'expressions' => [
                                [
                                    'type' => 'field_expression',
                                    'data' => [
                                        'field' => 'created_at',
                                        'operator' => 'after',
                                        'value' => now()->subMonths(6)->toDateString(),
                                    ],
                                ],
                                [
                                    'type' => 'field_expression',
                                    'data' => [
                                        'field' => 'status',
                                        'operator' => 'in',
                                        'value' => ['processing', 'shipped', 'delivered'],
                                    ],
                                ],
                            ],
                            'logic_operator' => 'and',
                        ],
                    ],
                ], // Recent orders (last 6 months) with progress
            ],
            [
                'name' => '📈 Customer Insights',
                'model' => Customer::class,
                'columns' => [
                    ['field' => 'name', 'label' => 'Name', 'type' => 'text', 'classification' => 'simple'],
                    ['field' => 'email', 'label' => 'Email', 'type' => 'text', 'classification' => 'simple'],
                    ['field' => 'created_at', 'label' => 'Member Since', 'type' => 'date', 'classification' => 'simple'],
                    // Aggregate columns showing customer activity
                    ['field' => 'id', 'label' => 'Total Orders', 'type' => 'number', 'classification' => 'aggregate', 'aggregate_function' => 'count', 'relationship' => 'orders'],
                    ['field' => 'total_price', 'label' => 'Lifetime Value', 'type' => 'money', 'classification' => 'aggregate', 'aggregate_function' => 'sum', 'relationship' => 'orders'],
                    ['field' => 'total_price', 'label' => 'Avg Order Value', 'type' => 'money', 'classification' => 'aggregate', 'aggregate_function' => 'avg', 'relationship' => 'orders'],
                ],
                'filters' => [
                    [
                        'type' => 'filter_group',
                        'data' => [
                            'expressions' => [
                                [
                                    'type' => 'aggregate_expression',
                                    'data' => [
                                        'relationship' => 'orders',
                                        'aggregate_function' => 'count',
                                        'field' => 'id',
                                        'operator' => 'greater_than_or_equal',
                                        'value' => '2',
                                    ],
                                ],
                            ],
                            'logic_operator' => 'and',
                        ],
                    ],
                ], // High-value customers with 2+ orders
            ],
            [
                'name' => '📦 Brand Performance',
                'model' => Brand::class,
                'columns' => [
                    ['field' => 'name', 'label' => 'Brand', 'type' => 'text', 'classification' => 'simple'],
                    ['field' => 'website', 'label' => 'Website', 'type' => 'text', 'classification' => 'simple'],
                    // Aggregate columns for brand analysis
                    ['field' => 'id', 'label' => 'Product Count', 'type' => 'number', 'classification' => 'aggregate', 'aggregate_function' => 'count', 'relationship' => 'products'],
                    ['field' => 'price', 'label' => 'Avg Price', 'type' => 'money', 'classification' => 'aggregate', 'aggregate_function' => 'avg', 'relationship' => 'products'],
                    ['field' => 'price', 'label' => 'Min Price', 'type' => 'money', 'classification' => 'aggregate', 'aggregate_function' => 'min', 'relationship' => 'products'],
                    ['field' => 'price', 'label' => 'Max Price', 'type' => 'money', 'classification' => 'aggregate', 'aggregate_function' => 'max', 'relationship' => 'products'],
                    ['field' => 'qty', 'label' => 'Total Stock', 'type' => 'number', 'classification' => 'aggregate', 'aggregate_function' => 'sum', 'relationship' => 'products'],
                ],
                'filters' => [], // Show all brands - no filter needed
            ],
            [
                'name' => '✍️ Author Performance',
                'model' => Author::class,
                'columns' => [
                    ['field' => 'name', 'label' => 'Author', 'type' => 'text', 'classification' => 'simple'],
                    ['field' => 'email', 'label' => 'Email', 'type' => 'text', 'classification' => 'simple'],
                    // Aggregate columns for author metrics
                    ['field' => 'id', 'label' => 'Posts Count', 'type' => 'number', 'classification' => 'aggregate', 'aggregate_function' => 'count', 'relationship' => 'posts'],
                    ['field' => 'published_at', 'label' => 'Published Posts', 'type' => 'number', 'classification' => 'aggregate', 'aggregate_function' => 'count', 'relationship' => 'posts', 'aggregate_filters' => [
                        [
                            'type' => 'filter_group',
                            'data' => [
                                'expressions' => [
                                    [
                                        'type' => 'field_expression',
                                        'data' => [
                                            'field' => 'published_at',
                                            'operator' => 'is_not_null',
                                            'value' => '',
                                        ],
                                    ],
                                ],
                                'logic_operator' => 'and',
                            ],
                        ],
                    ]],
                ],
                'filters' => [
                    [
                        'type' => 'filter_group',
                        'data' => [
                            'expressions' => [
                                [
                                    'type' => 'aggregate_expression',
                                    'data' => [
                                        'relationship' => 'posts',
                                        'aggregate_function' => 'count',
                                        'field' => 'id',
                                        'operator' => 'greater_than',
                                        'value' => '3',
                                    ],
                                ],
                            ],
                            'logic_operator' => 'and',
                        ],
                    ],
                ], // Active authors with 4+ posts
            ],
            [
                'name' => '🛍️ Product Catalog',
                'model' => Product::class,
                'columns' => [
                    ['field' => 'name', 'label' => 'Product', 'type' => 'text', 'classification' => 'simple'],
                    ['field' => 'sku', 'label' => 'SKU', 'type' => 'text', 'classification' => 'simple'],
                    ['field' => 'name', 'label' => 'Brand', 'type' => 'text', 'relationship' => 'brand', 'classification' => 'simple'],
                    ['field' => 'price', 'label' => 'Price', 'type' => 'money', 'classification' => 'simple'],
                    ['field' => 'qty', 'label' => 'Stock', 'type' => 'number', 'classification' => 'simple'],
                    ['field' => 'is_visible', 'label' => 'Visible', 'type' => 'boolean', 'classification' => 'simple'],
                ],
                'filters' => [
                    [
                        'type' => 'filter_group',
                        'data' => [
                            'expressions' => [
                                [
                                    'type' => 'field_expression',
                                    'data' => [
                                        'field' => 'is_visible',
                                        'operator' => 'is_true',
                                        'value' => '',
                                    ],
                                ],
                                [
                                    'type' => 'field_expression',
                                    'data' => [
                                        'field' => 'qty',
                                        'operator' => 'greater_than',
                                        'value' => '2',
                                    ],
                                ],
                                [
                                    'type' => 'field_expression',
                                    'data' => [
                                        'field' => 'price',
                                        'operator' => 'less_than',
                                        'value' => '500',
                                    ],
                                ],
                            ],
                            'logic_operator' => 'and',
                        ],
                    ],
                ], // Show visible, in-stock products under $500
            ],
        ];

        foreach ($reports as $reportData) {
            CustomReport::create([
                'tenant_id' => $team->id,
                'creator_id' => $user->id, // Demo user
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
