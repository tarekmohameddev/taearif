<?php

namespace Database\Factories;

use App\Domain\Domain\Models\CustomDomain;
use App\Domain\User\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Domain\Domain\Models\CustomDomain>
 */
class CustomDomainFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<CustomDomain>
     */
    protected $model = CustomDomain::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'requested_domain' => $this->faker->domainName(),
            'current_domain' => null,
            'status' => false,
        ];
    }

    /**
     * Mark the domain as approved (current domain assigned).
     */
    public function approved(): self
    {
        return $this->state(fn (array $attributes) => [
            'current_domain' => $attributes['requested_domain'] ?? $this->faker->domainName(),
            'status' => true,
        ]);
    }
}

