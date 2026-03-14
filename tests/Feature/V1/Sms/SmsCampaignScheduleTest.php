<?php

declare(strict_types=1);

namespace Tests\Feature\V1\Sms;

use App\Domain\Communication\Sms\Contracts\SmsGatewayClient;
use App\Domain\Communication\Sms\DTOs\SmsGatewaySendResult;
use App\Models\Api\marketing\MarketingChannelPricing;
use App\Models\Api\marketing\UserCredit;
use App\Models\SmsCampaign;
use App\Models\SmsMessageLog;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Domain\Communication\Sms\Contracts\SmsDispatcher;
use App\Jobs\DispatchSmsCampaignJob;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Mockery;
use Tests\TestCase;

class SmsCampaignScheduleTest extends TestCase
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
    public function scheduled_campaign_send_returns_202_and_stays_scheduled(): void
    {
        $this->requireSmsTables();
        $this->requireSmsPricing();

        $tenant = $this->createTenant();
        UserCredit::getOrCreateForUser($tenant->id)->update(['total_credits' => 10, 'used_credits' => 0, 'reserved_credits' => 0]);

        $campaign = SmsCampaign::create([
            'user_id' => $tenant->id,
            'name' => 'Scheduled Campaign',
            'message' => 'Later',
            'status' => 'draft',
            'scheduled_at' => now()->addHours(2),
        ]);

        Sanctum::actingAs($tenant);
        $this->postJson("/api/v1/sms/campaigns/{$campaign->id}/send", [
            'manual_phones' => ['+966500000001'],
        ], ['Idempotency-Key' => 'sched-key-' . uniqid()])->assertStatus(202);

        $campaign->refresh();
        $this->assertSame('scheduled', $campaign->status);
        $this->assertSame(1, SmsMessageLog::where('campaign_id', $campaign->id)->count());
    }

    /** @test */
    public function scheduler_command_processes_due_campaigns(): void
    {
        $this->requireSmsTables();
        $this->requireSmsPricing();

        $this->mock(SmsGatewayClient::class, function (Mockery\MockInterface $mock): void {
            $mock->shouldReceive('sendText')->andReturn(new SmsGatewaySendResult(true, 'gw-sched', 'test'));
            $mock->shouldReceive('verifyWebhookSignature')->andReturnTrue();
            $mock->shouldReceive('parseDeliveryWebhook')->andReturn([]);
        });

        $tenant = $this->createTenant();
        UserCredit::getOrCreateForUser($tenant->id)->update(['total_credits' => 10, 'used_credits' => 0, 'reserved_credits' => 0]);

        $campaign = SmsCampaign::create([
            'user_id' => $tenant->id,
            'name' => 'Due Campaign',
            'message' => 'Now',
            'status' => 'scheduled',
            'scheduled_at' => now()->subMinute(),
            'dispatch_reference' => (string) \Illuminate\Support\Str::uuid(),
            'reserved_credits' => 1,
        ]);
        UserCredit::where('user_id', $tenant->id)->update(['reserved_credits' => 1]);
        SmsMessageLog::create([
            'user_id' => $tenant->id,
            'campaign_id' => $campaign->id,
            'recipient_phone' => '+966500000099',
            'message' => 'Now',
            'status' => 'pending',
        ]);

        Artisan::call('sms:process-scheduled-campaigns');

        $job = new DispatchSmsCampaignJob($campaign->id);
        $job->handle(app(SmsDispatcher::class));

        $campaign->refresh();
        $this->assertSame('sent', $campaign->status);
    }
}
