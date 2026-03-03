<?php

declare(strict_types=1);

namespace Tests\Feature\V1\Communication;

use App\Models\Api\markting\CreditTransaction;
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

class SendMessageCreditFlowTest extends TestCase
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
    public function deduct_once_on_successful_send(): void
    {
        $this->requireTables();

        $this->mock(WhatsAppService::class, function (Mockery\MockInterface $mock): void {
            $mock->shouldReceive('sendMessage')->once()->andReturn(true);
        });

        $tenant = $this->createTenantUser();
        $this->ensureUserHasCredits($tenant, 10);

        $conversation = Conversation::create([
            'user_id' => $tenant->id,
            'channel' => 'whatsapp',
            'external_party_identifier' => '+966501234567',
            'last_message_at' => now(),
        ]);

        Sanctum::actingAs($tenant);

        $this->postJson('/api/v1/messages/send', [
            'conversation_id' => $conversation->id,
            'content' => 'Hello',
            'channel' => 'whatsapp',
        ], ['Idempotency-Key' => 'credit-deduct-' . uniqid()]);

        $uc = UserCredit::where('user_id', $tenant->id)->first();
        $this->assertNotNull($uc);
        $this->assertSame(1, $uc->used_credits);

        $usageCount = CreditTransaction::where('user_id', $tenant->id)->where('transaction_type', 'usage')->count();
        $this->assertGreaterThanOrEqual(1, $usageCount);
    }

    /** @test */
    public function no_double_deduction_on_replay(): void
    {
        $this->requireTables();

        $this->mock(WhatsAppService::class, function (Mockery\MockInterface $mock): void {
            $mock->shouldReceive('sendMessage')->once()->andReturn(true);
        });

        $tenant = $this->createTenantUser();
        $this->ensureUserHasCredits($tenant, 10);

        $conversation = Conversation::create([
            'user_id' => $tenant->id,
            'channel' => 'whatsapp',
            'external_party_identifier' => '+966501234567',
            'last_message_at' => now(),
        ]);

        Sanctum::actingAs($tenant);

        $key = 'replay-credit-' . uniqid();
        $payload = [
            'conversation_id' => $conversation->id,
            'content' => 'Hello',
            'channel' => 'whatsapp',
        ];

        $this->postJson('/api/v1/messages/send', $payload, ['Idempotency-Key' => $key]);
        $this->postJson('/api/v1/messages/send', $payload, ['Idempotency-Key' => $key]);

        $uc = UserCredit::where('user_id', $tenant->id)->first();
        $this->assertNotNull($uc);
        $this->assertSame(1, $uc->used_credits);
    }

    /** @test */
    public function refund_on_provider_failure(): void
    {
        $this->requireTables();

        $this->mock(WhatsAppService::class, function (Mockery\MockInterface $mock): void {
            $mock->shouldReceive('sendMessage')->once()->andReturn(false);
        });

        $tenant = $this->createTenantUser();
        $this->ensureUserHasCredits($tenant, 10);

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
        ], ['Idempotency-Key' => 'refund-' . uniqid()]);

        $res->assertStatus(502);

        $uc = UserCredit::where('user_id', $tenant->id)->first();
        $this->assertNotNull($uc);
        $this->assertSame(0, $uc->used_credits);

        $refundCount = CreditTransaction::where('user_id', $tenant->id)->where('transaction_type', 'refund')->count();
        $this->assertGreaterThanOrEqual(1, $refundCount);
    }

    /** @test */
    public function insufficient_credits_blocks_send_and_returns_400(): void
    {
        $this->requireTables();

        $this->mock(WhatsAppService::class, function (Mockery\MockInterface $mock): void {
            $mock->shouldNotReceive('sendMessage');
        });

        $tenant = $this->createTenantUser();
        $uc = UserCredit::getOrCreateForUser($tenant->id);
        $uc->update(['total_credits' => 0, 'used_credits' => 0]);

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
        ], ['Idempotency-Key' => 'insufficient-' . uniqid()]);

        $res->assertStatus(400)
            ->assertJsonPath('code', 'INSUFFICIENT_CREDITS');

        $uc->refresh();
        $this->assertSame(0, $uc->used_credits);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
