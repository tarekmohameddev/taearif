<?php

declare(strict_types=1);

namespace Tests\Feature\V1\Sms;

use App\Domain\Communication\Sms\Contracts\SmsGatewayClient;
use App\Domain\Communication\Sms\DTOs\SmsGatewaySendResult;
use App\Models\Api\marketing\MarketingChannelPricing;
use App\Models\Api\marketing\UserCredit;
use App\Models\SmsCampaign;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Mockery;
use Tests\TestCase;

class SmsIdempotencyTest extends TestCase
{
    use DatabaseTransactions;

    private function requireSmsTables(): void
    {
        foreach (['sms_campaigns', 'sms_message_logs', 'idempotency_keys', 'user_credits'] as $table) {
            if (!Schema::hasTable($table)) {
                $this->markTestSkipped("{$table} table required.");
            }
        }
    }

    private function requireSmsPricing(): void
    {
        if (!Schema::hasTable('marketing_channel_pricing')) {
            $this->markTestSkipped('marketing_channel_pricing table required.');
        }
        MarketingChannelPricing::updateOrCreate(
            ['channel_type' => 'sms'],
            [
                'credits_per_message' => 1,
                'price_per_credit' => 0.05,
                'effective_price_per_message' => 0.05,
                'currency' => 'SAR',
                'is_active' => true,
                'description' => 'SMS (test)',
            ]
        );
    }

    private function createTenant(): User
    {
        return User::factory()->create(['account_type' => 'tenant', 'tenant_id' => null]);
    }

    /** @test */
    public function same_key_same_payload_returns_replay_without_double_charge(): void
    {
        $this->requireSmsTables();
        $this->requireSmsPricing();

        $mock = $this->mock(SmsGatewayClient::class, function (Mockery\MockInterface $mock): void {
            $mock->shouldReceive('sendText')->andReturn(new SmsGatewaySendResult(true, 'gw-1', 'test'));
            $mock->shouldReceive('verifyWebhookSignature')->andReturnTrue();
            $mock->shouldReceive('parseDeliveryWebhook')->andReturn([]);
        });
        $this->app->instance(SmsGatewayClient::class, $mock);

        $tenant = $this->createTenant();
        UserCredit::getOrCreateForUser($tenant->id)->update(['total_credits' => 10, 'used_credits' => 0, 'reserved_credits' => 0]);
        $campaign = SmsCampaign::create([
            'user_id' => $tenant->id,
            'name' => 'Idem Campaign',
            'message' => 'Hi',
            'status' => 'draft',
        ]);

        Sanctum::actingAs($tenant);
        $key = 'idem-same-' . uniqid();
        $payload = ['manual_phones' => ['+966500000001']];

        $r1 = $this->postJson("/api/v1/sms/campaigns/{$campaign->id}/send", $payload, ['Idempotency-Key' => $key]);
        $r2 = $this->postJson("/api/v1/sms/campaigns/{$campaign->id}/send", $payload, ['Idempotency-Key' => $key]);

        $r1->assertStatus(202);
        $r2->assertStatus(202);
        $this->assertSame($r1->json('data.dispatch_reference'), $r2->json('data.dispatch_reference'));

        $credits = UserCredit::where('user_id', $tenant->id)->firstOrFail();
        $this->assertSame(1, (int) ($credits->reserved_credits ?? 0), 'Replay must not double-reserve; only one reserve of 1.');
    }

    /** @test */
    public function same_key_different_payload_returns_409_hash_mismatch(): void
    {
        $this->requireSmsTables();
        $this->requireSmsPricing();

        $tenant = $this->createTenant();
        UserCredit::getOrCreateForUser($tenant->id)->update(['total_credits' => 10, 'used_credits' => 0, 'reserved_credits' => 0]);
        $campaign = SmsCampaign::create([
            'user_id' => $tenant->id,
            'name' => 'Hash Mismatch',
            'message' => 'Hi',
            'status' => 'draft',
        ]);

        Sanctum::actingAs($tenant);
        $key = 'idem-mismatch-' . uniqid();

        $this->postJson("/api/v1/sms/campaigns/{$campaign->id}/send", [
            'manual_phones' => ['+966500000001'],
        ], ['Idempotency-Key' => $key])->assertStatus(202);

        $this->postJson("/api/v1/sms/campaigns/{$campaign->id}/send", [
            'manual_phones' => ['+966500000002'],
        ], ['Idempotency-Key' => $key])->assertStatus(409);
    }

    /** @test */
    public function send_without_idempotency_key_returns_422_or_400(): void
    {
        $this->requireSmsTables();
        $this->requireSmsPricing();

        $tenant = $this->createTenant();
        UserCredit::getOrCreateForUser($tenant->id)->update(['total_credits' => 10, 'used_credits' => 0]);
        $campaign = SmsCampaign::create([
            'user_id' => $tenant->id,
            'name' => 'No Key',
            'message' => 'Hi',
            'status' => 'draft',
        ]);

        Sanctum::actingAs($tenant);
        $res = $this->postJson("/api/v1/sms/campaigns/{$campaign->id}/send", [
            'manual_phones' => ['+966500000001'],
        ]);
        $this->assertTrue(in_array($res->status(), [400, 422], true), 'Expected 400 or 422 when Idempotency-Key missing.');
    }
}
