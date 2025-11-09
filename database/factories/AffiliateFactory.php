<?php

namespace Database\Factories;

use App\Domain\Referral\Models\Affiliate;
use App\Domain\User\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<\App\Domain\Referral\Models\Affiliate>
 */
class AffiliateFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<Affiliate>
     */
    protected $model = Affiliate::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'fullname' => $this->faker->name(),
            'bank_name' => $this->faker->company(),
            'bank_account_number' => $this->faker->bankAccountNumber(),
            'iban' => strtoupper('SA' . $this->faker->numerify(str_repeat('#', 20))),
            'commission_percentage' => $this->faker->randomFloat(2, 5, 25),
            'pending_amount' => $this->faker->randomFloat(2, 0, 1000),
            'request_status' => 'pending',
            'start_date_value' => $this->faker->date(),
            'to_date_value' => $this->faker->date(),
        ];
    }

    /**
     * Indicate that the affiliate is approved.
     */
    public function approved(): self
    {
        return $this->state(fn () => ['request_status' => 'approved']);
    }
}

