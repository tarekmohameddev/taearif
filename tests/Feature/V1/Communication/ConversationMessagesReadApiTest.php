<?php

declare(strict_types=1);

namespace Tests\Feature\V1\Communication;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ConversationMessagesReadApiTest extends TestCase
{
    use DatabaseTransactions;

    private function requireCommunicationTables(): void
    {
        if (!Schema::hasTable('conversations') || !Schema::hasTable('messages')) {
            $this->markTestSkipped('conversations and messages tables required.');
        }
    }

    private function createTenantUser(): User
    {
        return User::factory()->create([
            'account_type' => 'tenant',
            'tenant_id' => null,
            'rbac_version' => (int) config('rbac.version', 1),
            'rbac_seeded_at' => now(),
        ]);
    }

    /** @test */
    public function messages_index_returns_tenant_scoped_paginated_messages(): void
    {
        $this->requireCommunicationTables();
        $tenant = $this->createTenantUser();
        Sanctum::actingAs($tenant);

        $conversation = Conversation::create([
            'user_id' => $tenant->id,
            'channel' => 'whatsapp',
            'external_party_identifier' => '+966501234567',
            'last_message_at' => now(),
        ]);

        Message::create([
            'conversation_id' => $conversation->id,
            'user_id' => $tenant->id,
            'content' => 'Hello',
            'direction' => 'inbound',
            'status' => 'received',
            'provider_message_id' => 'wamid.test1',
            'meta' => \json_encode([]),
        ]);

        $res = $this->getJson('/api/v1/conversations/' . $conversation->id . '/messages');

        $res->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.pagination.current_page', 1);
        $messages = $res->json('data.messages');
        $this->assertIsArray($messages);
        $this->assertGreaterThanOrEqual(1, count($messages));
    }

    /** @test */
    public function messages_index_returns_404_when_conversation_not_owned(): void
    {
        $this->requireCommunicationTables();
        $tenantA = $this->createTenantUser();
        $tenantB = $this->createTenantUser();

        $conversation = Conversation::create([
            'user_id' => $tenantB->id,
            'channel' => 'whatsapp',
            'external_party_identifier' => '+966501234567',
            'last_message_at' => now(),
        ]);

        Sanctum::actingAs($tenantA);
        $res = $this->getJson('/api/v1/conversations/' . $conversation->id . '/messages');
        $res->assertNotFound();
    }
}
