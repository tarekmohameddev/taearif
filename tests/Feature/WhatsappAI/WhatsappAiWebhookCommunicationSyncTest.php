<?php

declare(strict_types=1);

namespace Tests\Feature\WhatsappAI;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Models\WaConversationState;
use App\Models\WaNumber;
use App\Models\WhatsappUser;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Modules\WhatsappAI\Entities\WhatsappConversation;
use Modules\WhatsappAI\Entities\WhatsappMessage;
use Modules\WhatsappAI\Jobs\ProcessConversation;
use Modules\WhatsappAI\Jobs\TranscribeAudio;
use Tests\TestCase;

class WhatsappAiWebhookCommunicationSyncTest extends TestCase
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
            'wa_numbers',
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
            'tenant_id' => null,
            'rbac_version' => (int) config('rbac.version', 1),
            'rbac_seeded_at' => now(),
        ]);
    }

    private function createWhatsappUser(User $tenant, string $phoneId = 'phone_test_123'): WhatsappUser
    {
        return WhatsappUser::create([
            'user_id' => $tenant->id,
            'phone_id' => $phoneId,
            'number' => '+966501111111',
            'status' => 'active',
        ]);
    }

    private function webhookPayload(string $phoneId, string $customerPhone, string $messageId, string $body = 'Hello from test'): array
    {
        return [
            'entry' => [
                [
                    'id' => 'waba_test',
                    'changes' => [
                        [
                            'value' => [
                                'metadata' => [
                                    'phone_number_id' => $phoneId,
                                    'display_phone_number' => '+966501111111',
                                ],
                                'contacts' => [
                                    ['profile' => ['name' => 'Test Customer']],
                                ],
                                'messages' => [
                                    [
                                        'id' => $messageId,
                                        'from' => $customerPhone,
                                        'type' => 'text',
                                        'text' => ['body' => $body],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    private function multiMessageWebhookPayload(string $phoneId, string $customerPhone, array $messages): array
    {
        return [
            'entry' => [
                [
                    'id' => 'waba_test',
                    'changes' => [
                        [
                            'value' => [
                                'metadata' => [
                                    'phone_number_id' => $phoneId,
                                    'display_phone_number' => '+966501111111',
                                ],
                                'messages' => $messages,
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /** @test */
    public function whatsapp_ai_webhook_syncs_into_communication_v1_tables(): void
    {
        $this->requireTables();
        Queue::fake();

        $tenant = $this->createTenant();
        $phoneId = 'phone_sync_' . uniqid();
        $this->createWhatsappUser($tenant, $phoneId);

        WaNumber::create([
            'user_id' => $tenant->id,
            'provider' => 'meta',
            'phone_number' => '+966501111111',
            'phone_number_id' => $phoneId,
            'name' => 'Main',
            'status' => 'active',
        ]);

        $customerPhone = '966509' . random_int(1000000, 9999999);
        $messageId = 'wamid.sync.' . uniqid();

        $response = $this->postJson('/api/whatsapp-ai/webhook', $this->webhookPayload($phoneId, $customerPhone, $messageId));
        $response->assertOk()->assertJsonPath('status', 'stored');

        $this->assertDatabaseHas('whatsapp_conversations', [
            'user_id' => $tenant->id,
            'customer_phone' => $customerPhone,
        ]);

        $aiConversation = WhatsappConversation::where('user_id', $tenant->id)
            ->where('customer_phone', $customerPhone)
            ->first();
        $this->assertNotNull($aiConversation);

        $this->assertDatabaseHas('whatsapp_messages', [
            'conversation_id' => $aiConversation->id,
            'whatsapp_message_id' => $messageId,
        ]);

        $conversation = Conversation::where('user_id', $tenant->id)
            ->where('channel', 'whatsapp')
            ->where('external_party_identifier', '+' . ltrim($customerPhone, '+'))
            ->first();
        $this->assertNotNull($conversation);

        $this->assertDatabaseHas('messages', [
            'conversation_id' => $conversation->id,
            'user_id' => $tenant->id,
            'provider_message_id' => $messageId,
            'direction' => 'inbound',
        ]);

        $this->assertDatabaseHas('wa_conversation_states', [
            'conversation_id' => $conversation->id,
            'user_id' => $tenant->id,
        ]);

        Sanctum::actingAs($tenant);
        $list = $this->getJson('/api/v1/whatsapp/conversations');
        $list->assertOk();
        $this->assertGreaterThan(0, $list->json('data.pagination.total'));

        $messages = $this->getJson('/api/v1/whatsapp/conversations/' . $conversation->id . '/messages');
        $messages->assertOk();
        $this->assertNotEmpty($messages->json('data.data.messages'));

        Queue::assertPushed(ProcessConversation::class);
        Queue::assertNotPushed(TranscribeAudio::class);
    }

    /** @test */
    public function duplicate_webhook_does_not_duplicate_messages_or_increment_unread_twice(): void
    {
        $this->requireTables();
        Queue::fake();

        $tenant = $this->createTenant();
        $phoneId = 'phone_dup_' . uniqid();
        $this->createWhatsappUser($tenant, $phoneId);

        $customerPhone = '966508' . random_int(1000000, 9999999);
        $messageId = 'wamid.dup.' . uniqid();
        $payload = $this->webhookPayload($phoneId, $customerPhone, $messageId);

        $this->postJson('/api/whatsapp-ai/webhook', $payload)->assertOk();
        $this->postJson('/api/whatsapp-ai/webhook', $payload)->assertOk();

        $this->assertSame(1, WhatsappMessage::where('whatsapp_message_id', $messageId)->count());
        $this->assertSame(1, Message::where('provider_message_id', $messageId)->where('user_id', $tenant->id)->count());

        $conversation = Conversation::where('user_id', $tenant->id)
            ->where('channel', 'whatsapp')
            ->first();
        $this->assertNotNull($conversation);

        $state = WaConversationState::where('conversation_id', $conversation->id)->first();
        $this->assertNotNull($state);
        $this->assertSame(1, (int) $state->unread_count);

        Queue::assertPushed(ProcessConversation::class, 1);
    }

    /** @test */
    public function messages_endpoint_rejects_non_whatsapp_conversation_channel(): void
    {
        $this->requireTables();

        $tenant = $this->createTenant();
        $otherTenant = $this->createTenant();

        $emailConversation = Conversation::create([
            'user_id' => $tenant->id,
            'channel' => 'email',
            'external_party_identifier' => 'user@example.com',
            'last_message_at' => now(),
        ]);

        Sanctum::actingAs($tenant);
        $this->getJson('/api/v1/whatsapp/conversations/' . $emailConversation->id . '/messages')
            ->assertStatus(404);

        $whatsappConversation = Conversation::create([
            'user_id' => $otherTenant->id,
            'channel' => 'whatsapp',
            'external_party_identifier' => '+966509999999',
            'last_message_at' => now(),
        ]);

        $this->getJson('/api/v1/whatsapp/conversations/' . $whatsappConversation->id . '/messages')
            ->assertStatus(404);
    }

    /** @test */
    public function webhook_processes_all_messages_in_payload(): void
    {
        $this->requireTables();
        Queue::fake();

        $tenant = $this->createTenant();
        $phoneId = 'phone_multi_' . uniqid();
        $this->createWhatsappUser($tenant, $phoneId);

        $customerPhone = '966505' . random_int(1000000, 9999999);
        $firstId = 'wamid.multi.first.' . uniqid();
        $secondId = 'wamid.multi.second.' . uniqid();

        $response = $this->postJson('/api/whatsapp-ai/webhook', $this->multiMessageWebhookPayload($phoneId, $customerPhone, [
            [
                'id' => $firstId,
                'from' => $customerPhone,
                'type' => 'text',
                'text' => ['body' => 'First message'],
            ],
            [
                'id' => $secondId,
                'from' => $customerPhone,
                'type' => 'text',
                'text' => ['body' => 'Second message'],
            ],
        ]));

        $response->assertOk()->assertJsonPath('processed', 2);

        $this->assertSame(2, WhatsappMessage::whereIn('whatsapp_message_id', [$firstId, $secondId])->count());
        $this->assertSame(2, Message::where('user_id', $tenant->id)
            ->whereIn('provider_message_id', [$firstId, $secondId])
            ->count());

        Queue::assertPushed(ProcessConversation::class, 2);
    }

    /** @test */
    public function conversation_list_excludes_states_for_non_whatsapp_conversations(): void
    {
        $this->requireTables();

        $tenant = $this->createTenant();

        $whatsappConversation = Conversation::create([
            'user_id' => $tenant->id,
            'channel' => 'whatsapp',
            'external_party_identifier' => '+966501111111',
            'last_message_at' => now(),
        ]);
        WaConversationState::create([
            'conversation_id' => $whatsappConversation->id,
            'user_id' => $tenant->id,
            'status' => 'active',
            'is_starred' => false,
            'unread_count' => 0,
            'last_message_time' => now(),
        ]);

        $emailConversation = Conversation::create([
            'user_id' => $tenant->id,
            'channel' => 'email',
            'external_party_identifier' => 'customer@example.com',
            'last_message_at' => now(),
        ]);
        WaConversationState::create([
            'conversation_id' => $emailConversation->id,
            'user_id' => $tenant->id,
            'status' => 'active',
            'is_starred' => false,
            'unread_count' => 0,
            'last_message_time' => now()->addMinute(),
        ]);

        Sanctum::actingAs($tenant);
        $response = $this->getJson('/api/v1/whatsapp/conversations');
        $response->assertOk();

        $this->assertSame(1, (int) $response->json('data.pagination.total'));
    }
}
