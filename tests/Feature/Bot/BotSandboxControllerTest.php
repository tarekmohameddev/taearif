<?php

declare(strict_types=1);

namespace Tests\Feature\Bot;

use App\Domain\Ai\Contracts\LlmClient;
use App\Domain\Ai\DTOs\LlmRequest;
use App\Domain\Ai\DTOs\LlmResponse;
use App\Domain\Ai\Services\LlmDriverFactory;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Models\WaAiConfig;
use App\Models\WaConversationAiState;
use App\Models\WaNumber;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * Tests for the Sandbox Simulator endpoints:
 *   POST /api/v1/whatsapp/ai/bot/simulate
 *   GET  /api/v1/whatsapp/ai/bot/simulate/conversation
 *   POST /api/v1/whatsapp/ai/bot/simulate/reset
 */
final class BotSandboxControllerTest extends TestCase
{
    use DatabaseTransactions;

    private User $user;
    private WaNumber $waNumber;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->actingAs($this->user, 'sanctum');

        // Create a WaNumber record so references to it can resolve
        $this->waNumber = WaNumber::create([
            'user_id'      => $this->user->id,
            'phone_number' => '+966500000099',
            'status'       => 'active',
        ]);

        // Create a bot config for the number with autonomy_level = 'autonomous'
        // autonomy_level is added by migration so we use forceFill + save
        $config = new WaAiConfig([
            'user_id'      => $this->user->id,
            'wa_number_id' => $this->waNumber->id,
            'enabled'      => true,
        ]);
        $config->forceFill(['autonomy_level' => 'autonomous'])->save();

