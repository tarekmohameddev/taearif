<?php

declare(strict_types=1);

namespace Tests\Feature\V1\Sms;

use App\Domain\Communication\Sms\Contracts\SmsGatewayClient;
use App\Domain\Communication\Sms\DTOs\SmsGatewaySendResult;
use App\Models\Api\marketing\MarketingChannelPricing;
use App\Models\Api\marketing\UserCredit;
use App\Models\SmsMessageLog;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Mockery;
use Tests\TestCase;

class SmsCreditFlowTest extends TestCase
{
    use DatabaseTransactions;

    private function requireSmsTables(): void
    {
        foreach (['sms_message_logs', 'idempotency_keys', 'user_credits', 'credit_transactions'] as $table) {
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

    /** @test */
    public function single_sms_deducts_one_credit(): void
    {
        $this->requireSmsTables();
        $this->requireSmsPricing();

        $mock = $this->mock(SmsGatewayClient::class, function (Mockery\MockInterface $mock): void {
            $mock->shouldReceive('sendText')->once()->andReturn(new SmsGatewaySendResult(true, 'gw-1', 'test'));
            $mock->shouldReceive('verifyWebhookSignature')->andReturnTrue();
            $mock->shouldReceive('parseDeliveryWebhook')->andReturn([]);
        });
        $this->app->instance(SmsGatewayClient::class, $mock);

        $tenant = User::factory()->create(['account_type' => 'tenant', 'tenant_id' => null]);
        UserCredit::getOrCreateForUser($tenant->id)->update(['total_credits' => 5, 'used_credits' => 0]);
        Sanctum::actingAs($tenant);

        $this->postJson('/api/v1/sms/messages/send', [
            'recipient_phone' => '+966500000077',
            'content' => 'One credit',
        ], ['Idempotency-Key' => 'credit-flow-' . uniqid()])->assertStatus(202);

        $credits = UserCredit::where('user_id', $tenant->id)->firstOrFail();
        $this->assertSame(1, (int) $credits->used_credits);
    }

    /** @test */
    public function provider_failure_triggers_refund(): void
    {
        $this->requireSmsTables();
        $this->requireSmsPricing();

        $mock = $this->mock(SmsGatewayClient::class, function (Mockery\MockInterface $mock): void {
            $mock->shouldReceive('sendText')->andReturn(new SmsGatewaySendResult(false, null, 'test', 'provider_error'));
            $mock->shouldReceive('verifyWebhookSignature')->andReturnTrue();
            $mock->shouldReceive('parseDeliveryWebhook')->andReturn([]);
        });
        $this->app->instance(SmsGatewayClient::class, $mock);

        $tenant = User::factory()->create(['account_type' => 'tenant', 'tenant_id' => null]);
        UserCredit::getOrCreateForUser($tenant->id)->update(['total_credits' => 5, 'used_credits' => 0]);
        Sanctum::actingAs($tenant);

        $this->postJson('/api/v1/sms/messages/send', [
            'recipient_phone' => '+966500000066',
            'content' => 'Fail',
        ], ['Idempotency-Key' => 'refund-flow-' . uniqid()]);

        $log = SmsMessageLog::where('user_id', $tenant->id)->latest('id')->first();
        $this->assertNotNull($log);
        $this->assertSame('failed', $log->status);
        $credits = UserCredit::where('user_id', $tenant->id)->firstOrFail();
        $refunded = \App\Models\Api\marketing\CreditTransaction::where('user_id', $tenant->id)
            ->where('transaction_type', 'refund')
            ->where('description', 'like', '%sms_message_log%')
            ->exists();
        $this->assertTrue($refunded, 'Expected a refund transaction for failed SMS.');
    }
}
