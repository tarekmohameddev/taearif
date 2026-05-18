<?php

declare(strict_types=1);

namespace Tests\Feature\WhatsappAI;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Models\WaConversationState;
use App\Models\WaNumber;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use Modules\WhatsappAI\Entities\WhatsappConversation;
use Modules\WhatsappAI\Entities\WhatsappMessage;
use Tests\TestCase;

class BackfillWhatsappAiToCommunicationTest extends TestCase
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
        ] as $table) {
            if (! Schema::hasTable($table)) {
                $this->markTestSkipped("{$table} table required.");
            }
        }
    }

    private function seedAiConversation(User $tenant, int $whatsappUserId): WhatsappConversation
    {
        $conversation = WhatsappConversation::create([
            'user_id' => $tenant->id,
            'whatsapp_user_id' => $whatsappUserId,
            'customer_phone' => '966507' . random_int(1000000, 9999999),
            'customer_name' => 'Backfill Customer',
            'status' => 'processed',
            'message_count' => 2,
            'last_message_at' => now()->subHour(),
        ]);

        WhatsappMessage::create([
            'conversation_id' => $conversation->id,
            'whatsapp_message_id' => 'wamid.backfill.' . uniqid(),
            'message_type' => 'text',
            'content' => 'First backfill message',
            'created_at' => now()->subMinutes(10),
            'updated_at' => now()->subMinutes(10),
        ]);

        WhatsappMessage::create([
            'conversation_id' => $conversation->id,
            'whatsapp_message_id' => 'wamid.backfill.' . uniqid(),
            'message_type' => 'text',
            'content' => 'Second backfill message',
            'created_at' => now()->subMinutes(5),
            'updated_at' => now()->subMinutes(5),
        ]);

        return $conversation;
    }

    /** @test */
    public function backfill_command_syncs_existing_whatsapp_ai_messages(): void
    {
        $this->requireTables();

        $tenant = User::factory()->create(['account_type' => 'tenant', 'tenant_id' => null]);
        $whatsappUserId = (int) \DB::table('whatsapp_users')->insertGetId([
            'user_id' => $tenant->id,
            'phone_id' => 'phone_backfill_' . uniqid(),
            'number' => '+966501234567',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $aiConversation = $this->seedAiConversation($tenant, $whatsappUserId);

        $exitCode = Artisan::call('whatsapp:backfill-ai-to-communication', [
            '--user-id' => $tenant->id,
            '--conversation-id' => $aiConversation->id,
        ]);
        $this->assertSame(0, $exitCode);

        $conversation = Conversation::where('user_id', $tenant->id)
            ->where('channel', 'whatsapp')
            ->first();
        $this->assertNotNull($conversation);

        $this->assertGreaterThanOrEqual(2, Message::where('conversation_id', $conversation->id)->count());
        $this->assertDatabaseHas('wa_conversation_states', [
            'conversation_id' => $conversation->id,
            'user_id' => $tenant->id,
        ]);

        $state = WaConversationState::where('conversation_id', $conversation->id)->first();
        $this->assertNotNull($state);
        $this->assertSame(0, (int) $state->unread_count);
    }

    /** @test */
    public function backfill_dry_run_does_not_create_communication_rows(): void
    {
        $this->requireTables();

        $tenant = User::factory()->create(['account_type' => 'tenant', 'tenant_id' => null]);
        $whatsappUserId = (int) \DB::table('whatsapp_users')->insertGetId([
            'user_id' => $tenant->id,
            'phone_id' => 'phone_backfill_dry_' . uniqid(),
            'number' => '+966501234567',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $aiConversation = $this->seedAiConversation($tenant, $whatsappUserId);

        $before = Message::where('user_id', $tenant->id)->count();

        Artisan::call('whatsapp:backfill-ai-to-communication', [
            '--user-id' => $tenant->id,
            '--conversation-id' => $aiConversation->id,
            '--dry-run' => true,
        ]);

        $this->assertSame($before, Message::where('user_id', $tenant->id)->count());
    }

    /** @test */
    public function backfill_is_repeatable_without_duplicating_messages(): void
    {
        $this->requireTables();

        $tenant = User::factory()->create(['account_type' => 'tenant', 'tenant_id' => null]);
        $whatsappUserId = (int) \DB::table('whatsapp_users')->insertGetId([
            'user_id' => $tenant->id,
            'phone_id' => 'phone_backfill_repeat_' . uniqid(),
            'number' => '+966501234567',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $aiConversation = $this->seedAiConversation($tenant, $whatsappUserId);

        Artisan::call('whatsapp:backfill-ai-to-communication', [
            '--user-id' => $tenant->id,
            '--conversation-id' => $aiConversation->id,
        ]);

        $countAfterFirst = Message::where('user_id', $tenant->id)->count();

        Artisan::call('whatsapp:backfill-ai-to-communication', [
            '--user-id' => $tenant->id,
            '--conversation-id' => $aiConversation->id,
        ]);

        $this->assertSame($countAfterFirst, Message::where('user_id', $tenant->id)->count());
    }

    /** @test */
    public function backfill_is_repeatable_for_whatsapp_ai_messages_without_provider_id(): void
    {
        $this->requireTables();

        $tenant = User::factory()->create(['account_type' => 'tenant', 'tenant_id' => null]);
        $whatsappUserId = (int) \DB::table('whatsapp_users')->insertGetId([
            'user_id' => $tenant->id,
            'phone_id' => 'phone_backfill_null_' . uniqid(),
            'number' => '+966501234567',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $aiConversation = WhatsappConversation::create([
            'user_id' => $tenant->id,
            'whatsapp_user_id' => $whatsappUserId,
            'customer_phone' => '966506' . random_int(1000000, 9999999),
            'customer_name' => 'No Provider Customer',
            'status' => 'processed',
        ]);

        $aiMessage = WhatsappMessage::create([
            'conversation_id' => $aiConversation->id,
            'whatsapp_message_id' => null,
            'message_type' => 'text',
            'content' => 'Message without provider id',
        ]);

        Artisan::call('whatsapp:backfill-ai-to-communication', [
            '--user-id' => $tenant->id,
            '--conversation-id' => $aiConversation->id,
        ]);
        Artisan::call('whatsapp:backfill-ai-to-communication', [
            '--user-id' => $tenant->id,
            '--conversation-id' => $aiConversation->id,
        ]);

        $this->assertSame(
            1,
            Message::where('user_id', $tenant->id)
                ->where('provider_message_id', 'whatsapp_ai:' . $aiMessage->id)
                ->count()
        );
    }

    /** @test */
    public function backfill_resolves_wa_number_from_whatsapp_user_phone_id(): void
    {
        $this->requireTables();

        $tenant = User::factory()->create(['account_type' => 'tenant', 'tenant_id' => null]);
        $phoneId = 'phone_backfill_resolve_' . uniqid();
        $whatsappUserId = (int) \DB::table('whatsapp_users')->insertGetId([
            'user_id' => $tenant->id,
            'phone_id' => $phoneId,
            'number' => '+966501234567',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $waNumber = WaNumber::create([
            'user_id' => $tenant->id,
            'provider' => 'meta',
            'phone_number' => '+966501234567',
            'phone_number_id' => $phoneId,
            'name' => 'Main',
            'status' => 'active',
        ]);

        $aiConversation = $this->seedAiConversation($tenant, $whatsappUserId);

        Artisan::call('whatsapp:backfill-ai-to-communication', [
            '--user-id' => $tenant->id,
            '--conversation-id' => $aiConversation->id,
        ]);

        $conversation = Conversation::where('user_id', $tenant->id)
            ->where('channel', 'whatsapp')
            ->first();
        $this->assertNotNull($conversation);

        $this->assertDatabaseHas('wa_conversation_states', [
            'conversation_id' => $conversation->id,
            'user_id' => $tenant->id,
            'wa_number_id' => $waNumber->id,
        ]);
    }
}
