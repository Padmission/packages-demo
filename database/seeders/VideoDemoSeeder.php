<?php

namespace Database\Seeders;

use App\Enums\PaymentMethod;
use App\Enums\PaymentProvider;
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
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Padmission\DataLens\Models\CustomReport;
use Padmission\DataLens\Models\CustomReportSummary;

class VideoDemoSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        DB::transaction(function () {
            $user = User::factory()->create([
                'name' => 'Demo User',
                'email' => 'video-demo@padmission.com',
                'password' => bcrypt(config('demo.password')),
            ]);

            $team = Team::factory()->create(['name' => 'TechFlow Inc']);

            $user->teams()->attach($team, ['role' => 'owner']);

            $this->seedShopData($team);
            $this->seedBlogData($team);
            $this->seedDataLensReports($team, $user);
        });
    }

    private function seedShopData(Team $team): void
    {
        $brandNames = ['Apple', 'Samsung', 'Sony', 'Nike', 'Adidas', 'Dyson', 'Bose', 'Herman Miller'];
        $brands = collect();
        foreach ($brandNames as $name) {
            $brands->push(Brand::factory()->create([
                'team_id' => $team->id,
                'name' => $name,
            ]));
        }

        $categoryNames = ['Electronics', 'Audio', 'Sportswear', 'Home & Office', 'Accessories', 'Premium'];
        $categories = collect();
        foreach ($categoryNames as $name) {
            $categories->push(ShopCategory::factory()->create([
                'team_id' => $team->id,
                'name' => $name,
            ]));
        }

        $products = Product::factory(40)->create([
            'team_id' => $team->id,
            'shop_brand_id' => fn () => $brands->random()->id,
            'price' => fn () => fake()->randomFloat(2, 15, 899),
        ])->each(function ($product) use ($categories) {
            $product->categories()->attach($categories->random(rand(1, 3)));
        });

        $customers = Customer::factory(30)->create([
            'team_id' => $team->id,
        ])->each(function ($customer) use ($team) {
            $addresses = Address::factory(rand(1, 3))->create([
                'team_id' => $team->id,
            ]);

            foreach ($addresses as $address) {
                $customer->addresses()->attach($address);
            }
        });

        $monthlyTargets = [12, 16, 20, 24, 28, 22];
        $allOrders = collect();

        foreach ($monthlyTargets as $index => $count) {
            $monthsAgo = 5 - $index;
            $startOfMonth = now()->subMonths($monthsAgo)->startOfMonth();
            $endOfMonth = now()->subMonths($monthsAgo)->endOfMonth();

            if ($monthsAgo === 0) {
                $endOfMonth = now();
            }

            $orders = Order::factory($count)->create([
                'team_id' => $team->id,
                'shop_customer_id' => fn () => $customers->random()->id,
                'created_at' => fn () => Carbon::createFromTimestamp(
                    rand($startOfMonth->timestamp, $endOfMonth->timestamp)
                ),
            ]);

            $allOrders = $allOrders->merge($orders);
        }

        $repeatCustomers = $customers->random(10);
        foreach ($repeatCustomers as $customer) {
            $extraOrders = Order::factory(rand(2, 4))->create([
                'team_id' => $team->id,
                'shop_customer_id' => $customer->id,
                'created_at' => fn () => Carbon::createFromTimestamp(
                    rand(now()->subMonths(5)->startOfMonth()->timestamp, now()->timestamp)
                ),
            ]);
            $allOrders = $allOrders->merge($extraOrders);
        }

        $paymentMethods = [
            ...array_fill(0, 45, PaymentMethod::CreditCard),
            ...array_fill(0, 30, PaymentMethod::DigitalWallet),
            ...array_fill(0, 15, PaymentMethod::BankTransfer),
            ...array_fill(0, 10, PaymentMethod::Cash),
        ];

        $allOrders->each(function ($order) use ($products, $team, $paymentMethods) {
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

            if (in_array($order->status->value, ['processing', 'shipped', 'delivered'])) {
                Payment::factory()->create([
                    'team_id' => $team->id,
                    'order_id' => $order->id,
                    'amount' => $order->items->sum(fn ($item) => $item->qty * $item->unit_price),
                    'method' => $paymentMethods[array_rand($paymentMethods)],
                    'provider' => fake()->randomElement(PaymentProvider::cases()),
                    'created_at' => $order->created_at,
                ]);
            }
        });
    }

    private function seedBlogData(Team $team): void
    {
        $authors = Author::factory(3)->create([
            'team_id' => $team->id,
        ]);

        $categories = BlogCategory::factory(4)->create([
            'team_id' => $team->id,
        ]);

        $posts = Post::factory(12)->create([
            'team_id' => $team->id,
            'blog_author_id' => fn () => $authors->random()->id,
            'blog_category_id' => fn () => $categories->random()->id,
        ]);

        $posts->each(function ($post) use ($team) {
            Comment::factory(rand(0, 5))->create([
                'team_id' => $team->id,
                'commentable_type' => Post::class,
                'commentable_id' => $post->id,
            ]);
        });

        Link::factory(10)->create([
            'team_id' => $team->id,
        ]);
    }

    private function seedDataLensReports(Team $team, User $user): void
    {
        $reports = [
            [
                'name' => 'Sales Dashboard',
                'model' => Order::class,
                'columns' => [
                    ['field' => 'number', 'label' => 'Order #', 'type' => 'text', 'classification' => 'simple'],
                    ['field' => 'name', 'label' => 'Customer', 'type' => 'text', 'relationship' => 'customer', 'classification' => 'simple'],
                    ['field' => 'status', 'label' => 'Status', 'type' => 'badge', 'classification' => 'simple'],
                    ['field' => 'created_at', 'label' => 'Date', 'type' => 'datetime', 'classification' => 'simple'],
                    ['field' => 'id', 'label' => 'Items Count', 'type' => 'number', 'classification' => 'aggregate', 'aggregate_function' => 'count', 'relationship' => 'items'],
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
                ],
                'summaries' => [
                    [
                        'name' => 'Revenue KPIs',
                        'configuration' => [
                            'grouping' => [
                                'columns' => [],
                            ],
                            'aggregations' => [
                                [
                                    'id' => 'total_orders',
                                    'source_column' => 'id',
                                    'function' => 'count',
                                    'label' => 'Total Orders',
                                    'type' => 'number',
                                    'is_reaggregation' => false,
                                    'special_handling' => null,
                                ],
                                [
                                    'id' => 'total_revenue',
                                    'source_column' => 'amount',
                                    'function' => 'sum',
                                    'label' => 'Total Revenue',
                                    'type' => 'money',
                                    'is_reaggregation' => false,
                                    'special_handling' => null,
                                ],
                                [
                                    'id' => 'avg_order',
                                    'source_column' => 'amount',
                                    'function' => 'avg',
                                    'label' => 'Avg Order Value',
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
                                'id' => 'revenue_kpis',
                                'type' => 'stats_overview',
                                'title' => 'Revenue KPIs',
                                'placement' => 'report_header',
                                'configuration' => [
                                    'metrics' => [
                                        ['field' => 'total_orders', 'label' => 'Total Orders', 'format' => 'number'],
                                        ['field' => 'total_revenue', 'label' => 'Total Revenue', 'format' => 'currency'],
                                        ['field' => 'avg_order', 'label' => 'Avg Order Value', 'format' => 'currency'],
                                    ],
                                    'column_span' => 'full',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            [
                'name' => 'Payment Analytics',
                'model' => Payment::class,
                'columns' => [
                    ['field' => 'reference', 'label' => 'Payment Ref', 'type' => 'text', 'classification' => 'simple'],
                    ['field' => 'method', 'label' => 'Method', 'type' => 'badge', 'classification' => 'simple'],
                    ['field' => 'provider', 'label' => 'Provider', 'type' => 'text', 'classification' => 'simple'],
                    ['field' => 'amount', 'label' => 'Amount', 'type' => 'money', 'classification' => 'simple'],
                    ['field' => 'created_at', 'label' => 'Payment Date', 'type' => 'datetime', 'classification' => 'simple'],
                    ['field' => 'name', 'label' => 'Customer', 'type' => 'text', 'relationship' => 'order.customer', 'classification' => 'simple'],
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
                ],
                'summaries' => [
                    [
                        'name' => 'Payment Method Breakdown',
                        'configuration' => [
                            'grouping' => [
                                'columns' => [
                                    [
                                        'field_name' => 'method',
                                        'group_by' => null,
                                        'alias' => 'Payment_Method',
                                        'relationship' => null,
                                    ],
                                ],
                            ],
                            'aggregations' => [
                                [
                                    'id' => 'method_count',
                                    'source_column' => 'reference',
                                    'function' => 'count',
                                    'label' => 'Transactions',
                                    'type' => 'number',
                                    'is_reaggregation' => false,
                                    'special_handling' => null,
                                ],
                                [
                                    'id' => 'method_total',
                                    'source_column' => 'amount',
                                    'function' => 'sum',
                                    'label' => 'Total',
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
                                'id' => 'method_pie',
                                'type' => 'chart',
                                'title' => 'Payment Method Distribution',
                                'placement' => 'report_header',
                                'configuration' => [
                                    'chart_type' => 'pie',
                                    'data_field' => 'method_total',
                                    'label_field' => 'Payment_Method',
                                    'show_percentages' => true,
                                    'column_span' => 'full',
                                ],
                                'styling' => [
                                    'colorMap' => [
                                        'method_count' => '#10b981',
                                        'method_total' => '#3b82f6',
                                    ],
                                    'theme' => 'light',
                                    'height' => null,
                                    'showBorder' => true,
                                    'showShadow' => true,
                                    'customCss' => [],
                                ],
                            ],
                        ],
                    ],
                    [
                        'name' => 'Monthly Transactions',
                        'configuration' => [
                            'grouping' => [
                                'columns' => [
                                    [
                                        'field_name' => 'created_at',
                                        'group_by' => 'month',
                                        'alias' => 'Month',
                                        'relationship' => null,
                                    ],
                                ],
                            ],
                            'aggregations' => [
                                [
                                    'id' => 'tx_count',
                                    'source_column' => 'reference',
                                    'function' => 'count',
                                    'label' => 'Count',
                                    'type' => 'number',
                                    'is_reaggregation' => false,
                                    'special_handling' => null,
                                ],
                                [
                                    'id' => 'tx_volume',
                                    'source_column' => 'amount',
                                    'function' => 'sum',
                                    'label' => 'Volume',
                                    'type' => 'money',
                                    'is_reaggregation' => false,
                                    'special_handling' => null,
                                ],
                            ],
                            'search' => [
                                'enabled' => true,
                                'columns' => ['Month'],
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
                                'id' => 'tx_trend',
                                'type' => 'chart',
                                'title' => 'Monthly Transaction Volume',
                                'placement' => 'report_header',
                                'configuration' => [
                                    'chart_type' => 'line',
                                    'x_axis' => 'Month',
                                    'y_axis' => 'tx_volume',
                                    'secondary_y_axis' => 'tx_count',
                                    'show_legend' => true,
                                    'column_span' => 'full',
                                ],
                                'styling' => [
                                    'colorMap' => [
                                        'tx_count' => '#f59e0b',
                                        'tx_volume' => '#3b82f6',
                                    ],
                                    'theme' => 'light',
                                    'height' => null,
                                    'showBorder' => true,
                                    'showShadow' => true,
                                    'customCss' => [],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            [
                'name' => 'Customer Analytics',
                'model' => Customer::class,
                'columns' => [
                    ['field' => 'name', 'label' => 'Name', 'type' => 'text', 'classification' => 'simple'],
                    ['field' => 'email', 'label' => 'Email', 'type' => 'text', 'classification' => 'simple'],
                    ['field' => 'created_at', 'label' => 'Member Since', 'type' => 'date', 'classification' => 'simple'],
                    ['field' => 'id', 'label' => 'Total Orders', 'type' => 'number', 'classification' => 'aggregate', 'aggregate_function' => 'count', 'relationship' => 'orders'],
                    ['field' => 'total_price', 'label' => 'Lifetime Value', 'type' => 'money', 'classification' => 'aggregate', 'aggregate_function' => 'sum', 'relationship' => 'orders'],
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
                ],
                'summaries' => [],
            ],
            [
                'name' => 'Product Catalog',
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
                                        'value' => 0,
                                    ],
                                ],
                            ],
                            'logic_operator' => 'and',
                        ],
                    ],
                ],
                'summaries' => [
                    [
                        'name' => 'Brand Price Overview',
                        'configuration' => [
                            'grouping' => [
                                'columns' => [
                                    [
                                        'field_name' => 'name',
                                        'group_by' => null,
                                        'alias' => 'Brand',
                                        'relationship' => 'brand',
                                    ],
                                ],
                            ],
                            'aggregations' => [
                                [
                                    'id' => 'product_count',
                                    'source_column' => 'id',
                                    'function' => 'count',
                                    'label' => 'Products',
                                    'type' => 'number',
                                    'is_reaggregation' => false,
                                    'special_handling' => null,
                                ],
                                [
                                    'id' => 'avg_price',
                                    'source_column' => 'price',
                                    'function' => 'avg',
                                    'label' => 'Avg Price',
                                    'type' => 'money',
                                    'is_reaggregation' => false,
                                    'special_handling' => null,
                                ],
                                [
                                    'id' => 'total_stock',
                                    'source_column' => 'qty',
                                    'function' => 'sum',
                                    'label' => 'Total Stock',
                                    'type' => 'number',
                                    'is_reaggregation' => false,
                                    'special_handling' => null,
                                ],
                            ],
                            'search' => [
                                'enabled' => true,
                                'columns' => ['Brand'],
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
                                'id' => 'brand_bar',
                                'type' => 'chart',
                                'title' => 'Brand Price Comparison',
                                'placement' => 'report_header',
                                'configuration' => [
                                    'chart_type' => 'bar',
                                    'x_axis' => 'Brand',
                                    'y_axis' => 'avg_price',
                                    'show_legend' => true,
                                    'column_span' => 'full',
                                ],
                                'styling' => [
                                    'colorMap' => [
                                        'product_count' => '#10b981',
                                        'avg_price' => '#3b82f6',
                                        'total_stock' => '#f59e0b',
                                    ],
                                    'theme' => 'light',
                                    'height' => null,
                                    'showBorder' => true,
                                    'showShadow' => true,
                                    'customCss' => [],
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
                'creator_id' => $user->id,
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

            if (! empty($reportData['summaries']) && class_exists(CustomReportSummary::class)) {
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
