<?php

namespace Database\Factories;

use App\Models\Api\Rms\RmReminder;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<\App\Models\Api\Rms\RmReminder>
 */
class RmReminderFactory extends Factory
{
    protected $model = RmReminder::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'type' => $this->faker->randomElement(['rent', 'maintenance', 'payment']),
            'entity_type' => $this->faker->randomElement(['lease', 'invoice', 'task']),
            'entity_id' => null,
            'rental_id' => null,
            'due_on' => Carbon::now()->addDays($this->faker->numberBetween(-3, 10)),
            'message' => $this->faker->sentence(),
            'status' => $this->faker->randomElement(['pending', 'completed', 'dismissed']),
            'snooze_until' => null,
        ];
    }
}

