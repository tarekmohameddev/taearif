<?php

declare(strict_types=1);

namespace Tests\Feature\V1\Sms;

use App\Domain\Communication\Sms\Contracts\SmsGatewayClient;
use App\Models\SmsMessageLog;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Tests\TestCase;

class SmsDeliveryWebhookTest extends TestCase
{
    use DatabaseTransactions;

    private function requireSmsTables(): void
    {
        if (!Schema::hasTable('sms_message_logs')) {
            $this->markTestSkipped('sms_message_logs table required.');
        }
    }

    /** @test */
    public function webhook_updates_log_to_delivered_and_is_idempotent(): void
    {
        $this->requireSmsTables();
        config()->set('communication.sms.webhook_secret', 'webhook-secret');

        $this->mock(SmsGatewayClient::class, function (Mockery\MockInterface $mock): void {
            $mock->shouldReceive('verifyWebhookSignature')->andReturnUsing(function ($rawBody, $headers, $secret): bool {
                $sig = is_array($headers['x-sms-signature'] ?? null)
                    ? ($headers['x-sms-signature'][0] ?? '')
                    : ($headers['x-sms-signature'] ?? '');
                return hash_equals(hash_hmac('sha256', $rawBody, (string) $secret), (string) $sig);
            });
            $mock->shouldReceive('parseDeliveryWebhook')->andReturnUsing(function (array $p): array {
                return [[
                    'gateway_message_id' => (string) $p['gateway_message_id'],
                    'status' => (string) $p['status'],
                    'error_message' => null,
                    'delivered_at' => now()->toIso8601String(),
                    'provider' => 'test',
                ]];
            });
        });

        $user = User::factory()->create(['account_type' => 'tenant', 'tenant_id' => null]);
        $log = SmsMessageLog::create([
            'user_id' => $user->id,
            'campaign_id' => null,
            'recipient_phone' => '+966500000088',
            'message' => 'Hi',
            'status' => 'sent',
            'gateway_message_id' => 'dlr-gw-1',
        ]);

        $payload = ['gateway_message_id' => 'dlr-gw-1', 'status' => 'delivered'];
        $raw = json_encode($payload);
        $sig = hash_hmac('sha256', (string) $raw, 'webhook-secret');

        $this->postJson('/api/v1/sms/webhooks/delivery', $payload, ['X-SMS-Signature' => $sig])
            ->assertOk();

        $log->refresh();
        $this->assertSame('delivered', $log->status);
        $this->assertNotNull($log->delivered_at);

        $this->postJson('/api/v1/sms/webhooks/delivery', $payload, ['X-SMS-Signature' => $sig])
            ->assertOk();
        $log->refresh();
        $this->assertSame('delivered', $log->status);
    }
}
