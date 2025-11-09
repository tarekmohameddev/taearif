<?php

namespace Database\Factories;

use App\Models\Api\UserApiCustomerReminder;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<\App\Models\Api\UserApiCustomerReminder>
 */
class UserApiCustomerReminderFactory extends Factory
{
    protected $model = UserApiCustomerReminder::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'customer_id' => null,
            'title' => $this->faker->sentence(3),
            'priority' => $this->faker->numberBetween(1, 3),
            'datetime' => Carbon::now()->addDays($this->faker->numberBetween(-2, 5)),
        ];
    }
}

