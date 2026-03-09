<?php

declare(strict_types=1);

namespace Tests\Feature\V1\Communication;

use App\Domain\Communication\Contracts\CommunicationService;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Models\WaConversationState;
use App\Models\WaNumber;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Log;
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

    private function requireWhatsAppStateTables(): void
    {
        foreach (['conversations', 'messages', 'wa_numbers', 'wa_conversation_states'] as $table) {
            if (! Schema::hasTable($table)) {
                $this->markTestSkipped("{$table} table required.");
            }
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

    /** @test */
    public function inbound_backfills_wa_number_id_when_state_exists_with_null_mapping(): void
    {
        $this->requireWhatsAppStateTables();
        $service = app(CommunicationService::class);
        $user = $this->createTenantUser();

        $waNumber = WaNumber::create([
            'user_id' => $user->id,
            'provider' => 'meta',
            'phone_number' => '+966500001111',
            'phone_number_id' => 'pnid-backfill-' . uniqid(),
            'name' => 'Backfill Number',
            'status' => 'active',
        ]);

        $conversation = Conversation::create([
            'user_id' => $user->id,
            'channel' => 'whatsapp',
            'external_party_identifier' => '+966512300001',
            'last_message_at' => now(),
        ]);

        WaConversationState::create([
            'conversation_id' => $conversation->id,
            'user_id' => $user->id,
            'wa_number_id' => null,
            'status' => 'active',
            'is_starred' => false,
            'unread_count' => 0,
        ]);

        $message = $service->recordInboundMessage(
            (int) $user->id,
            '+966512300001',
            'Backfill test',
            'whatsapp',
            'wamid.backfill-' . uniqid(),
            ['source' => 'test', 'wa_number_id' => (int) $waNumber->id]
        );

        $this->assertNotNull($message);
        $this->assertDatabaseHas('wa_conversation_states', [
            'conversation_id' => $conversation->id,
            'wa_number_id' => $waNumber->id,
        ]);
    }

    /** @test */
    public function inbound_mismatch_keeps_existing_wa_number_id_and_logs_conflict(): void
    {
        $this->requireWhatsAppStateTables();
        $service = app(CommunicationService::class);
        $user = $this->createTenantUser();

        $existingWaNumber = WaNumber::create([
            'user_id' => $user->id,
            'provider' => 'meta',
            'phone_number' => '+966500001112',
            'phone_number_id' => 'pnid-existing-' . uniqid(),
            'name' => 'Existing Number',
            'status' => 'active',
        ]);
        $incomingWaNumber = WaNumber::create([
            'user_id' => $user->id,
            'provider' => 'meta',
            'phone_number' => '+966500001113',
            'phone_number_id' => 'pnid-incoming-' . uniqid(),
            'name' => 'Incoming Number',
            'status' => 'active',
        ]);

        $conversation = Conversation::create([
            'user_id' => $user->id,
            'channel' => 'whatsapp',
            'external_party_identifier' => '+966512300002',
            'last_message_at' => now(),
        ]);

        $state = WaConversationState::create([
            'conversation_id' => $conversation->id,
            'user_id' => $user->id,
            'wa_number_id' => $existingWaNumber->id,
            'status' => 'active',
            'is_starred' => false,
            'unread_count' => 0,
        ]);

        Log::spy();

        $message = $service->recordInboundMessage(
            (int) $user->id,
            '+966512300002',
            'Mismatch test',
            'whatsapp',
            'wamid.mismatch-' . uniqid(),
            ['source' => 'test', 'wa_number_id' => (int) $incomingWaNumber->id]
        );

        $this->assertNotNull($message);
        $state->refresh();
        $this->assertSame((int) $existingWaNumber->id, (int) $state->wa_number_id);
        Log::shouldHaveReceived('warning')
            ->withArgs(function (string $message, array $context) use ($existingWaNumber, $incomingWaNumber): bool {
                return $message === 'communication.whatsapp.wa_number_mapping'
                    && ($context['outcome'] ?? null) === 'mismatch_kept_existing'
                    && (int) ($context['existing_wa_number_id'] ?? 0) === (int) $existingWaNumber->id
                    && (int) ($context['incoming_wa_number_id'] ?? 0) === (int) $incomingWaNumber->id;
            })
            ->once();
    }

    /** @test */
    public function unresolved_mapping_does_not_overwrite_existing_wa_number_id(): void
    {
        $this->requireWhatsAppStateTables();
        $service = app(CommunicationService::class);
        $user = $this->createTenantUser();

        $existingWaNumber = WaNumber::create([
            'user_id' => $user->id,
            'provider' => 'meta',
            'phone_number' => '+966500001114',
            'phone_number_id' => 'pnid-unresolved-' . uniqid(),
            'name' => 'Unresolved Number',
            'status' => 'active',
        ]);

        $conversation = Conversation::create([
            'user_id' => $user->id,
            'channel' => 'whatsapp',
            'external_party_identifier' => '+966512300003',
            'last_message_at' => now(),
        ]);

        $state = WaConversationState::create([
            'conversation_id' => $conversation->id,
            'user_id' => $user->id,
            'wa_number_id' => $existingWaNumber->id,
            'status' => 'active',
            'is_starred' => false,
            'unread_count' => 0,
        ]);

        $message = $service->recordInboundMessage(
            (int) $user->id,
            '+966512300003',
            'Unresolved test',
            'whatsapp',
            'wamid.unresolved-' . uniqid(),
            ['source' => 'test']
        );

        $this->assertNotNull($message);
        $state->refresh();
        $this->assertSame((int) $existingWaNumber->id, (int) $state->wa_number_id);
    }
}
