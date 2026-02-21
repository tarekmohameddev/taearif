<?php

declare(strict_types=1);

namespace Tests\Feature\V1\Sms;

use App\Domain\Communication\Sms\Contracts\SmsGatewayClient;
use App\Domain\Communication\Sms\DTOs\SmsGatewaySendResult;
use App\Models\Api\markting\MarketingChannelPricing;
use App\Models\Api\markting\UserCredit;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Mockery;
use Tests\TestCase;

class SmsSingleMessageSendTest extends TestCase
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
    public function single_sms_is_idempotent_and_replay_does_not_double_charge(): void
    {
        $this->requireSmsTables();
        $this->requireSmsPricing();

        $mock = $this->mock(SmsGatewayClient::class, function (Mockery\MockInterface $mock): void {
            $mock->shouldReceive('sendText')->once()->andReturn(new SmsGatewaySendResult(true, 'gw-single-1', 'test'));
            $mock->shouldReceive('verifyWebhookSignature')->andReturnTrue();
            $mock->shouldReceive('parseDeliveryWebhook')->andReturn([]);
        });
        $this->app->instance(SmsGatewayClient::class, $mock);

        $tenant = User::factory()->create(['account_type' => 'tenant', 'tenant_id' => null]);
        UserCredit::getOrCreateForUser($tenant->id)->update(['total_credits' => 5, 'used_credits' => 0]);
        Sanctum::actingAs($tenant);

        $key = 'sms-single-' . uniqid();
        $payload = [
            'recipient_phone' => '+966500000010',
            'content' => 'One SMS',
        ];

        $r1 = $this->postJson('/api/v1/sms/messages/send', $payload, ['Idempotency-Key' => $key]);
        $r2 = $this->postJson('/api/v1/sms/messages/send', $payload, ['Idempotency-Key' => $key]);

        $r1->assertStatus(202)->assertJsonPath('data.status', 'sent');
        $r2->assertStatus(202)->assertJsonPath('data.log_id', $r1->json('data.log_id'));

        $credits = UserCredit::where('user_id', $tenant->id)->firstOrFail();
        $this->assertSame(1, (int) $credits->used_credits);
    }
}

