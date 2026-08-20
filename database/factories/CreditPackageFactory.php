<?php

namespace Database\Factories;

use App\Models\Api\marketing\CreditPackage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Api\marketing\CreditPackage>
 */
class CreditPackageFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<CreditPackage>
     */
    protected $model = CreditPackage::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = $this->faker->unique()->words(3, true);

        return [
            'name' => ucfirst($name),
            'name_ar' => 'باقة ' . $this->faker->unique()->numerify('###'),
            'description' => $this->faker->sentence(),
            'description_ar' => 'وصف الباقة ' . $this->faker->unique()->numerify('###'),
            'credits' => $this->faker->numberBetween(50, 1000),
            'price' => $this->faker->randomFloat(2, 10, 500),
            'currency' => 'SAR',
            'discount_percentage' => 0,
            'is_popular' => false,
            'is_active' => true,
            'sort_order' => $this->faker->numberBetween(0, 20),
            'features' => [],
            'supports_marketing_channels' => true,
            'marketing_features' => [],
            'marketing_priority' => 0,
        ];
    }

    /**
     * Indicate that the package is inactive.
     */
    public function inactive(): self
    {
        return $this->state(fn () => ['is_active' => false]);
    }

    /**
     * Indicate that the package is popular.
     */
    public function popular(): self
    {
        return $this->state(fn () => ['is_popular' => true]);
    }
}
