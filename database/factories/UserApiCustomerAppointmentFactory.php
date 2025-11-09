<?php

namespace Database\Factories;

use App\Models\Api\UserApiCustomerAppointment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<\App\Models\Api\UserApiCustomerAppointment>
 */
class UserApiCustomerAppointmentFactory extends Factory
{
    protected $model = UserApiCustomerAppointment::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'customer_id' => null,
            'title' => $this->faker->sentence(4),
            'type' => $this->faker->randomElement(['meeting', 'call', 'follow-up']),
            'priority' => $this->faker->numberBetween(1, 3),
            'note' => $this->faker->optional()->sentence(),
            'datetime' => Carbon::now()->addDays($this->faker->numberBetween(-1, 7)),
            'duration' => $this->faker->numberBetween(15, 120),
        ];
    }
}

