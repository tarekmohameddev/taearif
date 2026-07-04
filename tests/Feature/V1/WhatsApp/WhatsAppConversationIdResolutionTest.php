<?php

declare(strict_types=1);

namespace Tests\Feature\V1\WhatsApp;

use App\Domain\Communication\Contracts\CommunicationService;
use App\Domain\Communication\DTOs\SendMessageDto;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Models\WaConversationState;
use App\Models\WaNumber;
use App\Models\WaTemplate;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Mockery;
use Tests\TestCase;

class WhatsAppConversationIdResolutionTest extends TestCase
{
    use DatabaseTransactions;

    private function requireTables(): void
    {
        foreach (['conversations', 'wa_conversation_states', 'messages', 'wa_numbers', 'wa_templates'] as $table) {
            if (! Schema::hasTable($table)) {
                $this->markTestSkipped("{$table} table required.");
            }
        }
    }

    private function createTenant(): User
    {
        return User::factory()->create(['account_type' => 'tenant', 'tenant_id' => null]);
    }

    /** @return array{tenant: User, conversation: Conversation, state: WaConversationState, waNumber: WaNumber} */
    private function createWhatsAppConversation(User $tenant): array
    {
        $waNumber = WaNumber::create([
            'user_id' => $tenant->id,
            'provider' => 'meta',
            'phone_number' => '+966501234567',
            'name' => 'Main',
            'status' => 'active',
        ]);

        $conversation = Conversation::create([
            'user_id' => $tenant->id,
            'channel' => 'whatsapp',
            'external_party_identifier' => '+966512345678',
            'last_message_at' => now(),
        ]);

        $state = WaConversationState::create([
            'conversation_id' => $conversation->id,
            'user_id' => $tenant->id,
            'wa_number_id' => $waNumber->id,
            'status' => 'active',
            'is_starred' => false,
            'unread_count' => 2,
        ]);

        Message::create([
            'conversation_id' => $conversation->id,
            'user_id' => $tenant->id,
            'content' => 'Hello from test',
            'direction' => 'inbound',
            'status' => 'received',
            'provider_message_id' => 'wamid.test.' . uniqid(),
        ]);

        return compact('tenant', 'conversation', 'state', 'waNumber');
    }

    /** @test */
    public function list_includes_state_id_alias(): void
    {
        $this->requireTables();

        $tenant = $this->createTenant();
        $fixture = $this->createWhatsAppConversation($tenant);

        Sanctum::actingAs($tenant);
        $res = $this->getJson('/api/v1/whatsapp/conversations');
        $res->assertOk();

        $row = collect($res->json('data.data'))->firstWhere('id', $fixture['state']->id);
        $this->assertNotNull($row);
        $this->assertSame($fixture['state']->id, $row['state_id']);
        $this->assertSame($fixture['conversation']->id, $row['conversation_id']);
    }

    /** @test */
    public function messages_endpoint_works_with_conversation_id_and_state_id(): void
    {
        $this->requireTables();

        $tenant = $this->createTenant();
        $fixture = $this->createWhatsAppConversation($tenant);

        Sanctum::actingAs($tenant);

        $byConversation = $this->getJson('/api/v1/whatsapp/conversations/' . $fixture['conversation']->id . '/messages');
        $byConversation->assertOk();

        $byState = $this->getJson('/api/v1/whatsapp/conversations/' . $fixture['state']->id . '/messages');
        $byState->assertOk();

        $this->assertSame(
            $byConversation->json('data.data.messages'),
            $byState->json('data.data.messages')
        );
    }

    /** @test */
    public function show_read_and_star_resolve_both_id_forms(): void
    {
        $this->requireTables();

        $tenant = $this->createTenant();
        $fixture = $this->createWhatsAppConversation($tenant);

        Sanctum::actingAs($tenant);

        $showByConversation = $this->getJson('/api/v1/whatsapp/conversations/' . $fixture['conversation']->id);
        $showByConversation->assertOk();

        $showByState = $this->getJson('/api/v1/whatsapp/conversations/' . $fixture['state']->id);
        $showByState->assertOk();

        $this->assertSame(
            $showByConversation->json('data.data.conversation_id'),
            $showByState->json('data.data.conversation_id')
        );

        $this->postJson('/api/v1/whatsapp/conversations/' . $fixture['state']->id . '/read')->assertOk();
        $this->assertSame(0, (int) $fixture['state']->fresh()->unread_count);

        $this->postJson('/api/v1/whatsapp/conversations/' . $fixture['conversation']->id . '/star')->assertOk();
        $this->assertTrue((bool) $fixture['state']->fresh()->is_starred);
    }

    /** @test */
    public function send_and_send_template_use_resolved_conversation_id(): void
    {
        $this->requireTables();

        $tenant = $this->createTenant();
        $fixture = $this->createWhatsAppConversation($tenant);

        $template = WaTemplate::create([
            'user_id' => $tenant->id,
            'name' => 'Greeting',
            'content' => 'Hello {{name}}',
            'category' => 'utility',
            'is_active' => true,
        ]);

        $outbound = Message::make([
            'conversation_id' => $fixture['conversation']->id,
            'user_id' => $tenant->id,
            'content' => 'sent',
            'direction' => 'outbound',
            'status' => 'sent',
            'provider_message_id' => 'wamid.out.' . uniqid(),
        ]);
        $outbound->id = 999001;

        $this->mock(CommunicationService::class, function (Mockery\MockInterface $mock) use ($fixture, $outbound): void {
            $mock->shouldReceive('sendMessage')
                ->twice()
                ->withArgs(function (SendMessageDto $dto) use ($fixture): bool {
                    return $dto->conversationId === $fixture['conversation']->id;
                })
                ->andReturn($outbound);
        });

        Sanctum::actingAs($tenant);

        $this->postJson('/api/v1/whatsapp/conversations/' . $fixture['state']->id . '/messages', [
            'wa_number_id' => $fixture['waNumber']->id,
            'content' => 'Hello',
        ])->assertOk();

        $this->postJson('/api/v1/whatsapp/conversations/' . $fixture['state']->id . '/messages/template', [
            'wa_number_id' => $fixture['waNumber']->id,
            'template_id' => $template->id,
            'variables' => ['name' => 'Customer'],
        ])->assertOk();
    }

    /** @test */
    public function tenant_isolation_is_enforced_for_both_id_forms(): void
    {
        $this->requireTables();

        $tenantA = $this->createTenant();
        $tenantB = $this->createTenant();
        $fixtureA = $this->createWhatsAppConversation($tenantA);

        Sanctum::actingAs($tenantB);

        $this->getJson('/api/v1/whatsapp/conversations/' . $fixtureA['conversation']->id . '/messages')
            ->assertStatus(404);
        $this->getJson('/api/v1/whatsapp/conversations/' . $fixtureA['state']->id . '/messages')
            ->assertStatus(404);
        $this->getJson('/api/v1/whatsapp/conversations/' . $fixtureA['state']->id)
            ->assertStatus(404);
    }

    /** @test */
    public function non_whatsapp_conversations_still_return_not_found(): void
    {
        $this->requireTables();

        $tenant = $this->createTenant();
        $emailConversation = Conversation::create([
            'user_id' => $tenant->id,
            'channel' => 'email',
            'external_party_identifier' => 'user@example.com',
            'last_message_at' => now(),
        ]);

        Sanctum::actingAs($tenant);

        $this->getJson('/api/v1/whatsapp/conversations/' . $emailConversation->id . '/messages')
            ->assertStatus(404);
        $this->getJson('/api/v1/whatsapp/conversations/' . $emailConversation->id)
            ->assertStatus(404);
    }
}
