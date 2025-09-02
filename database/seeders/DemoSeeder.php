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
use Padmission\DataLens\Models\CustomReportSummary;

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
            if (in_array($order->status->value, ['processing', 'shipped', 'delivered'])) {
                Payment::factory()->create([
                    'team_id' => $team->id,
                    'order_id' => $order->id, // Fixed: was shop_order_id, should be order_id
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
                                        'value' => now()->subMonths(6)->toISOString(),
                                        'date_value' => now()->subMonths(6)->toDateTimeString(),
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
                                        'value' => 2,
                                        'type' => 'aggregate',
                                        'fields' => [],
                                        'settings' => [],
                                        'value_end' => null,
                                        'value_start' => null,
                                        'child_relationship' => null,
                                        'parent_relationship' => null,
                                        'third_level_relationship' => null,
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
                                        'value' => 3,
                                        'type' => 'aggregate',
                                        'fields' => [],
                                        'settings' => [],
                                        'value_end' => null,
                                        'value_start' => null,
                                        'child_relationship' => null,
                                        'parent_relationship' => null,
                                        'third_level_relationship' => null,
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
                                        'value' => 2,
                                    ],
                                ],
                                [
                                    'type' => 'field_expression',
                                    'data' => [
                                        'field' => 'price',
                                        'operator' => 'less_than',
                                        'value' => 500,
                                    ],
                                ],
                            ],
                            'logic_operator' => 'and',
                        ],
                    ],
                ], // Show visible, in-stock products under $500
            ],
            [
                'name' => '💰 Payment Analytics Dashboard',
                'model' => Payment::class,
                'columns' => [
                    ['field' => 'reference', 'label' => 'Payment Ref', 'type' => 'text', 'classification' => 'simple'],
                    ['field' => 'method', 'label' => 'Payment Method', 'type' => 'badge', 'classification' => 'simple'],
                    ['field' => 'provider', 'label' => 'Provider', 'type' => 'text', 'classification' => 'simple'],
                    ['field' => 'amount', 'label' => 'Amount', 'type' => 'money', 'classification' => 'simple'],
                    ['field' => 'currency', 'label' => 'Currency', 'type' => 'text', 'classification' => 'simple'],
                    ['field' => 'created_at', 'label' => 'Payment Date', 'type' => 'datetime', 'classification' => 'simple'],
                    // Customer info through order relationship
                    ['field' => 'name', 'label' => 'Customer', 'type' => 'text', 'relationship' => 'order.customer', 'classification' => 'simple'],
                    ['field' => 'shop_customer_id', 'label' => 'Customer ID', 'type' => 'text', 'relationship' => 'order', 'classification' => 'simple'],
                    ['field' => 'number', 'label' => 'Order #', 'type' => 'text', 'relationship' => 'order', 'classification' => 'simple'],
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
                                        'value' => now()->subMonths(3)->toISOString(),
                                        'date_value' => now()->subMonths(3)->toDateTimeString(),
                                    ],
                                ],
                            ],
                            'logic_operator' => 'and',
                        ],
                    ],
                ], // Recent payments (last 3 months)
                'summaries' => [
                    [
                        'name' => 'Monthly Payment Trends',
                        'configuration' => [
                            'grouping' => [
                                'columns' => [
                                    [
                                        'field_name' => 'created_at',
                                        'group_by' => 'month',
                                        'alias' => 'Payment_Month',
                                        'relationship' => null,
                                    ],
                                ],
                            ],
                            'aggregations' => [
                                [
                                    'id' => 'payment_count',
                                    'source_column' => 'reference',
                                    'function' => 'count',
                                    'label' => 'Payment Count',
                                    'type' => 'number',
                                    'is_reaggregation' => false,
                                    'special_handling' => null,
                                ],
                                [
                                    'id' => 'total_amount',
                                    'source_column' => 'amount',
                                    'function' => 'sum',
                                    'label' => 'Total Revenue',
                                    'type' => 'money',
                                    'is_reaggregation' => false,
                                    'special_handling' => null,
                                ],
                                [
                                    'id' => 'avg_payment_size',
                                    'source_column' => 'amount',
                                    'function' => 'avg',
                                    'label' => 'Avg Payment Size',
                                    'type' => 'money',
                                    'is_reaggregation' => false,
                                    'special_handling' => null,
                                ],
                            ],
                            'search' => [
                                'enabled' => true,
                                'columns' => ['Payment_Month'],
                            ],
                            'processing_hints' => [
                                'prefer_sql' => true,
                                'use_index_hints' => false,
                                'batch_size' => 1000,
                            ],
                        ],
                        'processing_strategy' => 'sql',
                        'cache_enabled' => true,
                        'widgets' => [
                            [
                                'id' => 'payment_trend_chart',
                                'type' => 'chart',
                                'title' => 'Monthly Payment Trends',
                                'placement' => 'report_header',
                                'configuration' => [
                                    'chart_type' => 'line',
                                    'x_axis' => 'Payment_Month',
                                    'y_axis' => 'total_amount',
                                    'secondary_y_axis' => 'payment_count',
                                    'show_legend' => true,
                                    'column_span' => '2'
                                ],
                                'styling' => [
                                    'colorMap' => [
                                        'payment_count' => '#f51717',
                                        'total_amount' => '#2115d4',
                                        'avg_payment_size' => '#d416be'
                                    ],
                                    'theme' => 'light',
                                    'height' => null,
                                    'showBorder' => true,
                                    'showShadow' => true,
                                    'customCss' => []
                                ]
                            ],
                            [
                                'id' => 'payment_stats',
                                'type' => 'stats_overview',
                                'title' => 'Payment Metrics',
                                'placement' => 'report_footer',
                                'configuration' => [
                                    'metrics' => [
                                        ['field' => 'total_amount', 'label' => 'Total Revenue', 'format' => 'currency'],
                                        ['field' => 'payment_count', 'label' => 'Total Payments', 'format' => 'number'],
                                        ['field' => 'avg_payment_size', 'label' => 'Avg Payment', 'format' => 'currency']
                                    ],
                                    'column_span' => 'full'
                                ]
                            ],
                        ],
                    ],
                    [
                        'name' => 'Payment Method Analysis',
                        'configuration' => [
                            'grouping' => [
                                'columns' => [
                                    [
                                        'field_name' => 'method',
                                        'group_by' => null,
                                        'alias' => 'Payment_Method',
                                        'relationship' => null,
                                    ],
                                    [
                                        'field_name' => 'provider',
                                        'group_by' => null,
                                        'alias' => 'Provider',
                                        'relationship' => null,
                                    ],
                                ],
                            ],
                            'aggregations' => [
                                [
                                    'id' => 'method_count',
                                    'source_column' => 'reference',
                                    'function' => 'count',
                                    'label' => 'Transaction Count',
                                    'type' => 'number',
                                    'is_reaggregation' => false,
                                    'special_handling' => null,
                                ],
                                [
                                    'id' => 'method_revenue',
                                    'source_column' => 'amount',
                                    'function' => 'sum',
                                    'label' => 'Method Revenue',
                                    'type' => 'money',
                                    'is_reaggregation' => false,
                                    'special_handling' => null,
                                ],
                                [
                                    'id' => 'method_avg',
                                    'source_column' => 'amount',
                                    'function' => 'avg',
                                    'label' => 'Avg Transaction',
                                    'type' => 'money',
                                    'is_reaggregation' => false,
                                    'special_handling' => null,
                                ],
                            ],
                            'search' => [
                                'enabled' => false,
                            ],
                            'processing_hints' => [
                                'prefer_sql' => false,
                                'use_collection_processing' => true,
                            ],
                        ],
                        'processing_strategy' => 'hybrid',
                        'cache_enabled' => true,
                        'widgets' => [
                            [
                                'id' => 'method_distribution',
                                'type' => 'chart',
                                'title' => 'Payment Method Distribution',
                                'placement' => 'report_header',
                                'configuration' => [
                                    'chart_type' => 'pie',
                                    'data_field' => 'method_revenue',
                                    'label_field' => 'Payment_Method',
                                    'show_percentages' => true,
                                    'column_span' => '2'
                                ],
                                'styling' => [
                                    'colorMap' => [
                                        'method_count' => '#10b981',
                                        'method_revenue' => '#3b82f6',
                                        'method_avg' => '#f59e0b'
                                    ],
                                    'theme' => 'light',
                                    'height' => null,
                                    'showBorder' => true,
                                    'showShadow' => true,
                                    'customCss' => []
                                ]
                            ],
                        ],
                    ],
                    [
                        'name' => 'Business Performance Insights',
                        'configuration' => [
                            'grouping' => [
                                'columns' => [] // No grouping - single row of business metrics
                            ],
                            'aggregations' => [
                                [
                                    'id' => 'total_transactions',
                                    'source_column' => 'amount',
                                    'function' => 'count',
                                    'label' => 'Total Transactions',
                                    'type' => 'number',
                                    'is_reaggregation' => false,
                                    'special_handling' => null,
                                ],
                                [
                                    'id' => 'avg_customer_value',
                                    'source_column' => 'amount',
                                    'function' => 'avg',
                                    'label' => 'Avg Customer Value',
                                    'type' => 'money',
                                    'is_reaggregation' => false,
                                    'special_handling' => null,
                                ],
                                [
                                    'id' => 'highest_payment',
                                    'source_column' => 'amount',
                                    'function' => 'max',
                                    'label' => 'Largest Payment',
                                    'type' => 'money',
                                    'is_reaggregation' => false,
                                    'special_handling' => null,
                                ],
                                [
                                    'id' => 'lowest_payment',
                                    'source_column' => 'amount',
                                    'function' => 'min',
                                    'label' => 'Smallest Payment',
                                    'type' => 'money',
                                    'is_reaggregation' => false,
                                    'special_handling' => null,
                                ],
                            ],
                            'search' => [
                                'enabled' => false,
                            ],
                            'processing_hints' => [
                                'prefer_sql' => true,
                                'use_index_hints' => false,
                                'batch_size' => 1000,
                            ],
                        ],
                        'processing_strategy' => 'sql',
                        'cache_enabled' => true,
                        'widgets' => [
                            [
                                'id' => 'business_insights',
                                'type' => 'stats_overview',
                                'title' => 'Business Performance Insights',
                                'placement' => 'report_footer',
                                'configuration' => [
                                    'metrics' => [
                                        ['field' => 'total_transactions', 'label' => 'Total Transactions', 'format' => 'number'],
                                        ['field' => 'avg_customer_value', 'label' => 'Avg Customer Value', 'format' => 'currency'],
                                        ['field' => 'highest_payment', 'label' => 'Largest Payment', 'format' => 'currency'],
                                        ['field' => 'lowest_payment', 'label' => 'Smallest Payment', 'format' => 'currency'],
                                    ],
                                    'column_span' => 'full',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        foreach ($reports as $reportData) {
            $report = CustomReport::create([
                'team_id' => $team->id,
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

            // Create summaries if they exist
            if (isset($reportData['summaries'])) {
                foreach ($reportData['summaries'] as $summaryData) {
                    CustomReportSummary::create([
                        'custom_report_id' => $report->id,
                        'name' => $summaryData['name'],
                        'configuration' => $summaryData['configuration'],
                        'processing_strategy' => $summaryData['processing_strategy'],
                        'cache_enabled' => $summaryData['cache_enabled'],
                        'widget_configurations' => $summaryData['widgets'] ?? [],
                    ]);
                }
            }
        }
    }
}
