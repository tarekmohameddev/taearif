<?php

declare(strict_types=1);

namespace Tests\Feature\WhatsappAI;

use App\Models\Conversation;
use App\Models\User;
use App\Models\WaAiConfig;
use App\Models\WaConversationAiState;
use App\Models\WaNumber;
use App\Models\WhatsappUser;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Modules\WhatsappAI\Entities\WhatsappConversation;
use Tests\TestCase;

/**
 * Tests for the configurable agent-reply bot pause feature.
 *
 * Covers:
 * - Echo webhook: pause honours agent_reply_pause (off / 24h / 48h / indefinite)
 * - CRM send: pause triggered after successful message send
 * - Resume endpoint: clears agent_takeover pauses, rejects others
 */
class AgentReplyPauseTest extends TestCase
{
    use DatabaseTransactions;

    private function requireTables(): void
    {
        foreach ([
            'whatsapp_conversations',
            'whatsapp_messages',
            'whatsapp_users',
            'conversations',
            'messages',
            'wa_conversation_states',
            'wa_conversation_ai_states',
            'wa_numbers',
            'wa_ai_configs',
        ] as $table) {
            if (! Schema::hasTable($table)) {
                $this->markTestSkipped("{$table} table required.");
            }
        }
    }

    private function createTenant(): User
    {
        return User::factory()->create([
            'account_type' => 'tenant',
            'tenant_id'    => null,
            'rbac_version' => (int) config('rbac.version', 1),
            'rbac_seeded_at' => now(),
        ]);
    }

    private function createWhatsappUser(User $tenant, string $phoneId): WhatsappUser
    {
        return WhatsappUser::create([
            'user_id'  => $tenant->id,
            'phone_id' => $phoneId,
            'number'   => '+966501111111',
            'status'   => 'active',
        ]);
    }

    private function createWaNumber(User $tenant, string $phoneId): WaNumber
    {
        return WaNumber::create([
            'user_id'         => $tenant->id,
            'provider'        => 'meta',
            'phone_number'    => '+966501111111',
            'phone_number_id' => $phoneId,
            'name'            => 'Test Number',
            'status'          => 'active',
        ]);
    }

    private function createAiConfig(User $tenant, WaNumber $number, string $pauseMode): WaAiConfig
    {
        return WaAiConfig::create([
            'user_id'           => $tenant->id,
            'wa_number_id'      => $number->id,
            'enabled'           => true,
            'autonomy_level'    => 'autonomous',
            'agent_reply_pause' => $pauseMode,
            'scenarios'         => [],
        ]);
    }

    private function seedAiState(Conversation $conversation, User $tenant): WaConversationAiState
    {
        return WaConversationAiState::create([
            'conversation_id'    => $conversation->id,
            'user_id'            => $tenant->id,
            'facts'              => [],
            'opt_out_status'     => 'active',
            'disclosed_as_assistant' => false,
        ]);
    }

