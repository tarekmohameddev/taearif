<?php

declare(strict_types=1);

namespace Tests\Feature\V1\WhatsApp;

use App\Models\Conversation;
use App\Models\User;
use App\Models\WaConversationState;
use App\Models\WaNumber;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class WhatsAppConversationCreateOrReturnTest extends TestCase
{
    use DatabaseTransactions;

    private function requireTables(): void
    {
        if (! Schema::hasTable('conversations') || ! Schema::hasTable('wa_conversation_states') || ! Schema::hasTable('wa_numbers')) {
            $this->markTestSkipped('conversations, wa_conversation_states and wa_numbers tables required.');
        }
    }

    private function createTenant(): User
    {
        return User::factory()->create(['account_type' => 'tenant', 'tenant_id' => null]);
    }

    /** @test */
    public function store_creates_conversation_and_state_when_new(): void
    {
        $this->requireTables();

        $tenant = $this->createTenant();
        $waNumber = WaNumber::create([
            'user_id' => $tenant->id,
            'provider' => 'meta',
            'phone_number' => '+966501234567',
            'name' => 'Main',
            'status' => 'active',
        ]);

        Sanctum::actingAs($tenant);
        $res = $this->postJson('/api/v1/whatsapp/conversations', [
            'external_party_identifier' => '+966512345678',
            'wa_number_id' => $waNumber->id,
        ]);
        $res->assertOk();

        $this->assertDatabaseHas('conversations', [
            'user_id' => $tenant->id,
            'channel' => 'whatsapp',
            'external_party_identifier' => '+966512345678',
        ]);
        $conv = Conversation::where('user_id', $tenant->id)->where('external_party_identifier', '+966512345678')->first();
        $this->assertNotNull($conv);
        $this->assertDatabaseHas('wa_conversation_states', [
            'conversation_id' => $conv->id,
            'user_id' => $tenant->id,
            'wa_number_id' => $waNumber->id,
        ]);
    }

    /** @test */
    public function store_returns_existing_conversation_when_identifier_exists(): void
    {
        $this->requireTables();

        $tenant = $this->createTenant();
        $conv = Conversation::create([
            'user_id' => $tenant->id,
            'channel' => 'whatsapp',
            'external_party_identifier' => '+966512345678',
            'last_message_at' => now(),
        ]);
        WaConversationState::create([
            'conversation_id' => $conv->id,
            'user_id' => $tenant->id,
            'status' => 'active',
            'is_starred' => false,
            'unread_count' => 0,
        ]);

        Sanctum::actingAs($tenant);
        $res = $this->postJson('/api/v1/whatsapp/conversations', [
            'external_party_identifier' => '+966512345678',
        ]);
        $res->assertOk();

        $this->assertDatabaseCount('conversations', 1);
    }
}
