<?php

declare(strict_types=1);

namespace Tests\Feature\V1\WhatsApp;

use App\Models\Api\marketing\MarketingChannel;
use App\Models\Conversation;
use App\Models\User;
use App\Models\WaConversationState;
use App\Models\WaNumber;
use App\Models\WhatsappUser;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class WhatsAppWebhookWaNumberMappingTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('communication.whatsapp.provider', 'meta');
    }

    private function createTenant(): User
    {
        return User::factory()->create([
            'account_type' => 'tenant',
            'tenant_id' => null,
        ]);
    }

    private function requireTables(array $tables): void
    {
        foreach ($tables as $table) {
            if (! Schema::hasTable($table)) {
                $this->markTestSkipped("{$table} table required.");
            }
        }
    }

    private function createMarketingChannel(User $user, string $phoneId): MarketingChannel
    {
        $channel = [
            'user_id' => $user->id,
            'name' => 'Test WhatsApp',
            'description' => null,
            'type' => 'whatsapp',
            'number' => '+966500009999',
            'business_id' => null,
            'phone_id' => $phoneId,
            'access_token' => null,
            'is_verified' => 1,
            'is_connected' => 1,
            'sent_messages_count' => 0,
            'received_messages_count' => 0,
            'additional_settings' => null,
        ];

        if (Schema::hasColumn('marketing_channels', 'crm_integration_enabled')) {
            $channel['crm_integration_enabled'] = 0;
            $channel['appointment_system_integration_enabled'] = 0;
            $channel['integration_settings'] = null;
        }
        if (Schema::hasColumn('marketing_channels', 'customers_page_integration_enabled')) {
            $channel['customers_page_integration_enabled'] = 0;
            $channel['rental_page_integration_enabled'] = 0;
        }

        return MarketingChannel::create($channel);
    }

    /** @test */
    public function v1_webhook_incoming_maps_wa_number_id_into_conversation_state(): void
    {
        $this->requireTables(['users', 'wa_numbers', 'conversations', 'wa_conversation_states', 'messages']);

        $tenant = $this->createTenant();
        $waNumber = WaNumber::create([
            'user_id' => $tenant->id,
            'provider' => 'meta',
            'phone_number' => '+966500000221',
            'phone_number_id' => 'pnid-v1-' . uniqid(),
            'name' => 'V1 Webhook Number',
            'status' => 'active',
        ]);

        $payload = [
            'entry' => [
                [
                    'id' => 'entry-1',
                    'changes' => [
                        [
                            'value' => [
                                'metadata' => [
                                    'phone_number_id' => $waNumber->phone_number_id,
                                    'display_phone_number' => $waNumber->phone_number,
                                ],
                                'messages' => [
                                    [
                                        'from' => '966512300010',
                                        'id' => 'wamid.v1.' . uniqid(),
                                        'text' => ['body' => 'Hello from v1 webhook'],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $this->postJson('/api/v1/whatsapp/webhook/incoming', $payload)->assertOk();

        $conversation = Conversation::query()
            ->where('user_id', $tenant->id)
            ->where('channel', 'whatsapp')
            ->where('external_party_identifier', '+966512300010')
            ->first();
        $this->assertNotNull($conversation);
        $this->assertDatabaseHas('wa_conversation_states', [
            'conversation_id' => $conversation->id,
            'wa_number_id' => $waNumber->id,
        ]);
    }

    /** @test */
    public function legacy_meta_webhook_maps_wa_number_id_into_conversation_state(): void
    {
        $this->requireTables(['users', 'wa_numbers', 'whatsapp_users', 'conversations', 'wa_conversation_states', 'messages', 'api_customers']);

        $tenant = $this->createTenant();
        $waNumber = WaNumber::create([
            'user_id' => $tenant->id,
            'provider' => 'meta',
            'phone_number' => '+966500000222',
            'phone_number_id' => 'pnid-legacy-' . uniqid(),
            'name' => 'Legacy Meta Number',
            'status' => 'active',
        ]);

        WhatsappUser::create([
            'user_id' => $tenant->id,
            'number' => $waNumber->phone_number,
            'phone_id' => $waNumber->phone_number_id,
            'status' => 'active',
            'request_status' => 'active',
        ]);

        $payload = [
            'entry' => [
                [
                    'changes' => [
                        [
                            'value' => [
                                'metadata' => [
                                    'display_phone_number' => $waNumber->phone_number,
                                    'phone_number_id' => $waNumber->phone_number_id,
                                ],
                                'contacts' => [
                                    ['profile' => ['name' => 'Legacy Contact']],
                                ],
                                'messages' => [
                                    [
                                        'from' => '966512300011',
                                        'id' => 'wamid.legacy.' . uniqid(),
                                        'text' => ['body' => 'Hello from legacy webhook'],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $response = $this->postJson('/api/whatsapp/webhook', $payload);
        $this->assertTrue(in_array($response->status(), [200, 201], true));

        $conversation = Conversation::query()
            ->where('user_id', $tenant->id)
            ->where('channel', 'whatsapp')
            ->where('external_party_identifier', '+966512300011')
            ->first();
        $this->assertNotNull($conversation);
        $this->assertDatabaseHas('wa_conversation_states', [
            'conversation_id' => $conversation->id,
            'wa_number_id' => $waNumber->id,
        ]);
    }

    /** @test */
    public function marketing_meta_webhook_maps_wa_number_id_into_conversation_state(): void
    {
        $this->requireTables(['users', 'wa_numbers', 'marketing_channels', 'conversations', 'wa_conversation_states', 'messages']);

        $tenant = $this->createTenant();
        $waNumber = WaNumber::create([
            'user_id' => $tenant->id,
            'provider' => 'meta',
            'phone_number' => '+966500000223',
            'phone_number_id' => 'pnid-marketing-' . uniqid(),
            'name' => 'Marketing Number',
            'status' => 'active',
        ]);
        $this->createMarketingChannel($tenant, (string) $waNumber->phone_number_id);

        $payload = [
            'object' => 'whatsapp_business_account',
            'entry' => [
                [
                    'id' => 'entry-1',
                    'changes' => [
                        [
                            'field' => 'messages',
                            'value' => [
                                'metadata' => [
                                    'phone_number_id' => $waNumber->phone_number_id,
                                    'display_phone_number' => $waNumber->phone_number,
                                ],
                                'messages' => [
                                    [
                                        'id' => 'wamid.marketing.' . uniqid(),
                                        'from' => '966512300012',
                                        'type' => 'text',
                                        'text' => ['body' => 'Hello from marketing webhook'],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $this->postJson('/api/v1/marketing/webhooks/whatsapp', $payload)->assertOk();

        $conversation = Conversation::query()
            ->where('user_id', $tenant->id)
            ->where('channel', 'whatsapp')
            ->where('external_party_identifier', '+966512300012')
            ->first();
        $this->assertNotNull($conversation);
        $state = WaConversationState::query()->where('conversation_id', $conversation->id)->first();
        $this->assertNotNull($state);
        $this->assertSame((int) $waNumber->id, (int) $state->wa_number_id);
    }
}
