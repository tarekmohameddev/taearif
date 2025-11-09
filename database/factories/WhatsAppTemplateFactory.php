<?php

namespace Database\Factories;

use App\Domain\Marketing\Models\WhatsAppTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Domain\Marketing\Models\WhatsAppTemplate>
 */
class WhatsAppTemplateFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<WhatsAppTemplate>
     */
    protected $model = WhatsAppTemplate::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->words(2, true),
            'description' => $this->faker->sentence(),
            'content' => 'Hello {{name}}, welcome to our service!',
            'type' => $this->faker->randomElement(['welcome', 'notification', 'reminder']),
            'language' => $this->faker->randomElement(['en', 'ar']),
            'variables' => 'name',
            'status' => true,
        ];
    }
}

