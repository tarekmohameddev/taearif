<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ShadowBotDraft;
use Illuminate\Database\Eloquent\Factories\Factory;

final class ShadowBotDraftFactory extends Factory
{
    protected $model = ShadowBotDraft::class;

    public function definition(): array
    {
        return [
            'conversation_id'    => $this->faker->randomNumber(5),
            'user_id'            => $this->faker->randomNumber(4),
            'trigger_message_id' => $this->faker->randomNumber(5),
            'draft_reply'        => $this->faker->sentence(10),
            'used_sources'       => [],
            'confidence'         => $this->faker->numberBetween(50, 95),
            'status'             => 'pending',
            'agent_reply'        => null,
            'agent_id'           => null,
            'acted_at'           => null,
            'tokens_in'          => $this->faker->numberBetween(100, 800),
            'tokens_out'         => $this->faker->numberBetween(50, 300),
        ];
    }
}
