<?php

namespace Database\Factories;

use App\Domain\Billing\Models\Plan;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<\App\Domain\Billing\Models\Plan>
 */
class PlanFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<Plan>
     */
    protected $model = Plan::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = $this->faker->unique()->words(3, true);

        return [
            'title' => ucfirst($title),
            'subtitle' => $this->faker->sentence(),
            'slug' => Str::slug($title) . '-' . Str::random(6),
            'price' => $this->faker->randomFloat(2, 10, 500),
            'term' => 'monthly',
            'icon' => null,
            'featured' => 0,
            'is_trial' => false,
            'trial_days' => 0,
            'status' => 1,
            'is_active' => true,
            'features' => ['feature 1', 'feature 2'],
            'new_features' => [],
            'meta_keywords' => $this->faker->words(3, true),
            'meta_description' => $this->faker->sentence(),
            'number_of_vcards' => $this->faker->numberBetween(0, 50),
            'project_limit_number' => $this->faker->numberBetween(0, 50),
            'real_estate_limit_number' => $this->faker->numberBetween(0, 50),
            'video_size_limit' => $this->faker->numberBetween(100, 1000),
            'file_size_limit' => $this->faker->numberBetween(100, 2048),
            'serial_number' => $this->faker->numberBetween(1, 1000),
        ];
    }

    /**
     * Indicate that the plan includes a trial.
     */
    public function trial(): self
    {
        return $this->state(fn () => [
            'is_trial' => true,
            'trial_days' => $this->faker->numberBetween(1, 30),
        ]);
    }

    /**
     * Indicate that the plan is featured.
     */
    public function featured(): self
    {
        return $this->state(fn () => ['featured' => 1]);
    }
}

