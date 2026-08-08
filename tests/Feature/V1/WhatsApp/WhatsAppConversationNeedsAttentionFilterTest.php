<?php

declare(strict_types=1);

namespace Tests\Feature\V1\WhatsApp;

use App\Models\Conversation;
use App\Models\User;
use App\Models\WaConversationAiState;
use App\Models\WaConversationState;
use App\Models\WaNumber;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class WhatsAppConversationNeedsAttentionFilterTest extends TestCase
{
    use DatabaseTransactions;

    private function requireTables(): void
    {
        foreach (['conversations', 'wa_conversation_states', 'wa_conversation_ai_states', 'wa_numbers'] as $table) {
            if (! Schema::hasTable($table)) {
                $this->markTestSkipped("{$table} table required.");
            }
        }
    }

    private function createTenant(): User
    {
        return User::factory()->create(['account_type' => 'tenant', 'tenant_id' => null]);
    }

    /**
     * @return array{tenant: User, waNumber: WaNumber, needsAttention: WaConversationState, agentTakeover: WaConversationState, normal: WaConversationState}
     */
    private function seedConversations(User $tenant): array
    {
        $waNumber = WaNumber::create([
            'user_id' => $tenant->id,
            'provider' => 'meta',
            'phone_number' => '+966501111111',
            'name' => 'Main',
            'status' => 'active',
        ]);

        $needsAttention = $this->makeState($tenant, $waNumber, '+966511111111', [
            'bot_paused_until' => now()->addHours(24),
            'handoff_reason' => 'customer_requested_human',
        ]);

        $agentTakeover = $this->makeState($tenant, $waNumber, '+966522222222', [
            'bot_paused_until' => now()->addHours(48),
            'handoff_reason' => 'agent_takeover',
        ]);

        $normal = $this->makeState($tenant, $waNumber, '+966533333333', null);

        return compact('tenant', 'waNumber', 'needsAttention', 'agentTakeover', 'normal');
    }

    /**
     * @param  array{bot_paused_until?: \Carbon\CarbonInterface, handoff_reason?: string}|null  $ai
     */
    private function makeState(User $tenant, WaNumber $waNumber, string $phone, ?array $ai): WaConversationState
    {
        $conversation = Conversation::create([
            'user_id' => $tenant->id,
            'channel' => 'whatsapp',
            'external_party_identifier' => $phone,
            'last_message_at' => now(),
        ]);

        $state = WaConversationState::create([
            'conversation_id' => $conversation->id,
            'user_id' => $tenant->id,
            'wa_number_id' => $waNumber->id,
            'status' => 'active',
            'is_starred' => false,
            'unread_count' => 0,
            'last_message_time' => now(),
        ]);

        if ($ai !== null) {
            WaConversationAiState::create([
                'conversation_id' => $conversation->id,
                'user_id' => $tenant->id,
                'bot_paused_until' => $ai['bot_paused_until'],
                'handoff_reason' => $ai['handoff_reason'],
            ]);
        }

        return $state->fresh(['aiState']);
    }

    /** @test */
    public function list_includes_ai_attention_fields_without_filter(): void
    {
        $this->requireTables();

        $tenant = $this->createTenant();
        $fixture = $this->seedConversations($tenant);

        Sanctum::actingAs($tenant);
        $res = $this->getJson('/api/v1/whatsapp/conversations?wa_number_id=' . $fixture['waNumber']->id);
        $res->assertOk();

        $rows = collect($res->json('data.data'));
        $attentionRow = $rows->firstWhere('id', $fixture['needsAttention']->id);
        $normalRow = $rows->firstWhere('id', $fixture['normal']->id);

        $this->assertNotNull($attentionRow);
        $this->assertTrue($attentionRow['needs_attention']);
        $this->assertSame('customer_requested_human', $attentionRow['handoff_reason']);
        $this->assertNotNull($attentionRow['bot_paused_until']);

        $this->assertNotNull($normalRow);
        $this->assertFalse($normalRow['needs_attention']);
        $this->assertNull($normalRow['handoff_reason']);
        $this->assertNull($normalRow['bot_paused_until']);
    }

    /** @test */
    public function needs_attention_filter_returns_only_escalated_conversations(): void
    {
        $this->requireTables();

        $tenant = $this->createTenant();
        $fixture = $this->seedConversations($tenant);

        Sanctum::actingAs($tenant);
        $res = $this->getJson(
            '/api/v1/whatsapp/conversations?needs_attention=1&wa_number_id=' . $fixture['waNumber']->id
        );
        $res->assertOk();

        $ids = collect($res->json('data.data'))->pluck('id')->all();

        $this->assertContains($fixture['needsAttention']->id, $ids);
        $this->assertNotContains($fixture['agentTakeover']->id, $ids);
        $this->assertNotContains($fixture['normal']->id, $ids);
        $this->assertCount(1, $ids);
    }

    /** @test */
    public function show_includes_needs_attention_fields(): void
    {
        $this->requireTables();

        $tenant = $this->createTenant();
        $fixture = $this->seedConversations($tenant);

        Sanctum::actingAs($tenant);
        $res = $this->getJson('/api/v1/whatsapp/conversations/' . $fixture['needsAttention']->conversation_id);
        $res->assertOk();

        $data = $res->json('data.data');
        $this->assertTrue($data['needs_attention']);
        $this->assertSame('customer_requested_human', $data['handoff_reason']);
        $this->assertNotNull($data['bot_paused_until']);
    }
}
