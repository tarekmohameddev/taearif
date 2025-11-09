<?php

namespace Database\Factories;

use App\Domain\Crm\Models\Lead;
use App\Domain\Crm\Models\LeadActivity;
use App\Domain\Admin\Models\Admin;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<\App\Domain\Crm\Models\LeadActivity>
 */
class LeadActivityFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<LeadActivity>
     */
    protected $model = LeadActivity::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $scheduled = Carbon::now()->addDays($this->faker->numberBetween(1, 7));

        return [
            'lead_id' => Lead::factory(),
            'admin_id' => Admin::factory(),
            'type' => $this->faker->randomElement(['note', 'call', 'email', 'meeting', 'task']),
            'description' => $this->faker->sentence(),
            'scheduled_at' => $scheduled,
            'completed_at' => null,
        ];
    }
}

