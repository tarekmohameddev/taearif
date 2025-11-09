<?php

namespace Database\Factories;

use App\Domain\Billing\Models\Invoice;
use App\Domain\User\Models\User;
use App\Domain\Billing\Models\Plan;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<\App\Domain\Billing\Models\Invoice>
 */
class InvoiceFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<Invoice>
     */
    protected $model = Invoice::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $start = Carbon::today();

        return [
            'uuid' => (string) Str::uuid(),
            'user_id' => User::factory(),
            'package_id' => Plan::factory(),
            'package_price' => 99.99,
            'discount' => 0,
            'coupon_code' => null,
            'price' => 99.99,
            'currency' => 'USD',
            'currency_symbol' => '$',
            'payment_method' => 'manual',
            'transaction_id' => Str::uuid()->toString(),
            'status' => 0,
            'is_trial' => false,
            'trial_days' => 0,
            'receipt' => null,
            'transaction_details' => null,
            'settings' => null,
            'start_date' => $start,
            'expire_date' => $start->copy()->addMonth(),
            'modified' => false,
            'conversation_id' => Str::random(16),
        ];
    }

    /**
     * Indicate that the invoice is paid/approved.
     */
    public function paid(): self
    {
        return $this->state(fn () => ['status' => 1]);
    }

    /**
     * Indicate that the invoice is rejected.
     */
    public function rejected(): self
    {
        return $this->state(fn () => ['status' => 2]);
    }
}

