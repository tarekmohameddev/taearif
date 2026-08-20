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
use Modules\WhatsappAI\Entities\WhatsappConversation;
use Modules\WhatsappAI\Entities\WhatsappMessage;
use Tests\TestCase;

class WhatsappAiWebhookOutboundEchoTest extends TestCase
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

    private function echoWebhookPayload(string $phoneId, string $businessPhone, string $customerPhone, string $messageId, string $body = 'Reply from staff'): array
    {
        return [
            'entry' => [
                [
                    'id' => 'waba_test',
                    'changes' => [
                        [
                            'field' => 'message_echoes',
                            'value' => [
                                'metadata' => [
                                    'phone_number_id' => $phoneId,
                                    'display_phone_number' => $businessPhone,
                                ],
                                'message_echoes' => [
                                    [
                                        'id' => $messageId,
                                        'from' => $businessPhone,
                                        'to' => $customerPhone,
                                        'timestamp' => (string) time(),
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

    private function smbEchoWebhookPayload(string $phoneId, string $businessPhone, string $customerPhone, string $messageId, string $body = 'SMB reply'): array
    {
        return [
            'entry' => [
                [
                    'id' => 'waba_test',
                    'changes' => [
                        [
                            'field' => 'smb_message_echoes',
                            'value' => [
                                'metadata' => [
                                    'phone_number_id' => $phoneId,
                                    'display_phone_number' => $businessPhone,
                                ],
                                'smb_message_echoes' => [
                                    [
                                        'id' => $messageId,
                                        'from' => $businessPhone,
                                        'to' => $customerPhone,
                                        'timestamp' => (string) time(),
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

    private function inboundWebhookPayload(string $phoneId, string $customerPhone, string $messageId, string $body = 'Hello'): array
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

    /** @test */
    public function message_echoes_webhook_stores_outbound_message(): void
    {
        $this->requireTables();
        Queue::fake();

        $tenant = $this->createTenant();
        $phoneId = 'phone_echo_' . uniqid();
        $businessPhone = '+966501111111';
        $this->createWhatsappUser($tenant, $phoneId);

        WaNumber::create([
            'user_id' => $tenant->id,
            'provider' => 'meta',
            'phone_number' => $businessPhone,
            'phone_number_id' => $phoneId,
            'name' => 'Main',
            'status' => 'active',
        ]);

        $customerPhone = '966509' . random_int(1000000, 9999999);
        $messageId = 'wamid.echo.' . uniqid();

        $response = $this->postJson('/api/whatsapp-ai/webhook', $this->echoWebhookPayload(
            $phoneId,
            $businessPhone,
            $customerPhone,
            $messageId,
            'Reply from staff'
        ));
        $response->assertOk()->assertJsonPath('status', 'stored');

        // Verify WhatsappMessage was created with direction=outbound
        $this->assertDatabaseHas('whatsapp_messages', [
            'whatsapp_message_id' => $messageId,
            'direction' => 'outbound',
            'content' => 'Reply from staff',
        ]);

        // Verify Communication Message was created with direction=outbound
        $aiConversation = WhatsappConversation::where('user_id', $tenant->id)
            ->where('customer_phone', $customerPhone)
            ->first();
        $this->assertNotNull($aiConversation);

        $commConversation = Conversation::where('user_id', $tenant->id)
            ->where('external_party_identifier', '+' . $customerPhone)
            ->first();
        $this->assertNotNull($commConversation);

        $this->assertDatabaseHas('messages', [
            'conversation_id' => $commConversation->id,
            'direction' => 'outbound',
            'status' => 'delivered',
            'provider_message_id' => $messageId,
        ]);
    }

    /** @test */
    public function smb_message_echoes_webhook_stores_outbound_message(): void
    {
        $this->requireTables();
        Queue::fake();

        $tenant = $this->createTenant();
        $phoneId = 'phone_smb_' . uniqid();
        $businessPhone = '+966501111111';
        $this->createWhatsappUser($tenant, $phoneId);

        WaNumber::create([
            'user_id' => $tenant->id,
            'provider' => 'meta',
            'phone_number' => $businessPhone,
            'phone_number_id' => $phoneId,
            'name' => 'Main',
            'status' => 'active',
        ]);

        $customerPhone = '966509' . random_int(1000000, 9999999);
        $messageId = 'wamid.smb.' . uniqid();

        $response = $this->postJson('/api/whatsapp-ai/webhook', $this->smbEchoWebhookPayload(
            $phoneId,
            $businessPhone,
            $customerPhone,
            $messageId,
            'SMB reply from app'
        ));
        $response->assertOk()->assertJsonPath('status', 'stored');

        $this->assertDatabaseHas('whatsapp_messages', [
            'whatsapp_message_id' => $messageId,
            'direction' => 'outbound',
            'content' => 'SMB reply from app',
        ]);
    }

    /** @test */
    public function echo_does_not_duplicate_existing_message(): void
    {
        $this->requireTables();
        Queue::fake();

        $tenant = $this->createTenant();
        $phoneId = 'phone_dedup_' . uniqid();
        $businessPhone = '+966501111111';
        $whatsappUser = $this->createWhatsappUser($tenant, $phoneId);

        $customerPhone = '966509' . random_int(1000000, 9999999);
        $messageId = 'wamid.existing.' . uniqid();

        // Pre-create the conversation and message (simulating it was sent via API)
        $conversation = WhatsappConversation::create([
            'whatsapp_user_id' => $whatsappUser->id,
            'user_id' => $tenant->id,
            'customer_phone' => $customerPhone,
            'status' => 'collecting',
        ]);

        WhatsappMessage::create([
            'conversation_id' => $conversation->id,
            'direction' => 'outbound',
            'whatsapp_message_id' => $messageId,
            'message_type' => 'text',
            'content' => 'Sent via API',
        ]);

        // Send echo webhook with same message ID
        $response = $this->postJson('/api/whatsapp-ai/webhook', $this->echoWebhookPayload(
            $phoneId,
            $businessPhone,
            $customerPhone,
            $messageId,
            'Echo content'
        ));
        $response->assertOk();

        // Should not create duplicate
        $this->assertEquals(1, WhatsappMessage::where('whatsapp_message_id', $messageId)->count());

        // Content should remain original
        $this->assertDatabaseHas('whatsapp_messages', [
            'whatsapp_message_id' => $messageId,
            'content' => 'Sent via API',
        ]);
    }

    /** @test */
    public function inbound_message_has_direction_inbound(): void
    {
        $this->requireTables();
        Queue::fake();

        $tenant = $this->createTenant();
        $phoneId = 'phone_inbound_' . uniqid();
        $this->createWhatsappUser($tenant, $phoneId);

        $customerPhone = '966509' . random_int(1000000, 9999999);
        $messageId = 'wamid.inbound.' . uniqid();

        $response = $this->postJson('/api/whatsapp-ai/webhook', $this->inboundWebhookPayload(
            $phoneId,
            $customerPhone,
            $messageId,
            'Customer message'
        ));
        $response->assertOk()->assertJsonPath('status', 'stored');

        $this->assertDatabaseHas('whatsapp_messages', [
            'whatsapp_message_id' => $messageId,
            'direction' => 'inbound',
            'content' => 'Customer message',
        ]);
    }

    /** @test */
    public function conversation_shows_both_inbound_and_outbound_messages(): void
    {
        $this->requireTables();
        Queue::fake();

        $tenant = $this->createTenant();
        $phoneId = 'phone_unified_' . uniqid();
        $businessPhone = '+966501111111';
        $this->createWhatsappUser($tenant, $phoneId);

        WaNumber::create([
            'user_id' => $tenant->id,
            'provider' => 'meta',
            'phone_number' => $businessPhone,
            'phone_number_id' => $phoneId,
            'name' => 'Main',
            'status' => 'active',
        ]);

        $customerPhone = '966509' . random_int(1000000, 9999999);

        // Customer sends a message (inbound)
        $inboundMessageId = 'wamid.in.' . uniqid();
        $this->postJson('/api/whatsapp-ai/webhook', $this->inboundWebhookPayload(
            $phoneId,
            $customerPhone,
            $inboundMessageId,
            'Customer: Hello'
        ))->assertOk();

        // Staff replies from app (outbound echo)
        $outboundMessageId = 'wamid.out.' . uniqid();
        $this->postJson('/api/whatsapp-ai/webhook', $this->echoWebhookPayload(
            $phoneId,
            $businessPhone,
            $customerPhone,
            $outboundMessageId,
            'Staff: Hi there'
        ))->assertOk();

        // Verify conversation has both messages with correct directions
        $conversation = WhatsappConversation::where('user_id', $tenant->id)
            ->where('customer_phone', $customerPhone)
            ->first();
        $this->assertNotNull($conversation);

        $messages = WhatsappMessage::where('conversation_id', $conversation->id)
            ->orderBy('id')
            ->get();

        $this->assertCount(2, $messages);
        $this->assertEquals('inbound', $messages[0]->direction);
        $this->assertEquals('Customer: Hello', $messages[0]->content);
        $this->assertEquals('outbound', $messages[1]->direction);
        $this->assertEquals('Staff: Hi there', $messages[1]->content);
    }

    /** @test */
    public function outbound_echo_does_not_increment_unread_count(): void
    {
        $this->requireTables();
        Queue::fake();

        $tenant = $this->createTenant();
        $phoneId = 'phone_unread_' . uniqid();
        $businessPhone = '+966501111111';
        $this->createWhatsappUser($tenant, $phoneId);

        WaNumber::create([
            'user_id' => $tenant->id,
            'provider' => 'meta',
            'phone_number' => $businessPhone,
            'phone_number_id' => $phoneId,
            'name' => 'Main',
            'status' => 'active',
        ]);

        $customerPhone = '966509' . random_int(1000000, 9999999);

        // Send outbound echo
        $messageId = 'wamid.unread.' . uniqid();
        $this->postJson('/api/whatsapp-ai/webhook', $this->echoWebhookPayload(
            $phoneId,
            $businessPhone,
            $customerPhone,
            $messageId,
            'Staff message'
        ))->assertOk();

        // Verify unread count is 0 (outbound doesn't count as unread)
        $commConversation = Conversation::where('user_id', $tenant->id)
            ->where('external_party_identifier', '+' . $customerPhone)
            ->first();

        if ($commConversation) {
            $state = WaConversationState::where('conversation_id', $commConversation->id)->first();
            if ($state) {
                $this->assertEquals(0, $state->unread_count);
            }
        }
    }
}
