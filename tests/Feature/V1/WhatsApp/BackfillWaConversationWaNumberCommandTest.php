<?php

declare(strict_types=1);

namespace Tests\Feature\V1\WhatsApp;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Models\WaConversationState;
use App\Models\WaNumber;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class BackfillWaConversationWaNumberCommandTest extends TestCase
{
    use DatabaseTransactions;

    private function requireTables(): void
    {
        foreach (['users', 'conversations', 'messages', 'wa_numbers', 'wa_conversation_states'] as $table) {
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
        ]);
    }

    private function createConversationStateWithMessage(User $tenant, array $meta, ?int $waNumberId = null): WaConversationState
    {
        $conversation = Conversation::create([
            'user_id' => $tenant->id,
            'channel' => 'whatsapp',
            'external_party_identifier' => '+9665' . random_int(10000000, 99999999),
            'last_message_at' => now(),
        ]);

        $state = WaConversationState::create([
            'conversation_id' => $conversation->id,
            'user_id' => $tenant->id,
            'wa_number_id' => $waNumberId,
            'status' => 'active',
            'is_starred' => false,
            'unread_count' => 0,
        ]);

        Message::create([
            'conversation_id' => $conversation->id,
            'user_id' => $tenant->id,
            'content' => 'Historical inbound',
            'direction' => 'inbound',
            'status' => 'received',
            'provider_message_id' => 'wamid.backfill.' . uniqid('', true),
            'meta' => $meta,
        ]);

        return $state;
    }

    /** @test */
    public function it_backfills_using_direct_meta_wa_number_id(): void
    {
        $this->requireTables();

        $tenant = $this->createTenant();
        $waNumber = WaNumber::create([
            'user_id' => $tenant->id,
            'provider' => 'meta',
            'phone_number' => '+966500010001',
            'phone_number_id' => 'pnid-direct-' . uniqid(),
            'name' => 'Direct Match',
            'status' => 'active',
        ]);

        $state = $this->createConversationStateWithMessage($tenant, [
            'wa_number_id' => $waNumber->id,
        ]);

        $this->artisan('communication:backfill-wa-conversation-wa-number')
            ->assertExitCode(0);

        $state->refresh();
        $this->assertSame((int) $waNumber->id, (int) $state->wa_number_id);
    }

    /** @test */
    public function it_backfills_using_display_phone_match(): void
    {
        $this->requireTables();

        $tenant = $this->createTenant();
        $waNumber = WaNumber::create([
            'user_id' => $tenant->id,
            'provider' => 'meta',
            'phone_number' => '+966500010002',
            'phone_number_id' => 'pnid-display-' . uniqid(),
            'name' => 'Display Match',
            'status' => 'active',
        ]);

        $state = $this->createConversationStateWithMessage($tenant, [
            'display_phone' => '966500010002',
        ]);

        $this->artisan('communication:backfill-wa-conversation-wa-number')
            ->assertExitCode(0);

        $state->refresh();
        $this->assertSame((int) $waNumber->id, (int) $state->wa_number_id);
    }

    /** @test */
    public function it_backfills_using_evolution_instance_match(): void
    {
        $this->requireTables();

        $tenant = $this->createTenant();
        $waNumber = WaNumber::create([
            'user_id' => $tenant->id,
            'provider' => 'evolution',
            'phone_number' => '+966500010003',
            'provider_account_id' => 'instance-backfill-' . uniqid(),
            'name' => 'Instance Match',
            'status' => 'active',
        ]);

        $state = $this->createConversationStateWithMessage($tenant, [
            'context' => ['instance' => $waNumber->provider_account_id],
        ]);

        $this->artisan('communication:backfill-wa-conversation-wa-number')
            ->assertExitCode(0);

        $state->refresh();
        $this->assertSame((int) $waNumber->id, (int) $state->wa_number_id);
    }

    /** @test */
    public function it_backfills_using_marketing_channel_id_match(): void
    {
        $this->requireTables();

        $tenant = $this->createTenant();
        $waNumber = WaNumber::create([
            'user_id' => $tenant->id,
            'provider' => 'meta',
            'phone_number' => '+966500010004',
            'phone_number_id' => 'pnid-channel-' . uniqid(),
            'marketing_channel_id' => 34567,
            'name' => 'Channel Match',
            'status' => 'active',
        ]);

        $state = $this->createConversationStateWithMessage($tenant, [
            'channel_id' => 34567,
        ]);

        $this->artisan('communication:backfill-wa-conversation-wa-number')
            ->assertExitCode(0);

        $state->refresh();
        $this->assertSame((int) $waNumber->id, (int) $state->wa_number_id);
    }

    /** @test */
    public function it_leaves_null_when_unresolved(): void
    {
        $this->requireTables();

        $tenant = $this->createTenant();
        $state = $this->createConversationStateWithMessage($tenant, [
            'source' => 'legacy',
        ]);

        $this->artisan('communication:backfill-wa-conversation-wa-number')
            ->assertExitCode(0);

        $state->refresh();
        $this->assertNull($state->wa_number_id);
    }

    /** @test */
    public function it_leaves_null_when_match_is_ambiguous(): void
    {
        $this->requireTables();

        $tenant = $this->createTenant();
        $instance = 'instance-ambiguous-' . uniqid();

        WaNumber::create([
            'user_id' => $tenant->id,
            'provider' => 'evolution',
            'phone_number' => '+966500010005',
            'provider_account_id' => $instance,
            'name' => 'Ambiguous 1',
            'status' => 'active',
        ]);
        WaNumber::create([
            'user_id' => $tenant->id,
            'provider' => 'evolution',
            'phone_number' => '+966500010006',
            'provider_account_id' => $instance,
            'name' => 'Ambiguous 2',
            'status' => 'active',
        ]);

        $state = $this->createConversationStateWithMessage($tenant, [
            'context' => ['instance' => $instance],
        ]);

        $this->artisan('communication:backfill-wa-conversation-wa-number')
            ->assertExitCode(0);

        $state->refresh();
        $this->assertNull($state->wa_number_id);
    }

    /** @test */
    public function it_does_not_modify_rows_with_existing_non_null_mapping(): void
    {
        $this->requireTables();

        $tenant = $this->createTenant();
        $existing = WaNumber::create([
            'user_id' => $tenant->id,
            'provider' => 'meta',
            'phone_number' => '+966500010007',
            'phone_number_id' => 'pnid-existing-' . uniqid(),
            'name' => 'Existing',
            'status' => 'active',
        ]);
        $incoming = WaNumber::create([
            'user_id' => $tenant->id,
            'provider' => 'meta',
            'phone_number' => '+966500010008',
            'phone_number_id' => 'pnid-incoming-' . uniqid(),
            'name' => 'Incoming',
            'status' => 'active',
        ]);

        $state = $this->createConversationStateWithMessage($tenant, [
            'wa_number_id' => $incoming->id,
        ], $existing->id);

        $this->artisan('communication:backfill-wa-conversation-wa-number')
            ->assertExitCode(0);

        $state->refresh();
        $this->assertSame((int) $existing->id, (int) $state->wa_number_id);
    }

    /** @test */
    public function dry_run_reports_updates_without_writing_to_database(): void
    {
        $this->requireTables();

        $tenant = $this->createTenant();
        $waNumber = WaNumber::create([
            'user_id' => $tenant->id,
            'provider' => 'meta',
            'phone_number' => '+966500010009',
            'phone_number_id' => 'pnid-dry-' . uniqid(),
            'name' => 'Dry Run',
            'status' => 'active',
        ]);

        $state = $this->createConversationStateWithMessage($tenant, [
            'wa_number_id' => $waNumber->id,
        ]);

        $this->artisan('communication:backfill-wa-conversation-wa-number', ['--dry-run' => true])
            ->assertExitCode(0);

        $state->refresh();
        $this->assertNull($state->wa_number_id);
    }
}

