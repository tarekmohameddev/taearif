<?php

namespace Database\Factories;

use App\Domain\Support\Models\Inquiry;
use App\Domain\User\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Domain\Support\Models\Inquiry>
 */
class InquiryFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<Inquiry>
     */
    protected $model = Inquiry::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'customer_id' => null,
            'phone_number' => $this->faker->phoneNumber(),
            'message' => $this->faker->sentence(),
            'inquiry_type' => 'buy',
            'property_type' => 'apartment',
            'budget' => $this->faker->randomFloat(2, 1000, 10000),
            'currency' => 'USD',
            'bedrooms' => $this->faker->numberBetween(1, 5),
            'bathrooms' => $this->faker->numberBetween(1, 3),
            'min_area_sqm' => 50,
            'max_area_sqm' => 120,
            'furnished' => false,
            'urgency' => 'medium',
            'location' => $this->faker->city(),
            'country_code' => 'US',
            'region_code' => 'CA',
            'region_name' => $this->faker->state(),
            'city' => $this->faker->city(),
            'district' => $this->faker->streetName(),
            'latitude' => $this->faker->latitude(),
            'longitude' => $this->faker->longitude(),
            'location_confidence' => 85,
            'source_channel' => 'website',
            'lang' => 'en',
            'detected_entities_json' => [],
        ];
    }
}

