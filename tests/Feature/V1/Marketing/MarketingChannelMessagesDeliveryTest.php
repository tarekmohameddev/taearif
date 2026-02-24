<?php

declare(strict_types=1);

namespace Tests\Feature\V1\Marketing;

use App\Models\Api\markting\MarketingChannel;
use App\Models\Api\markting\MarketingChannelMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MarketingChannelMessagesDeliveryTest extends TestCase
{
    use DatabaseTransactions;

    private function requireTables(): void
    {
        foreach (['marketing_channels', 'marketing_channel_messages'] as $table) {
            if (! Schema::hasTable($table)) {
                $this->markTestSkipped("{$table} table required.");
            }
        }
    }

    /** Create a marketing channel for testing (avoids auto_increment issues on id across environments). */
    private function createTestChannel(User $user): MarketingChannel
    {
        $id = (int) (900000 + random_int(1, 99999));
        $base = [
            'id' => $id,
            'user_id' => $user->id,
            'name' => 'Test WhatsApp',
            'description' => null,
            'type' => 'whatsapp',
            'number' => '1234567890',
            'business_id' => null,
            'phone_id' => null,
            'access_token' => null,
            'is_verified' => 1,
            'is_connected' => 1,
            'sent_messages_count' => 0,
            'received_messages_count' => 0,
            'additional_settings' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ];
        if (Schema::hasColumn('marketing_channels', 'crm_integration_enabled')) {
            $base['crm_integration_enabled'] = 0;
            $base['appointment_system_integration_enabled'] = 0;
            $base['integration_settings'] = null;
        }
        if (Schema::hasColumn('marketing_channels', 'customers_page_integration_enabled')) {
            $base['customers_page_integration_enabled'] = 0;
            $base['rental_page_integration_enabled'] = 0;
        }
        DB::table('marketing_channels')->insert($base);

        return MarketingChannel::find($id);
    }

    /** @test */
    public function get_messages_returns_paginated_list_for_authenticated_user(): void
    {
        $this->requireTables();

        $user = User::factory()->create(['account_type' => 'tenant', 'tenant_id' => null]);
        $channel = $this->createTestChannel($user);

        MarketingChannelMessage::create([
            'user_id' => $user->id,
            'channel_id' => $channel->id,
            'customer_id' => null,
            'recipient_phone' => '+966500000001',
            'recipient_name' => 'Test Customer',
            'message_content' => 'Hello',
            'message_type' => 'text',
            'status' => 'sent',
            'provider_message_id' => 'wamid.test1',
            'sent_at' => now(),
            'credits_used' => 1,
        ]);

        Sanctum::actingAs($user);
        $response = $this->getJson('/api/v1/marketing/channels/messages');

        $response->assertOk()
            ->assertJsonPath('message', 'Messages retrieved successfully');
        $data = $response->json('data');
        $this->assertNotNull($data);
        $this->assertArrayHasKey('data', $data);
        $this->assertCount(1, $data['data']);
        $this->assertSame('sent', $data['data'][0]['status']);
        $this->assertSame('wamid.test1', $data['data'][0]['provider_message_id']);
    }

    /** @test */
    public function get_messages_requires_authentication(): void
    {
        $this->requireTables();
        $this->getJson('/api/v1/marketing/channels/messages')->assertUnauthorized();
    }

    /** @test */
    public function get_message_stats_returns_aggregates_for_authenticated_user(): void
    {
        $this->requireTables();

        $user = User::factory()->create(['account_type' => 'tenant', 'tenant_id' => null]);
        $channel = $this->createTestChannel($user);

        MarketingChannelMessage::create([
            'user_id' => $user->id,
            'channel_id' => $channel->id,
            'recipient_phone' => '+966500000001',
            'message_content' => 'Hi',
            'status' => 'sent',
            'provider_message_id' => 'wamid.s1',
            'sent_at' => now(),
            'credits_used' => 1,
        ]);
        MarketingChannelMessage::create([
            'user_id' => $user->id,
            'channel_id' => $channel->id,
            'recipient_phone' => '+966500000002',
            'message_content' => 'Hi',
            'status' => 'delivered',
            'provider_message_id' => 'wamid.d1',
            'sent_at' => now(),
            'delivered_at' => now(),
            'credits_used' => 1,
        ]);
        MarketingChannelMessage::create([
            'user_id' => $user->id,
            'channel_id' => $channel->id,
            'recipient_phone' => '+966500000003',
            'message_content' => 'Hi',
            'status' => 'read',
            'provider_message_id' => 'wamid.r1',
            'sent_at' => now(),
            'delivered_at' => now(),
            'read_at' => now(),
            'credits_used' => 1,
        ]);

        Sanctum::actingAs($user);
        $response = $this->getJson('/api/v1/marketing/channels/messages/stats');

        $response->assertOk()
            ->assertJsonPath('message', 'Message statistics retrieved successfully');
        $data = $response->json('data');
        $this->assertSame(3, (int) $data['total']);
        $this->assertSame(1, (int) $data['sent']);
        $this->assertSame(1, (int) $data['delivered']);
        $this->assertSame(1, (int) $data['read']);
        $this->assertArrayHasKey('delivery_rate', $data);
        $this->assertArrayHasKey('read_rate', $data);
    }

    /** @test */
    public function get_message_stats_requires_authentication(): void
    {
        $this->requireTables();
        $this->getJson('/api/v1/marketing/channels/messages/stats')->assertUnauthorized();
    }

    /** @test */
    public function webhook_delivery_updates_message_status_to_delivered(): void
    {
        $this->requireTables();

        $user = User::factory()->create(['account_type' => 'tenant', 'tenant_id' => null]);
        $channel = $this->createTestChannel($user);

        $message = MarketingChannelMessage::create([
            'user_id' => $user->id,
            'channel_id' => $channel->id,
            'recipient_phone' => '+966500000099',
            'recipient_name' => 'Webhook Customer',
            'message_content' => 'Test',
            'message_type' => 'text',
            'status' => 'sent',
            'provider_message_id' => 'wamid.delivery-test-123',
            'sent_at' => now(),
            'credits_used' => 1,
        ]);

        $payload = [
            'object' => 'whatsapp_business_account',
            'entry' => [
                [
                    'id' => '123',
                    'changes' => [
                        [
                            'field' => 'message_deliveries',
                            'value' => [
                                'metadata' => ['phone_number_id' => '123'],
                                'statuses' => [
                                    [
                                        'id' => 'wamid.delivery-test-123',
                                        'status' => 'delivered',
                                        'timestamp' => (string) time(),
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        Sanctum::actingAs($user);
        $this->postJson('/api/v1/marketing/webhooks/whatsapp', $payload)->assertOk();

        $message->refresh();
        $this->assertSame('delivered', $message->status);
        $this->assertNotNull($message->delivered_at);
    }

    /** @test */
    public function webhook_read_updates_message_status_to_read(): void
    {
        $this->requireTables();

        $user = User::factory()->create(['account_type' => 'tenant', 'tenant_id' => null]);
        $channel = $this->createTestChannel($user);

        $message = MarketingChannelMessage::create([
            'user_id' => $user->id,
            'channel_id' => $channel->id,
            'recipient_phone' => '+966500000098',
            'recipient_name' => 'Read Customer',
            'message_content' => 'Test',
            'message_type' => 'text',
            'status' => 'delivered',
            'provider_message_id' => 'wamid.read-test-456',
            'sent_at' => now(),
            'delivered_at' => now(),
            'credits_used' => 1,
        ]);

        $payload = [
            'object' => 'whatsapp_business_account',
            'entry' => [
                [
                    'id' => '123',
                    'changes' => [
                        [
                            'field' => 'message_reads',
                            'value' => [
                                'metadata' => ['phone_number_id' => '123'],
                                'statuses' => [
                                    [
                                        'id' => 'wamid.read-test-456',
                                        'status' => 'read',
                                        'timestamp' => (string) time(),
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        Sanctum::actingAs($user);
        $this->postJson('/api/v1/marketing/webhooks/whatsapp', $payload)->assertOk();

        $message->refresh();
        $this->assertSame('read', $message->status);
        $this->assertNotNull($message->read_at);
    }
}
