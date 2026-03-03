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

class ConversationsReadApiTest extends TestCase
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
    public function index_returns_tenant_scoped_conversations_with_pagination(): void
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

        $res = $this->getJson('/api/v1/conversations?per_page=20');

        $res->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.pagination.current_page', 1)
            ->assertJsonPath('data.pagination.per_page', 20);
        $conversations = $res->json('data.conversations');
        $this->assertIsArray($conversations);
        $this->assertGreaterThanOrEqual(1, count($conversations));
        $ids = array_column($conversations, 'id');
        $this->assertContains((string) $conversation->id, $ids);
    }

    /** @test */
    public function index_respects_search(): void
    {
        $this->requireCommunicationTables();
        $tenant = $this->createTenantUser();
        Sanctum::actingAs($tenant);

        Conversation::create([
            'user_id' => $tenant->id,
            'channel' => 'whatsapp',
            'external_party_identifier' => '+966509999999',
            'last_message_at' => now(),
        ]);

        $res = $this->getJson('/api/v1/conversations?search=966509999999');
        $res->assertOk()->assertJsonPath('status', 'success');
    }

    /** @test */
    public function show_returns_404_for_conversation_not_owned(): void
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
        $res = $this->getJson('/api/v1/conversations/' . $conversation->id);
        $res->assertNotFound();
    }

    /** @test */
    public function employee_sees_tenant_owner_pooled_conversations(): void
    {
        $this->requireCommunicationTables();
        $tenant = $this->createTenantUser();
        $employee = User::factory()->create([
            'account_type' => 'employee',
            'tenant_id' => $tenant->id,
        ]);

        $conversation = Conversation::create([
            'user_id' => $tenant->id,
            'channel' => 'whatsapp',
            'external_party_identifier' => '+966501234567',
            'last_message_at' => now(),
        ]);

        Sanctum::actingAs($employee);
        $res = $this->getJson('/api/v1/conversations');
        $res->assertOk()->assertJsonPath('status', 'success');
        $conversations = $res->json('data.conversations');
        $ids = array_column($conversations ?? [], 'id');
        $this->assertContains((string) $conversation->id, $ids);
    }
}
