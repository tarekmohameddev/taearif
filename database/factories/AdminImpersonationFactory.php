<?php

namespace Database\Factories;

use App\Domain\Admin\Models\Admin;
use App\Domain\Admin\Models\AdminImpersonation;
use App\Domain\User\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<\App\Domain\Admin\Models\AdminImpersonation>
 */
class AdminImpersonationFactory extends Factory
{
    protected $model = AdminImpersonation::class;

    public function definition(): array
    {
        return [
            'admin_id' => Admin::factory(),
            'user_id' => User::factory(),
            'token_id' => null,
            'started_at' => Carbon::now(),
            'ended_at' => null,
            'duration_seconds' => null,
            'ip_address' => $this->faker->ipv4(),
            'user_agent' => $this->faker->userAgent(),
            'reason' => $this->faker->sentence(),
            'actions_count' => 0,
            'status' => 'active',
        ];
    }

    public function ended(): self
    {
        return $this->state(function () {
            $endedAt = Carbon::now();

            return [
                'ended_at' => $endedAt,
                'status' => 'ended',
                'duration_seconds' => 300,
            ];
        });
    }
}

