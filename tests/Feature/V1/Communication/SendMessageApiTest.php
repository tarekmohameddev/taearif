<?php

declare(strict_types=1);

namespace Tests\Feature\V1\Communication;

use App\Models\Api\marketing\UserCredit;
use App\Models\Conversation;
use App\Models\User;
use App\Services\WhatsAppService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Mockery;
use Tests\TestCase;

class SendMessageApiTest extends TestCase
{
    use DatabaseTransactions;

    private function requireCommunicationAndCreditsTables(): void
    {
        if (!Schema::hasTable('conversations') || !Schema::hasTable('messages') || !Schema::hasTable('idempotency_keys')) {
            $this->markTestSkipped('conversations, messages and idempotency_keys tables required.');
        }
        if (!Schema::hasTable('user_credits') || !Schema::hasTable('credit_transactions')) {
            $this->markTestSkipped('user_credits and credit_transactions tables required.');
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

    private function ensureUserHasCredits(User $user, int $credits = 100): void
    {
        $uc = UserCredit::getOrCreateForUser($user->id);
        $uc->update(['total_credits' => $credits, 'used_credits' => 0]);
    }

    /** @test */
    public function send_returns_200_and_message_when_successful(): void
    {
        $this->requireCommunicationAndCreditsTables();

        $this->mock(WhatsAppService::class, function (Mockery\MockInterface $mock): void {
            $mock->shouldReceive('sendMessage')->once()->andReturn(true);
        });

        $tenant = $this->createTenantUser();
        $this->ensureUserHasCredits($tenant);

        $conversation = Conversation::create([
            'user_id' => $tenant->id,
            'channel' => 'whatsapp',
            'external_party_identifier' => '+966501234567',
            'last_message_at' => now(),
        ]);

        Sanctum::actingAs($tenant);

        $res = $this->postJson('/api/v1/messages/send', [
            'conversation_id' => $conversation->id,
            'content' => 'Hello',
            'channel' => 'whatsapp',
        ], [
            'Idempotency-Key' => 'test-key-' . uniqid(),
        ]);

        $res->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.message.direction', 'outbound')
            ->assertJsonPath('data.message.status', 'sent')
            ->assertJsonPath('data.message.content', 'Hello');

        $this->assertDatabaseHas('messages', [
            'conversation_id' => $conversation->id,
            'user_id' => $tenant->id,
            'direction' => 'outbound',
            'status' => 'sent',
        ]);
    }

    /** @test */
    public function send_returns_422_when_idempotency_key_missing(): void
    {
        $this->requireCommunicationAndCreditsTables();

        $tenant = $this->createTenantUser();
        Sanctum::actingAs($tenant);

        $res = $this->postJson('/api/v1/messages/send', [
            'conversation_id' => 1,
            'content' => 'Hello',
        ]);

        $res->assertStatus(422)
            ->assertJsonPath('code', 'VALIDATION_ERROR');
    }

    /** @test */
    public function send_returns_422_when_channel_is_not_whatsapp(): void
    {
        $this->requireCommunicationAndCreditsTables();

        $tenant = $this->createTenantUser();
        Sanctum::actingAs($tenant);

        $res = $this->postJson('/api/v1/messages/send', [
            'conversation_id' => 1,
            'content' => 'Hello',
            'channel' => 'sms',
        ], [
            'Idempotency-Key' => 'test-key-' . uniqid(),
        ]);

        $res->assertStatus(422);
    }

    /** @test */
    public function send_returns_404_for_cross_tenant_conversation(): void
    {
        $this->requireCommunicationAndCreditsTables();

        $this->mock(WhatsAppService::class, function (Mockery\MockInterface $mock): void {
            $mock->shouldNotReceive('sendMessage');
        });

        $tenantA = $this->createTenantUser();
        $tenantB = $this->createTenantUser();
        $this->ensureUserHasCredits($tenantA);

        $conversation = Conversation::create([
            'user_id' => $tenantB->id,
            'channel' => 'whatsapp',
            'external_party_identifier' => '+966501234567',
            'last_message_at' => now(),
        ]);

        Sanctum::actingAs($tenantA);

        $res = $this->postJson('/api/v1/messages/send', [
            'conversation_id' => $conversation->id,
            'content' => 'Hello',
        ], [
            'Idempotency-Key' => 'test-key-' . uniqid(),
        ]);

        $res->assertNotFound()
            ->assertJsonPath('code', 'CONVERSATION_NOT_FOUND');
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
