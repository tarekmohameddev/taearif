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
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Mockery;
use Tests\TestCase;

class SmsCampaignPauseResumeTest extends TestCase
{
    use DatabaseTransactions;

    private function requireSmsTables(): void
    {
        foreach (['sms_campaigns', 'sms_message_logs', 'idempotency_keys', 'user_credits', 'credit_transactions'] as $table) {
            if (!Schema::hasTable($table)) {
                $this->markTestSkipped("{$table} table required.");
            }
        }
        if (!Schema::hasColumn('user_credits', 'reserved_credits')) {
            $this->markTestSkipped('user_credits.reserved_credits column required.');
        }
        if (!Schema::hasColumn('sms_campaigns', 'reserved_credits')) {
            $this->markTestSkipped('sms_campaigns.reserved_credits column required.');
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
    public function pause_in_progress_campaign_marks_logs_paused_and_releases_credits(): void
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
            'name' => 'Pause Me',
            'message' => 'Hi',
            'status' => 'draft',
        ]);

        Sanctum::actingAs($tenant);
        $this->postJson("/api/v1/sms/campaigns/{$campaign->id}/send", [
            'manual_phones' => ['+966500000001', '+966500000002', '+966500000003'],
        ], ['Idempotency-Key' => 'pause-test-' . uniqid()])->assertStatus(202);

        $campaign->refresh();
        $this->assertSame(3, (int) $campaign->reserved_credits);
        $creditsBefore = UserCredit::where('user_id', $tenant->id)->firstOrFail();
        $this->assertSame(3, (int) ($creditsBefore->reserved_credits ?? 0));

        $res = $this->postJson("/api/v1/sms/campaigns/{$campaign->id}/pause");
        $res->assertStatus(200)
            ->assertJsonPath('data.status', 'paused')
            ->assertJsonPath('data.paused_count', 3)
            ->assertJsonPath('data.credit_info.released', 3);

        $campaign->refresh();
        $this->assertSame('paused', $campaign->status);
        $pausedLogs = SmsMessageLog::where('campaign_id', $campaign->id)->where('status', 'paused')->count();
        $this->assertSame(3, $pausedLogs);

