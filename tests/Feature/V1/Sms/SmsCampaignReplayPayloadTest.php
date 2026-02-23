<?php

declare(strict_types=1);

namespace Tests\Feature\V1\Sms;

use App\Domain\Communication\Sms\Contracts\SmsGatewayClient;
use App\Domain\Communication\Sms\DTOs\SmsGatewaySendResult;
use App\Models\Api\markting\MarketingChannelPricing;
use App\Models\Api\markting\UserCredit;
use App\Models\SmsCampaign;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Mockery;
use Tests\TestCase;

class SmsCampaignReplayPayloadTest extends TestCase
{
    use DatabaseTransactions;

    private function requireSmsTables(): void
    {
        foreach (['sms_campaigns', 'sms_message_logs', 'idempotency_keys', 'user_credits', 'credit_transactions'] as $table) {
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

    private function createTenantUser(): User
    {
        return User::factory()->create([
            'account_type' => 'tenant',
            'tenant_id' => null,
        ]);
    }

    /** @test */
    public function campaign_send_replays_stored_payload_and_does_not_double_deduct(): void
    {
        $this->requireSmsTables();
        $this->requireSmsPricing();

        $this->mock(SmsGatewayClient::class, function (Mockery\MockInterface $mock): void {
            $mock->shouldReceive('sendText')->andReturn(new SmsGatewaySendResult(true, 'gw-1', 'test'));
            $mock->shouldReceive('verifyWebhookSignature')->andReturnTrue();
            $mock->shouldReceive('parseDeliveryWebhook')->andReturn([]);
        });

        $tenant = $this->createTenantUser();
        UserCredit::getOrCreateForUser($tenant->id)->update(['total_credits' => 10, 'used_credits' => 0, 'reserved_credits' => 0]);

        $campaign = SmsCampaign::create([
            'user_id' => $tenant->id,
            'name' => 'Campaign A',
            'message' => 'Hello',
            'status' => 'scheduled',
            'scheduled_at' => now()->addHour(),
        ]);

        Sanctum::actingAs($tenant);
        $key = 'sms-campaign-key-' . uniqid();
        $payload = [
            'manual_phones' => ['+966500000001', '+966500000002'],
        ];

        $r1 = $this->postJson("/api/v1/sms/campaigns/{$campaign->id}/send", $payload, ['Idempotency-Key' => $key]);
        $r2 = $this->postJson("/api/v1/sms/campaigns/{$campaign->id}/send", $payload, ['Idempotency-Key' => $key]);

        $r1->assertStatus(202);
        $r2->assertStatus(202);
        $this->assertSame($r1->json('data.dispatch_reference'), $r2->json('data.dispatch_reference'));

        $credits = UserCredit::where('user_id', $tenant->id)->firstOrFail();
        $this->assertSame(2, (int) ($credits->reserved_credits ?? 0), 'Replay must not double-reserve; only one reserve of 2.');
        $this->assertSame(0, (int) $credits->used_credits, 'Credits consumed only when messages are sent (job may not run in test).');

        $this->assertSame(2, \App\Models\SmsMessageLog::where('campaign_id', $campaign->id)->count());
    }

    /** @test */
    public function campaign_send_same_key_different_payload_returns_409(): void
    {
        $this->requireSmsTables();
        $this->requireSmsPricing();

        $tenant = $this->createTenantUser();
        UserCredit::getOrCreateForUser($tenant->id)->update(['total_credits' => 10, 'used_credits' => 0, 'reserved_credits' => 0]);

        $campaign = SmsCampaign::create([
            'user_id' => $tenant->id,
            'name' => 'Campaign B',
            'message' => 'Hello',
            'status' => 'scheduled',
            'scheduled_at' => now()->addHour(),
        ]);

        Sanctum::actingAs($tenant);
        $key = 'sms-campaign-mismatch-' . uniqid();

        $this->postJson("/api/v1/sms/campaigns/{$campaign->id}/send", [
            'manual_phones' => ['+966500000001'],
        ], ['Idempotency-Key' => $key])->assertStatus(202);

        $this->postJson("/api/v1/sms/campaigns/{$campaign->id}/send", [
            'manual_phones' => ['+966500000002'],
        ], ['Idempotency-Key' => $key])->assertStatus(409);
    }
}

