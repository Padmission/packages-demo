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
}
