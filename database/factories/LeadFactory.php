<?php

namespace Database\Factories;

use App\Domain\Crm\Models\Lead;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<\App\Domain\Crm\Models\Lead>
 */
class LeadFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<Lead>
     */
    protected $model = Lead::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'phone' => $this->faker->phoneNumber(),
            'company' => $this->faker->company(),
            'source' => 'manual',
            'status' => 'new',
            'notes' => $this->faker->sentence(),
            'custom_fields' => ['budget' => $this->faker->numberBetween(1000, 10000)],
        ];
    }

    /**
     * Indicate that the lead has a specific status.
     */
    public function status(string $status): self
    {
        return $this->state(fn () => ['status' => $status]);
    }
}

