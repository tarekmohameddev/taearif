<?php

declare(strict_types=1);

namespace Tests\Feature\V1\Communication;

use App\Domain\Communication\Contracts\CommunicationService;
use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class WebhookInboundPersistenceTest extends TestCase
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
    public function record_inbound_message_returns_null_when_required_values_missing(): void
    {
        $this->requireCommunicationTables();
        $service = app(CommunicationService::class);

        $user = $this->createTenantUser();

        $this->assertNull($service->recordInboundMessage(0, '+966501234567', 'Hi', 'whatsapp', null, []));
        $this->assertNull($service->recordInboundMessage($user->id, '', 'Hi', 'whatsapp', null, []));
        $this->assertNull($service->recordInboundMessage($user->id, '+966501234567', '', 'whatsapp', null, []));
    }

    /** @test */
    public function record_inbound_message_returns_null_when_tenant_mapping_unresolved(): void
    {
        $this->requireCommunicationTables();
        $service = app(CommunicationService::class);

        $nonExistentUserId = 999999999;
        $result = $service->recordInboundMessage(
            $nonExistentUserId,
            '+966501234567',
            'Hello',
            'whatsapp',
            null,
            []
        );

        $this->assertNull($result);
    }

    /** @test */
    public function duplicate_provider_message_id_returns_existing_message_and_does_not_create_second_row(): void
    {
        $this->requireCommunicationTables();
        $service = app(CommunicationService::class);
        $user = $this->createTenantUser();
        $providerId = 'wamid.dup-' . uniqid();

        $first = $service->recordInboundMessage(
            (int) $user->id,
            '+966501234567',
            'First',
            'whatsapp',
            $providerId,
            ['source' => 'test']
        );
        $this->assertNotNull($first);
        $this->assertInstanceOf(Message::class, $first);

        $second = $service->recordInboundMessage(
            (int) $user->id,
            '+966501234567',
            'Second',
            'whatsapp',
            $providerId,
            ['source' => 'test']
        );
        $this->assertNotNull($second);
        $this->assertSame($first->id, $second->id);

        $count = Message::where('provider_message_id', $providerId)->where('user_id', $user->id)->count();
        $this->assertSame(1, $count);
    }

    /** @test */
    public function valid_record_inbound_message_persists_conversation_and_message(): void
    {
        $this->requireCommunicationTables();
        $service = app(CommunicationService::class);
        $user = $this->createTenantUser();

        $msg = $service->recordInboundMessage(
            (int) $user->id,
            '+966501234567',
            'Hello',
            'whatsapp',
            'wamid.valid-' . uniqid(),
            ['source' => 'evolution_webhook']
        );

        $this->assertNotNull($msg);
        $this->assertInstanceOf(Message::class, $msg);
        $this->assertSame('inbound', $msg->direction);
        $this->assertSame('received', $msg->status);
        $this->assertSame('Hello', $msg->content);
        $this->assertDatabaseHas('conversations', [
            'user_id' => $user->id,
            'channel' => 'whatsapp',
        ]);
        $this->assertStringContainsString('966501234567', $msg->conversation->external_party_identifier ?? '');
    }
}
