<?php

namespace Database\Factories;

use App\Domain\Admin\Models\Role;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Domain\Admin\Models\Role>
 */
class RoleFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<Role>
     */
    protected $model = Role::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->jobTitle(),
            'permissions' => ['view_dashboard', 'manage_users'],
        ];
    }
}

