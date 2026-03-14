<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Communication\Sms;

use App\Domain\Communication\Contracts\CreditService;
use App\Domain\Communication\Sms\Contracts\SmsGatewayClient;
use App\Domain\Communication\Sms\DTOs\SmsGatewaySendResult;
use App\Domain\Communication\Sms\Services\SmsDispatcherService;
use App\Models\Api\marketing\UserCredit;
use App\Models\SmsCampaign;
use App\Models\SmsMessageLog;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Tests\TestCase;

class SmsDispatcherServiceTest extends TestCase
{
    use DatabaseTransactions;

    private function requireSmsTables(): void
    {
        foreach (['sms_campaigns', 'sms_message_logs', 'user_credits'] as $table) {
            if (!Schema::hasTable($table)) {
                $this->markTestSkipped("{$table} table required.");
            }
        }
    }

    /** @test */
    public function dispatch_campaign_updates_pending_logs_to_sent_and_increments_count(): void
    {
        $this->requireSmsTables();
        $gateway = Mockery::mock(SmsGatewayClient::class);
        $gateway->shouldReceive('sendText')
            ->andReturn(new SmsGatewaySendResult(true, 'gw-1', 'test'));
        $gateway->shouldReceive('verifyWebhookSignature')->andReturnTrue();
        $gateway->shouldReceive('parseDeliveryWebhook')->andReturn([]);
        $this->app->instance(SmsGatewayClient::class, $gateway);

        $user = User::factory()->create(['account_type' => 'tenant', 'tenant_id' => null]);
        UserCredit::getOrCreateForUser($user->id);
        $campaign = SmsCampaign::create([
            'user_id' => $user->id,
            'name' => 'Disp Campaign',
            'message' => 'Hi',
            'status' => 'in_progress',
        ]);
        SmsMessageLog::create([
            'user_id' => $user->id,
            'campaign_id' => $campaign->id,
            'recipient_phone' => '+966500000011',
            'message' => 'Hi',
            'status' => 'pending',
        ]);

        $dispatcher = app(SmsDispatcherService::class);
        $dispatcher->dispatchCampaign($campaign->id);

        $campaign->refresh();
        $this->assertSame('sent', $campaign->status);
        $this->assertSame(1, (int) $campaign->sent_count);
        $log = SmsMessageLog::where('campaign_id', $campaign->id)->first();
        $this->assertSame('sent', $log->status);
        $this->assertSame('gw-1', $log->gateway_message_id);
    }

    /** @test */
    public function dispatch_campaign_refunds_on_gateway_failure(): void
    {
        $this->requireSmsTables();
        $gateway = Mockery::mock(SmsGatewayClient::class);
        $gateway->shouldReceive('sendText')
            ->andReturn(new SmsGatewaySendResult(false, null, 'test', 'provider_error'));
        $gateway->shouldReceive('verifyWebhookSignature')->andReturnTrue();
        $gateway->shouldReceive('parseDeliveryWebhook')->andReturn([]);
        $this->app->instance(SmsGatewayClient::class, $gateway);

        $user = User::factory()->create(['account_type' => 'tenant', 'tenant_id' => null]);
        UserCredit::getOrCreateForUser($user->id)->update(['total_credits' => 10, 'used_credits' => 1]);
        $campaign = SmsCampaign::create([
            'user_id' => $user->id,
            'name' => 'Fail Campaign',
            'message' => 'Hi',
            'status' => 'in_progress',
        ]);
        $log = SmsMessageLog::create([
            'user_id' => $user->id,
            'campaign_id' => $campaign->id,
            'recipient_phone' => '+966500000022',
            'message' => 'Hi',
            'status' => 'pending',
        ]);

        $dispatcher = app(SmsDispatcherService::class);
        $dispatcher->dispatchCampaign($campaign->id);

        $log->refresh();
        $this->assertSame('failed', $log->status);
        $this->assertNotNull($log->refund_processed_at);
    }
}
