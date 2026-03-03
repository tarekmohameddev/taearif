<?php

declare(strict_types=1);

namespace Tests\Feature\V1\Communication;

use App\Models\Api\markting\UserCredit;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Services\WhatsAppService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Mockery;
use Tests\TestCase;

class SendMessageIdempotencyReplayTest extends TestCase
{
    use DatabaseTransactions;

    private function requireTables(): void
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
    public function first_request_succeeds_and_second_same_key_same_payload_replays_same_message(): void
    {
        $this->requireTables();

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

        $key = 'replay-key-' . uniqid();
        $payload = [
            'conversation_id' => $conversation->id,
            'content' => 'Hello',
            'channel' => 'whatsapp',
        ];

        $res1 = $this->postJson('/api/v1/messages/send', $payload, ['Idempotency-Key' => $key]);
        $res1->assertOk();
        $messageId1 = $res1->json('data.message.id');

        $res2 = $this->postJson('/api/v1/messages/send', $payload, ['Idempotency-Key' => $key]);
        $res2->assertOk();
        $messageId2 = $res2->json('data.message.id');

        $this->assertSame($messageId1, $messageId2);

        $messageCount = Message::where('conversation_id', $conversation->id)->where('direction', 'outbound')->count();
        $this->assertSame(1, $messageCount);
    }

    /** @test */
    public function same_key_different_payload_returns_409(): void
    {
        $this->requireTables();

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

        $key = 'conflict-key-' . uniqid();

        $this->postJson('/api/v1/messages/send', [
            'conversation_id' => $conversation->id,
            'content' => 'First',
            'channel' => 'whatsapp',
        ], ['Idempotency-Key' => $key])->assertOk();

        $res = $this->postJson('/api/v1/messages/send', [
            'conversation_id' => $conversation->id,
            'content' => 'Second',
            'channel' => 'whatsapp',
        ], ['Idempotency-Key' => $key]);

        $res->assertStatus(409)
            ->assertJsonPath('code', 'HASH_MISMATCH');
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
