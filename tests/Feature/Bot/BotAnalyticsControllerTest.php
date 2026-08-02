<?php

declare(strict_types=1);

namespace Tests\Feature\Bot;

use App\Models\ShadowBotDraft;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

final class BotAnalyticsControllerTest extends TestCase
{
    use DatabaseTransactions;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->actingAs($this->user, 'sanctum');
    }

    public function test_dashboard_returns_correct_structure(): void
    {
        $response = $this->getJson('/api/v1/whatsapp/ai/bot/dashboard');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'period', 'since', 'usage', 'shadow', 'handoff_reasons',
                'top_unanswered', 'last_eval',
            ]);
    }

    public function test_shadow_drafts_returns_paginated_list(): void
    {
        ShadowBotDraft::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'status' => 'pending',
        ]);

        $response = $this->getJson('/api/v1/whatsapp/ai/bot/shadow-drafts');
        $response->assertStatus(200);
    }

    public function test_act_on_draft_approve(): void
    {
        $draft = ShadowBotDraft::factory()->create([
            'user_id' => $this->user->id,
            'status'  => 'pending',
        ]);

        $response = $this->postJson("/api/v1/whatsapp/ai/bot/shadow-drafts/{$draft->id}/act", [
            'action' => 'approved',
        ]);

        $response->assertStatus(200)->assertJson(['success' => true, 'status' => 'approved']);
        $this->assertDatabaseHas('shadow_bot_drafts', ['id' => $draft->id, 'status' => 'approved']);
    }

    public function test_act_on_draft_conflict_when_not_pending(): void
    {
        $draft = ShadowBotDraft::factory()->create([
            'user_id' => $this->user->id,
            'status'  => 'approved',
        ]);

        $response = $this->postJson("/api/v1/whatsapp/ai/bot/shadow-drafts/{$draft->id}/act", [
            'action' => 'discarded',
        ]);

        $response->assertStatus(409);
    }
}