        // Stub the LLM driver so no real API calls are made
        $this->bindStubLlmDriver();
    }

    public function test_first_turn_creates_sandbox_conversation_and_persists_messages(): void
    {
        $response = $this->postJson('/api/v1/whatsapp/ai/bot/simulate', [
            'wa_number_id'   => $this->waNumber->id,
            'message'        => 'السلام عليكم، ابحث عن شقة',
            'customer_phone' => '+966500000001',
        ]);

        $response->assertStatus(200);
        $data = $response->json();

        // Conversation ID returned
        $this->assertArrayHasKey('conversation_id', $data);
        $convId = $data['conversation_id'];
        $this->assertNotNull($convId);

        // Conversation stored with sandbox channel
        $conv = Conversation::find($convId);
        $this->assertNotNull($conv);
        $this->assertSame('whatsapp_sandbox', $conv->channel);

        // Inbound message persisted
        $this->assertDatabaseHas('messages', [
            'conversation_id' => $convId,
            'direction'       => 'inbound',
            'content'         => 'السلام عليكم، ابحث عن شقة',
        ]);

        // Outbound message persisted (bot replied)
        $this->assertGreaterThan(0, Message::where('conversation_id', $convId)->where('direction', 'outbound')->count());

        // Required response fields present
        $this->assertArrayHasKey('outcome', $data);
        $this->assertArrayHasKey('trace', $data);
        $this->assertArrayHasKey('bot_messages', $data);
        $this->assertArrayHasKey('turn_index', $data);
        $this->assertSame(1, $data['turn_index']);
    }

    public function test_second_turn_sees_first_turn_in_context(): void
    {
        $phone = '+966500000001';
        $waId  = $this->waNumber->id;

        // Turn 1
        $this->postJson('/api/v1/whatsapp/ai/bot/simulate', [
            'wa_number_id'   => $waId,
            'message'        => 'ابحث عن شقة في الرياض',
            'customer_phone' => $phone,
        ])->assertStatus(200);

        // Turn 2 — same conversation
        $response = $this->postJson('/api/v1/whatsapp/ai/bot/simulate', [
            'wa_number_id'   => $waId,
            'message'        => 'وش أرخص شقة عندكم؟',
            'customer_phone' => $phone,
        ]);

        $response->assertStatus(200);
        $data = $response->json();

        $this->assertSame(2, $data['turn_index']);

        // Both turns are in the same sandbox conversation
        $convId = $data['conversation_id'];
        $inboundCount = Message::where('conversation_id', $convId)->where('direction', 'inbound')->count();
        $this->assertSame(2, (int) $inboundCount);
    }

    public function test_include_transcript_embeds_messages_in_response(): void
    {
        $response = $this->postJson('/api/v1/whatsapp/ai/bot/simulate', [
            'wa_number_id'       => $this->waNumber->id,
            'message'            => 'مرحبا',
            'customer_phone'     => '+966500000002',
            'include_transcript' => true,
        ]);

        $response->assertStatus(200);
        $data = $response->json();

        $this->assertArrayHasKey('transcript', $data);
        $this->assertArrayHasKey('messages', $data['transcript']);
        $this->assertGreaterThan(0, count($data['transcript']['messages']));
    }

    public function test_known_issues_repro_greeting_does_not_increment_failed_turns_and_building_does_not_ask_bedrooms(): void
    {
        $phone = '+966500000099';
        $waId  = $this->waNumber->id;

        // Ensure a clean sandbox conversation
        $this->postJson('/api/v1/whatsapp/ai/bot/simulate/reset', [
            'wa_number_id'   => $waId,
            'customer_phone' => $phone,
        ])->assertStatus(200);

        // Turn 1: greeting should not increment failed turns
        $turn1 = $this->postJson('/api/v1/whatsapp/ai/bot/simulate', [
            'wa_number_id'       => $waId,
            'message'            => 'حياك الله',
            'customer_phone'     => $phone,
            'include_transcript' => true,
        ])->assertStatus(200)->json();

        $facts1 = is_array($turn1['facts'] ?? null) ? $turn1['facts'] : [];
        $this->assertSame(0, (int) ($facts1['_failed_turns'] ?? 0));

        // Turn 2: building + street + budget should not trigger bedrooms slot-fill
        $turn2 = $this->postJson('/api/v1/whatsapp/ai/bot/simulate', [
            'wa_number_id'       => $waId,
            'message'            => 'بدور على عمارة في شارع الملك عبد العزيز بميزانية 7 مليون',
            'customer_phone'     => $phone,
            'include_transcript' => true,
        ])->assertStatus(200)->json();

        $facts2 = is_array($turn2['facts'] ?? null) ? $turn2['facts'] : [];
        $this->assertSame('عمارة', $facts2['type'] ?? null);
        $this->assertStringContainsString('شارع الملك عبد العزيز', (string) ($facts2['district'] ?? ''));
        $this->assertStringNotContainsString('بميزانية', (string) ($facts2['district'] ?? ''));
        $this->assertSame(0, (int) ($facts2['_failed_turns'] ?? 0));

        $this->assertNull($turn2['next_question'] ?? null);
    }

    public function test_simulation_transcript_endpoint_returns_conversation_history(): void
    {
        $phone = '+966500000003';
        $waId  = $this->waNumber->id;

        // Create a turn first
        $this->postJson('/api/v1/whatsapp/ai/bot/simulate', [
            'wa_number_id'   => $waId,
            'message'        => 'كم سعر الشقة؟',
            'customer_phone' => $phone,
        ])->assertStatus(200);

        // Now fetch transcript
        $response = $this->getJson("/api/v1/whatsapp/ai/bot/simulate/conversation?wa_number_id={$waId}&customer_phone={$phone}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'conversation_id',
                'messages',
                'turn_count',
                'ai_state',
            ]);

        $this->assertSame(1, $response->json('turn_count'));
    }

    public function test_sandbox_conversation_does_not_appear_in_whatsapp_inbox(): void
    {
        $this->postJson('/api/v1/whatsapp/ai/bot/simulate', [
            'wa_number_id'   => $this->waNumber->id,
            'message'        => 'اختبار',
            'customer_phone' => '+966500000004',
        ])->assertStatus(200);

        // The agent inbox endpoint lists wa_conversation_states; there must be none for sandbox
        $convs = Conversation::where('user_id', $this->user->id)
            ->where('channel', 'whatsapp_sandbox')
            ->get();
        $this->assertGreaterThan(0, $convs->count());

        // No WaConversationState record (which would make it appear in the inbox)
        foreach ($convs as $conv) {
            $this->assertDatabaseMissing('wa_conversation_states', [
                'conversation_id' => $conv->id,
            ]);
        }
    }

    public function test_reset_clears_sandbox_conversation(): void
    {
        $phone = '+966500000005';
        $waId  = $this->waNumber->id;

        // Run a turn to create the sandbox conversation
        $first = $this->postJson('/api/v1/whatsapp/ai/bot/simulate', [
            'wa_number_id'   => $waId,
            'message'        => 'ابحث عن فيلا',
            'customer_phone' => $phone,
        ])->assertStatus(200)->json();

        $convId = $first['conversation_id'];

        // Verify messages exist
        $this->assertGreaterThan(0, Message::where('conversation_id', $convId)->count());

        // Reset
        $resetResponse = $this->postJson('/api/v1/whatsapp/ai/bot/simulate/reset', [
            'wa_number_id'   => $waId,
            'customer_phone' => $phone,
        ]);

        $resetResponse->assertStatus(200)->assertJson([
            'success' => true,
            'cleared' => true,
        ]);

        // Conversation + messages deleted
        $this->assertNull(Conversation::find($convId));
        $this->assertSame(0, (int) Message::where('conversation_id', $convId)->count());
        $this->assertNull(WaConversationAiState::where('conversation_id', $convId)->first());
    }

    public function test_reset_returns_false_when_no_sandbox_exists(): void
    {
        $response = $this->postJson('/api/v1/whatsapp/ai/bot/simulate/reset', [
            'wa_number_id'   => $this->waNumber->id,
            'customer_phone' => '+966500099999',
        ]);

        $response->assertStatus(200)->assertJson([
            'success' => true,
            'cleared' => false,
        ]);
    }

    public function test_simulate_fails_with_missing_required_fields(): void
    {
        $this->postJson('/api/v1/whatsapp/ai/bot/simulate', [
            // missing wa_number_id and message
        ])->assertStatus(422);
    }

    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Bind a stub LlmClient into the container so the LlmDriverFactory returns it
     * instead of making real HTTP calls. Returns a deterministic bot JSON reply for
     * every generation request and a deterministic rewrite reply for Pass 1.
     */
    private function bindStubLlmDriver(): void
    {
        $stub = new class implements LlmClient {
            public function complete(LlmRequest $request): LlmResponse
            {
                // Pass 1 (rewrite) — maxTokens 150
                if ($request->maxTokens <= 150) {
                    $content = json_encode([
                        'standalone_query' => $request->messages[array_key_last($request->messages)]->content ?? 'query',
                        'intent'           => 'general',
                        'difficulty'       => 'easy',
                    ]);
                    return new LlmResponse(
                        content: $content,
                        tokensIn: 50,
                        tokensOut: 30,
                        latencyMs: 100,
                        model: 'stub-model',
                        provider: 'stub',
                    );
                }

                // Pass 2 (generation)
                $content = json_encode([
                    'reply'          => 'أهلاً وسهلاً، يسعدني مساعدتك.',
                    'used_sources'   => [],
                    'confidence'     => 90,
                    'needs_human'    => false,
                    'handoff_reason' => null,
                    'facts_update'   => [],
                    'next_question'  => null,
                ]);
                return new LlmResponse(
                    content: $content,
                    tokensIn: 200,
                    tokensOut: 50,
                    latencyMs: 300,
                    model: 'stub-model',
                    provider: 'stub',
                );
            }
        };

        $factory = $this->createMock(LlmDriverFactory::class);
        $factory->method('makeForTenant')->willReturn($stub);

        $this->app->instance(LlmDriverFactory::class, $factory);
    }
}