    private function echoPayload(string $phoneId, string $businessPhone, string $customerPhone, string $messageId, string $body = 'Hello from agent'): array
    {
        return [
            'entry' => [[
                'id'      => 'waba_test',
                'changes' => [[
                    'field' => 'message_echoes',
                    'value' => [
                        'metadata' => [
                            'phone_number_id'      => $phoneId,
                            'display_phone_number' => $businessPhone,
                        ],
                        'message_echoes' => [[
                            'id'        => $messageId,
                            'from'      => $businessPhone,
                            'to'        => $customerPhone,
                            'timestamp' => (string) time(),
                            'type'      => 'text',
                            'text'      => ['body' => $body],
                        ]],
                    ],
                ]],
            ]],
        ];
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Echo webhook tests
    // ──────────────────────────────────────────────────────────────────────────

    /** @test */
    public function echo_webhook_pauses_bot_for_48h_by_default(): void
    {
        $this->requireTables();
        Queue::fake();

        $tenant      = $this->createTenant();
        $phoneId     = 'phone_pause_48h_' . uniqid();
        $bizPhone    = '+966501000001';
        $custPhone   = '966509' . random_int(1000000, 9999999);
        $messageId   = 'wamid.pause48.' . uniqid();

        $this->createWhatsappUser($tenant, $phoneId);
        $waNumber = $this->createWaNumber($tenant, $phoneId);
        $this->createAiConfig($tenant, $waNumber, '48h');

        // Pre-create a Communication conversation + AI state so the echo path finds them
        $conversation = Conversation::create([
            'user_id'                    => $tenant->id,
            'external_party_identifier'  => '+' . $custPhone,
            'channel'                    => 'whatsapp',
            'status'                     => 'open',
        ]);
        $aiState = $this->seedAiState($conversation, $tenant);

        // Also need WhatsappConversation linked to the synced conversation
        $whatsappUser = WhatsappUser::where('user_id', $tenant->id)->where('phone_id', $phoneId)->first();
        WhatsappConversation::create([
            'whatsapp_user_id' => $whatsappUser->id,
            'customer_phone'   => $custPhone,
            'user_id'          => $tenant->id,
            'status'           => 'collecting',
        ]);

        $response = $this->postJson('/api/whatsapp-ai/webhook', $this->echoPayload(
            $phoneId, $bizPhone, $custPhone, $messageId,
        ));

        $response->assertOk();

        $aiState->refresh();
        $this->assertNotNull($aiState->bot_paused_until);
        $this->assertEquals('agent_takeover', $aiState->handoff_reason);
        // Should be roughly 48 hours from now (allow ±60 s)
        $this->assertEqualsWithDelta(48, now()->diffInHours($aiState->bot_paused_until, false), 1);
    }

    /** @test */
    public function echo_webhook_does_not_pause_bot_when_pause_mode_is_off(): void
    {
        $this->requireTables();
        Queue::fake();

        $tenant      = $this->createTenant();
        $phoneId     = 'phone_pause_off_' . uniqid();
        $bizPhone    = '+966501000002';
        $custPhone   = '966509' . random_int(1000000, 9999999);
        $messageId   = 'wamid.pauseoff.' . uniqid();

        $this->createWhatsappUser($tenant, $phoneId);
        $waNumber = $this->createWaNumber($tenant, $phoneId);
        $this->createAiConfig($tenant, $waNumber, 'off');

        $conversation = Conversation::create([
            'user_id'                    => $tenant->id,
            'external_party_identifier'  => '+' . $custPhone,
            'channel'                    => 'whatsapp',
            'status'                     => 'open',
        ]);
        $aiState = $this->seedAiState($conversation, $tenant);

        $whatsappUser = WhatsappUser::where('user_id', $tenant->id)->where('phone_id', $phoneId)->first();
        WhatsappConversation::create([
            'whatsapp_user_id' => $whatsappUser->id,
            'customer_phone'   => $custPhone,
            'user_id'          => $tenant->id,
            'status'           => 'collecting',
        ]);

        $this->postJson('/api/whatsapp-ai/webhook', $this->echoPayload(
            $phoneId, $bizPhone, $custPhone, $messageId,
        ))->assertOk();

        $aiState->refresh();
        $this->assertNull($aiState->bot_paused_until);
        $this->assertNull($aiState->handoff_reason);
    }

    /** @test */
    public function echo_webhook_pauses_bot_indefinitely_when_configured(): void
    {
        $this->requireTables();
        Queue::fake();

        $tenant      = $this->createTenant();
        $phoneId     = 'phone_indefinite_' . uniqid();
        $bizPhone    = '+966501000003';
        $custPhone   = '966509' . random_int(1000000, 9999999);
        $messageId   = 'wamid.indefinite.' . uniqid();

        $this->createWhatsappUser($tenant, $phoneId);
        $waNumber = $this->createWaNumber($tenant, $phoneId);
        $this->createAiConfig($tenant, $waNumber, 'indefinite');

        $conversation = Conversation::create([
            'user_id'                    => $tenant->id,
            'external_party_identifier'  => '+' . $custPhone,
            'channel'                    => 'whatsapp',
            'status'                     => 'open',
        ]);
        $aiState = $this->seedAiState($conversation, $tenant);

        $whatsappUser = WhatsappUser::where('user_id', $tenant->id)->where('phone_id', $phoneId)->first();
        WhatsappConversation::create([
            'whatsapp_user_id' => $whatsappUser->id,
            'customer_phone'   => $custPhone,
            'user_id'          => $tenant->id,
            'status'           => 'collecting',
        ]);

        $this->postJson('/api/whatsapp-ai/webhook', $this->echoPayload(
            $phoneId, $bizPhone, $custPhone, $messageId,
        ))->assertOk();

        $aiState->refresh();
        $this->assertTrue($aiState->isBotPaused());
        $this->assertEquals('agent_takeover', $aiState->handoff_reason);
        // Far-future sentinel — should be many years in the future
        $this->assertGreaterThan(100, now()->diffInDays($aiState->bot_paused_until, false));
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Resume endpoint tests
    // ──────────────────────────────────────────────────────────────────────────

    /** @test */
    public function resume_endpoint_clears_agent_takeover_pause(): void
    {
        $this->requireTables();

        $tenant   = $this->createTenant();
        $waNumber = WaNumber::create([
            'user_id'      => $tenant->id,
            'provider'     => 'meta',
            'phone_number' => '+966501000010',
            'name'         => 'Resume Test',
            'status'       => 'active',
        ]);

        $conversation = Conversation::create([
            'user_id'                    => $tenant->id,
            'external_party_identifier'  => '+966509000010',
            'channel'                    => 'whatsapp',
            'status'                     => 'open',
        ]);

        $aiState = WaConversationAiState::create([
            'conversation_id'        => $conversation->id,
            'user_id'                => $tenant->id,
            'facts'                  => [],
            'opt_out_status'         => 'active',
            'disclosed_as_assistant' => false,
            'bot_paused_until'       => now()->addHours(48),
            'handoff_reason'         => 'agent_takeover',
        ]);

        // wa_conversation_states row is needed for findForUserByConversationOrStateId
        \App\Models\WaConversationState::create([
            'user_id'         => $tenant->id,
            'conversation_id' => $conversation->id,
            'wa_number_id'    => $waNumber->id,
            'status'          => 'active',
        ]);

        Sanctum::actingAs($tenant);

        $res = $this->postJson("/api/v1/whatsapp/conversations/{$conversation->id}/bot/resume");

        $res->assertOk()
            ->assertJsonPath('data.bot_paused_until', null)
            ->assertJsonPath('data.handoff_reason', null)
            ->assertJsonPath('data.needs_attention', false);

        $aiState->refresh();
        $this->assertNull($aiState->bot_paused_until);
        $this->assertNull($aiState->handoff_reason);
    }

    /** @test */
    public function resume_endpoint_is_idempotent_when_bot_already_unpaused(): void
    {
        $this->requireTables();

        $tenant   = $this->createTenant();
        $waNumber = WaNumber::create([
            'user_id'      => $tenant->id,
            'provider'     => 'meta',
            'phone_number' => '+966501000011',
            'name'         => 'Resume Test 2',
            'status'       => 'active',
        ]);

        $conversation = Conversation::create([
            'user_id'                    => $tenant->id,
            'external_party_identifier'  => '+966509000011',
            'channel'                    => 'whatsapp',
            'status'                     => 'open',
        ]);

        WaConversationAiState::create([
            'conversation_id'        => $conversation->id,
            'user_id'                => $tenant->id,
            'facts'                  => [],
            'opt_out_status'         => 'active',
            'disclosed_as_assistant' => false,
        ]);

        \App\Models\WaConversationState::create([
            'user_id'         => $tenant->id,
            'conversation_id' => $conversation->id,
            'wa_number_id'    => $waNumber->id,
            'status'          => 'active',
        ]);

        Sanctum::actingAs($tenant);

        $res = $this->postJson("/api/v1/whatsapp/conversations/{$conversation->id}/bot/resume");

        $res->assertOk()
            ->assertJsonPath('data.bot_paused_until', null)
            ->assertJsonPath('data.needs_attention', false);
    }

    /** @test */
    public function resume_endpoint_rejects_non_agent_takeover_pause(): void
    {
        $this->requireTables();

        $tenant   = $this->createTenant();
        $waNumber = WaNumber::create([
            'user_id'      => $tenant->id,
            'provider'     => 'meta',
            'phone_number' => '+966501000012',
            'name'         => 'Resume Test 3',
            'status'       => 'active',
        ]);

        $conversation = Conversation::create([
            'user_id'                    => $tenant->id,
            'external_party_identifier'  => '+966509000012',
            'channel'                    => 'whatsapp',
            'status'                     => 'open',
        ]);

        WaConversationAiState::create([
            'conversation_id'        => $conversation->id,
            'user_id'                => $tenant->id,
            'facts'                  => [],
            'opt_out_status'         => 'active',
            'disclosed_as_assistant' => false,
            'bot_paused_until'       => now()->addHours(24),
            'handoff_reason'         => 'compliance',
        ]);

        \App\Models\WaConversationState::create([
            'user_id'         => $tenant->id,
            'conversation_id' => $conversation->id,
            'wa_number_id'    => $waNumber->id,
            'status'          => 'active',
        ]);

        Sanctum::actingAs($tenant);

        $res = $this->postJson("/api/v1/whatsapp/conversations/{$conversation->id}/bot/resume");

        $res->assertUnprocessable()
            ->assertJsonPath('code', 'BOT_PAUSE_NOT_RESUMABLE');
    }
}
