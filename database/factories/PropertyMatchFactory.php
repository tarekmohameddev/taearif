<?php

namespace Database\Factories;

use App\Models\PropertyMatch;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PropertyMatch>
 */
class PropertyMatchFactory extends Factory
{
    protected $model = PropertyMatch::class;

    public function definition(): array
    {
        return [
            'user_id'          => 1,
            'customer_key'     => $this->faker->numerify('9665########'),
            'request_type'     => 'web',
            'request_id'       => 1,
            'property_id'      => 1,
            'match_score'      => $this->faker->numberBetween(20, 90),
            'database_score'   => $this->faker->numberBetween(10, 50),
            'ai_score'         => $this->faker->numberBetween(0, 50),
            'match_explanation'=> $this->faker->sentence(),
            'matched_criteria' => ['location', 'type'],
            'is_reviewed'      => false,
            'is_contacted'     => false,
        ];
    }

    public function reviewed(): static
    {
        return $this->state(['is_reviewed' => true]);
    }

    public function forRequest(int $userId, string $requestType, int $requestId): static
    {
        return $this->state([
            'user_id'      => $userId,
            'request_type' => $requestType,
            'request_id'   => $requestId,
        ]);
    }
}
