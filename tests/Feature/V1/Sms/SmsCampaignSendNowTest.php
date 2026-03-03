<?php

declare(strict_types=1);

namespace Tests\Feature\V1\Sms;

use App\Domain\Communication\Sms\Contracts\SmsGatewayClient;
use App\Domain\Communication\Sms\DTOs\SmsGatewaySendResult;
use App\Models\Api\markting\MarketingChannelPricing;
use App\Models\Api\markting\UserCredit;
use App\Models\SmsCampaign;
use App\Models\SmsMessageLog;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Mockery;
use Tests\TestCase;

class SmsCampaignSendNowTest extends TestCase
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

    private function createTenant(): User
    {
        return User::factory()->create(['account_type' => 'tenant', 'tenant_id' => null]);
    }

    /** @test */
    public function send_now_creates_logs_and_returns_202(): void
    {
        $this->requireSmsTables();
        $this->requireSmsPricing();

        $this->mock(SmsGatewayClient::class, function (Mockery\MockInterface $mock): void {
            $mock->shouldReceive('sendText')->andReturn(new SmsGatewaySendResult(true, 'gw-1', 'test'));
            $mock->shouldReceive('verifyWebhookSignature')->andReturnTrue();
            $mock->shouldReceive('parseDeliveryWebhook')->andReturn([]);
        });

        $tenant = $this->createTenant();
        UserCredit::getOrCreateForUser($tenant->id)->update(['total_credits' => 10, 'used_credits' => 0, 'reserved_credits' => 0]);

        $campaign = SmsCampaign::create([
            'user_id' => $tenant->id,
            'name' => 'Send Now Campaign',
            'message' => 'Hello',
            'status' => 'draft',
        ]);

        Sanctum::actingAs($tenant);
        $res = $this->postJson("/api/v1/sms/campaigns/{$campaign->id}/send", [
            'manual_phones' => ['+966500000001', '+966500000002'],
        ], ['Idempotency-Key' => 'send-now-key-' . uniqid()]);

        $res->assertStatus(202)->assertJsonPath('data.status', 'in_progress');
        $this->assertNotEmpty($res->json('data.dispatch_reference'));

        $count = SmsMessageLog::where('campaign_id', $campaign->id)->count();
        $this->assertSame(2, $count);

        $credits = UserCredit::where('user_id', $tenant->id)->firstOrFail();
        $this->assertSame(2, (int) ($credits->reserved_credits ?? 0), 'Credits are reserved at send time, not deducted upfront.');
    }

    /** @test */
    public function send_with_insufficient_credits_returns_400(): void
    {
        $this->requireSmsTables();
        $this->requireSmsPricing();

        $tenant = $this->createTenant();
        UserCredit::getOrCreateForUser($tenant->id)->update(['total_credits' => 1, 'used_credits' => 0, 'reserved_credits' => 0]);

        $campaign = SmsCampaign::create([
            'user_id' => $tenant->id,
            'name' => 'No Credits',
            'message' => 'Hi',
            'status' => 'draft',
        ]);

        Sanctum::actingAs($tenant);
        $this->postJson("/api/v1/sms/campaigns/{$campaign->id}/send", [
            'manual_phones' => ['+966500000001', '+966500000002'],
        ], ['Idempotency-Key' => 'insufficient-' . uniqid()])->assertStatus(400);

        $credits = UserCredit::where('user_id', $tenant->id)->firstOrFail();
        $this->assertSame(0, (int) ($credits->used_credits ?? 0));
        $this->assertSame(0, (int) ($credits->reserved_credits ?? 0));
    }

    /** @test */
    public function send_without_recipients_returns_422(): void
    {
        $this->requireSmsTables();
        $this->requireSmsPricing();

        $tenant = $this->createTenant();
        UserCredit::getOrCreateForUser($tenant->id)->update(['total_credits' => 10, 'used_credits' => 0, 'reserved_credits' => 0]);

        $campaign = SmsCampaign::create([
            'user_id' => $tenant->id,
            'name' => 'No Recipients',
            'message' => 'Hi',
            'status' => 'draft',
        ]);

        Sanctum::actingAs($tenant);
        $res = $this->postJson("/api/v1/sms/campaigns/{$campaign->id}/send", [], [
            'Idempotency-Key' => 'no-recipients-' . uniqid(),
        ]);

        $res->assertStatus(422)
            ->assertJsonPath('message', 'Validation failed')
            ->assertJsonPath('errors.customer_ids.0', 'At least one of customer_ids or manual_phones must be provided with at least one value.');
    }

    /** @test */
    public function send_with_invalid_recipients_returns_422(): void
    {
        $this->requireSmsTables();
        $this->requireSmsPricing();

        $tenant = $this->createTenant();
        UserCredit::getOrCreateForUser($tenant->id)->update(['total_credits' => 10, 'used_credits' => 0, 'reserved_credits' => 0]);

        $campaign = SmsCampaign::create([
            'user_id' => $tenant->id,
            'name' => 'Invalid Phones',
            'message' => 'Hi',
            'status' => 'draft',
        ]);

        Sanctum::actingAs($tenant);
        $res = $this->postJson("/api/v1/sms/campaigns/{$campaign->id}/send", [
            'manual_phones' => ['123', 'abc'],
        ], ['Idempotency-Key' => 'invalid-recipients-' . uniqid()]);

        $res->assertStatus(422)
            ->assertJsonPath('code', 'VALIDATION_FAILED')
            ->assertJsonPath('message', 'No valid phone numbers from the given customer_ids or manual_phones. Ensure customer IDs exist and have a valid phone (8–16 digits), and that manual_phones are valid (8–16 digits).');
    }
}
