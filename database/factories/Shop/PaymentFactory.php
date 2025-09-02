<?php

namespace Database\Factories\Shop;

use Akaunting\Money\Currency;
use App\Enums\PaymentMethod;
use App\Enums\PaymentProvider;
use App\Models\Shop\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;

class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition(): array
    {
        return [
            'reference' => 'PAY' . $this->faker->unique()->randomNumber(6),
            'currency' => $this->faker->randomElement(collect(Currency::getCurrencies())->keys()),
            'amount' => $this->faker->randomFloat(2, 100, 2000),
            'provider' => $this->faker->randomElement(PaymentProvider::cases()),
            'method' => $this->faker->randomElement(PaymentMethod::cases()),
            'created_at' => $this->faker->dateTimeBetween('-1 year', '-6 month'),
            'updated_at' => $this->faker->dateTimeBetween('-5 month', 'now'),
        ];
    }
}
