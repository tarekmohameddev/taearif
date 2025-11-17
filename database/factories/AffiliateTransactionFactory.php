<?php

namespace Database\Factories;

use App\Domain\Affiliate\Models\Affiliate;
use App\Domain\Affiliate\Models\AffiliateTransaction;
use App\Models\User as TenantUser;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Domain\Affiliate\Models\AffiliateTransaction>
 */
class AffiliateTransactionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<AffiliateTransaction>
     */
    protected $model = AffiliateTransaction::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'affiliate_id' => Affiliate::factory(),
            'referral_user_id' => TenantUser::factory(),
            'type' => 'pending',
            'amount' => $this->faker->randomFloat(2, 10, 500),
            'note' => $this->faker->sentence(),
        ];
    }

    /**
     * Mark the transaction as collected.
     */
    public function collected(): self
    {
        return $this->state(fn () => ['type' => 'collected']);
    }
}