        $creditsAfter = UserCredit::where('user_id', $tenant->id)->firstOrFail();
        $this->assertSame(0, (int) ($creditsAfter->reserved_credits ?? 0));
        $this->assertSame(0, (int) $creditsAfter->used_credits);
    }

    /** @test */
    public function pause_returns_404_for_unknown_campaign(): void
    {
        $this->requireSmsTables();

        $tenant = $this->createTenant();
        Sanctum::actingAs($tenant);

        $this->postJson('/api/v1/sms/campaigns/99999/pause')->assertStatus(404)
            ->assertJsonPath('code', 'CAMPAIGN_NOT_FOUND');
    }

    /** @test */
    public function pause_returns_422_if_campaign_not_in_progress(): void
    {
        $this->requireSmsTables();

        $tenant = $this->createTenant();
        $campaign = SmsCampaign::create([
            'user_id' => $tenant->id,
            'name' => 'Draft',
            'message' => 'Hi',
            'status' => 'draft',
        ]);

        Sanctum::actingAs($tenant);
        $res = $this->postJson("/api/v1/sms/campaigns/{$campaign->id}/pause");
        $res->assertStatus(422)->assertJsonPath('code', 'VALIDATION_FAILED');
    }

    /** @test */
    public function resume_continue_re_pends_paused_logs_and_reserves_credits(): void
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
            'name' => 'Resume Continue',
            'message' => 'Hello',
            'status' => 'draft',
        ]);

        Sanctum::actingAs($tenant);
        $this->postJson("/api/v1/sms/campaigns/{$campaign->id}/send", [
            'manual_phones' => ['+966500000011', '+966500000012'],
        ], ['Idempotency-Key' => 'resume-cont-' . uniqid()])->assertStatus(202);

        $this->postJson("/api/v1/sms/campaigns/{$campaign->id}/pause")->assertStatus(200);

        $res = $this->postJson("/api/v1/sms/campaigns/{$campaign->id}/resume", [
            'mode' => 'continue',
        ], ['Idempotency-Key' => 'resume-cont-key-' . uniqid()]);

        $res->assertStatus(202)
            ->assertJsonPath('data.status', 'in_progress')
            ->assertJsonPath('data.mode', 'continue')
            ->assertJsonPath('data.recipient_count', 2);

        $campaign->refresh();
        $pending = SmsMessageLog::where('campaign_id', $campaign->id)->where('status', 'pending')->count();
        $this->assertSame(2, $pending);
    }

    /** @test */
    public function resume_continue_updates_message_on_paused_logs_if_message_was_edited(): void
    {
        $this->requireSmsTables();
        $this->requireSmsPricing();

        $tenant = $this->createTenant();
        UserCredit::getOrCreateForUser($tenant->id)->update(['total_credits' => 10, 'used_credits' => 0, 'reserved_credits' => 0]);

        $campaign = SmsCampaign::create([
            'user_id' => $tenant->id,
            'name' => 'Edit Message',
            'message' => 'Original',
            'status' => 'paused',
            'reserved_credits' => 0,
        ]);
        SmsMessageLog::create([
            'user_id' => $tenant->id,
            'campaign_id' => $campaign->id,
            'recipient_phone' => '+966500000021',
            'message' => 'Original',
            'status' => 'paused',
        ]);

        Sanctum::actingAs($tenant);
        $this->patchJson("/api/v1/sms/campaigns/{$campaign->id}", ['message' => 'Updated text'])->assertStatus(200);
        $campaign->refresh();
        $this->assertSame('Updated text', $campaign->message);

        $this->postJson("/api/v1/sms/campaigns/{$campaign->id}/resume", [
            'mode' => 'continue',
        ], ['Idempotency-Key' => 'resume-edit-' . uniqid()])->assertStatus(202);

        $log = SmsMessageLog::where('campaign_id', $campaign->id)->where('status', 'pending')->first();
        $this->assertNotNull($log);
        $this->assertSame('Updated text', $log->message);
    }

    /** @test */
    public function resume_continue_fails_with_400_if_insufficient_credits(): void
    {
        $this->requireSmsTables();
        $this->requireSmsPricing();

        $tenant = $this->createTenant();
        UserCredit::getOrCreateForUser($tenant->id)->update(['total_credits' => 1, 'used_credits' => 0, 'reserved_credits' => 0]);

        $campaign = SmsCampaign::create([
            'user_id' => $tenant->id,
            'name' => 'No Credits Resume',
            'message' => 'Hi',
            'status' => 'paused',
            'reserved_credits' => 0,
        ]);
        SmsMessageLog::create([
            'user_id' => $tenant->id,
            'campaign_id' => $campaign->id,
            'recipient_phone' => '+966500000031',
            'message' => 'Hi',
            'status' => 'paused',
        ]);
        SmsMessageLog::create([
            'user_id' => $tenant->id,
            'campaign_id' => $campaign->id,
            'recipient_phone' => '+966500000032',
            'message' => 'Hi',
            'status' => 'paused',
        ]);

        Sanctum::actingAs($tenant);
        $res = $this->postJson("/api/v1/sms/campaigns/{$campaign->id}/resume", [
            'mode' => 'continue',
        ], ['Idempotency-Key' => 'resume-no-credits-' . uniqid()]);

        $res->assertStatus(400)->assertJsonPath('code', 'INSUFFICIENT_CREDITS');
    }

    /** @test */
    public function resume_restart_cancels_paused_logs_and_creates_new_ones(): void
    {
        $this->requireSmsTables();
        $this->requireSmsPricing();

        $this->mock(SmsGatewayClient::class, function (Mockery\MockInterface $mock): void {
            $mock->shouldReceive('sendText')->andReturn(new SmsGatewaySendResult(true, 'gw-1', 'test'));
            $mock->shouldReceive('verifyWebhookSignature')->andReturnTrue();
            $mock->shouldReceive('parseDeliveryWebhook')->andReturn([]);
        });

        $tenant = $this->createTenant();
        UserCredit::getOrCreateForUser($tenant->id)->update(['total_credits' => 20, 'used_credits' => 0, 'reserved_credits' => 0]);

        $campaign = SmsCampaign::create([
            'user_id' => $tenant->id,
            'name' => 'Restart',
            'message' => 'First',
            'status' => 'paused',
            'meta' => ['send_customer_ids' => [], 'send_manual_phones' => ['+966500000041', '+966500000042']],
            'reserved_credits' => 0,
        ]);
        SmsMessageLog::create([
            'user_id' => $tenant->id,
            'campaign_id' => $campaign->id,
            'recipient_phone' => '+966500000041',
            'message' => 'First',
            'status' => 'paused',
        ]);
        SmsMessageLog::create([
            'user_id' => $tenant->id,
            'campaign_id' => $campaign->id,
            'recipient_phone' => '+966500000042',
            'message' => 'First',
            'status' => 'paused',
        ]);

        Sanctum::actingAs($tenant);
        $res = $this->postJson("/api/v1/sms/campaigns/{$campaign->id}/resume", [
            'mode' => 'restart',
        ], ['Idempotency-Key' => 'resume-restart-' . uniqid()]);

        $res->assertStatus(202)
            ->assertJsonPath('data.status', 'in_progress')
            ->assertJsonPath('data.mode', 'restart')
            ->assertJsonPath('data.recipient_count', 2);

        $cancelled = SmsMessageLog::where('campaign_id', $campaign->id)->where('status', 'cancelled')->count();
        $this->assertSame(2, $cancelled);

        $pending = SmsMessageLog::where('campaign_id', $campaign->id)->where('status', 'pending')->count();
        $this->assertSame(2, $pending);

        $campaign->refresh();
        $this->assertSame(0, (int) $campaign->sent_count);
        $this->assertSame(0, (int) $campaign->failed_count);
    }

    /** @test */
    public function resume_restart_fails_with_400_if_insufficient_credits(): void
    {
        $this->requireSmsTables();
        $this->requireSmsPricing();

        $tenant = $this->createTenant();
        UserCredit::getOrCreateForUser($tenant->id)->update(['total_credits' => 1, 'used_credits' => 0, 'reserved_credits' => 0]);

        $campaign = SmsCampaign::create([
            'user_id' => $tenant->id,
            'name' => 'Restart No Credits',
            'message' => 'Hi',
            'status' => 'paused',
            'meta' => ['send_customer_ids' => [], 'send_manual_phones' => ['+966500000051', '+966500000052']],
            'reserved_credits' => 0,
        ]);
        SmsMessageLog::create([
            'user_id' => $tenant->id,
            'campaign_id' => $campaign->id,
            'recipient_phone' => '+966500000051',
            'message' => 'Hi',
            'status' => 'paused',
        ]);

        Sanctum::actingAs($tenant);
        $res = $this->postJson("/api/v1/sms/campaigns/{$campaign->id}/resume", [
            'mode' => 'restart',
        ], ['Idempotency-Key' => 'restart-no-credits-' . uniqid()]);

        $res->assertStatus(400)->assertJsonPath('code', 'INSUFFICIENT_CREDITS');
    }

    /** @test */
    public function credits_are_consumed_per_send_not_upfront(): void
    {
        $this->requireSmsTables();
        $this->requireSmsPricing();

        $callCount = 0;
        $this->mock(SmsGatewayClient::class, function (Mockery\MockInterface $mock) use (&$callCount): void {
            $mock->shouldReceive('sendText')->andReturnUsing(function () use (&$callCount) {
                $callCount++;

                return new SmsGatewaySendResult(true, 'gw-' . $callCount, 'test');
            });
            $mock->shouldReceive('verifyWebhookSignature')->andReturnTrue();
            $mock->shouldReceive('parseDeliveryWebhook')->andReturn([]);
        });

        $tenant = $this->createTenant();
        UserCredit::getOrCreateForUser($tenant->id)->update(['total_credits' => 10, 'used_credits' => 0, 'reserved_credits' => 0]);

        $campaign = SmsCampaign::create([
            'user_id' => $tenant->id,
            'name' => 'Consume Per Send',
            'message' => 'Hi',
            'status' => 'draft',
        ]);

        Sanctum::actingAs($tenant);
        $this->postJson("/api/v1/sms/campaigns/{$campaign->id}/send", [
            'manual_phones' => ['+966500000061', '+966500000062'],
        ], ['Idempotency-Key' => 'consume-test-' . uniqid()])->assertStatus(202);

        $creditsAfterSend = UserCredit::where('user_id', $tenant->id)->firstOrFail();
        $this->assertSame(2, (int) ($creditsAfterSend->reserved_credits ?? 0));
        $this->assertSame(0, (int) $creditsAfterSend->used_credits);

        Bus::fake();
        $job = new \App\Jobs\DispatchSmsCampaignJob($campaign->id);
        $job->handle(app(\App\Domain\Communication\Sms\Contracts\SmsDispatcher::class));

        $creditsAfterJob = UserCredit::where('user_id', $tenant->id)->firstOrFail();
        $this->assertSame(0, (int) ($creditsAfterJob->reserved_credits ?? 0));
        $this->assertSame(2, (int) $creditsAfterJob->used_credits);
    }

    /** @test */
    public function credits_released_on_failure_not_refunded(): void
    {
        $this->requireSmsTables();
        $this->requireSmsPricing();

        $this->mock(SmsGatewayClient::class, function (Mockery\MockInterface $mock): void {
            $mock->shouldReceive('sendText')->andReturn(new SmsGatewaySendResult(false, null, 'test', 'provider_error'));
            $mock->shouldReceive('verifyWebhookSignature')->andReturnTrue();
            $mock->shouldReceive('parseDeliveryWebhook')->andReturn([]);
        });

        $tenant = $this->createTenant();
        UserCredit::getOrCreateForUser($tenant->id)->update(['total_credits' => 10, 'used_credits' => 0, 'reserved_credits' => 0]);

        $campaign = SmsCampaign::create([
            'user_id' => $tenant->id,
            'name' => 'Failure Release',
            'message' => 'Hi',
            'status' => 'draft',
        ]);

        Sanctum::actingAs($tenant);
        $this->postJson("/api/v1/sms/campaigns/{$campaign->id}/send", [
            'manual_phones' => ['+966500000071'],
        ], ['Idempotency-Key' => 'failure-release-' . uniqid()])->assertStatus(202);

        $creditsAfterSend = UserCredit::where('user_id', $tenant->id)->firstOrFail();
        $this->assertSame(1, (int) ($creditsAfterSend->reserved_credits ?? 0));

        $job = new \App\Jobs\DispatchSmsCampaignJob($campaign->id);
        $job->handle(app(\App\Domain\Communication\Sms\Contracts\SmsDispatcher::class));

        $creditsAfterJob = UserCredit::where('user_id', $tenant->id)->firstOrFail();
        $this->assertSame(0, (int) ($creditsAfterJob->reserved_credits ?? 0));
        $this->assertSame(0, (int) $creditsAfterJob->used_credits, 'Failed message should release reserved credit, not consume.');
    }
}
