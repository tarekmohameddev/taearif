<?php

namespace Database\Factories;

use App\Domain\Admin\Models\Admin;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<\App\Domain\Admin\Models\Admin>
 */
class AdminFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<Admin>
     */
    protected $model = Admin::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $firstName = $this->faker->firstName();
        $lastName = $this->faker->lastName();

        return [
            'uuid' => (string) Str::uuid(),
            'role_id' => null,
            'username' => $this->faker->unique()->userName(),
            'email' => $this->faker->unique()->safeEmail(),
            'password' => Hash::make('password'),
            'first_name' => $firstName,
            'last_name' => $lastName,
            'image' => null,
            'status' => true,
        ];
    }

    /**
     * Indicate that the admin is inactive.
     */
    public function inactive(): self
    {
        return $this->state(fn () => ['status' => false]);
    }
}

