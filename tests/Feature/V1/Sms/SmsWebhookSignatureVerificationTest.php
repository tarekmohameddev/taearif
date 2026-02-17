<?php

declare(strict_types=1);

namespace Tests\Feature\V1\Sms;

use App\Domain\Communication\Sms\Contracts\SmsGatewayClient;
use App\Domain\Communication\Sms\DTOs\SmsGatewaySendResult;
use App\Models\SmsMessageLog;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Tests\TestCase;

class SmsWebhookSignatureVerificationTest extends TestCase
{
    use DatabaseTransactions;

    private function requireSmsTables(): void
    {
        if (!Schema::hasTable('sms_message_logs')) {
            $this->markTestSkipped('sms_message_logs table required.');
        }
    }

    /** @test */
    public function webhook_rejects_invalid_signature_and_accepts_valid_signature(): void
    {
        $this->requireSmsTables();
        config()->set('communication.sms.webhook_secret', 'test-secret');

        $this->mock(SmsGatewayClient::class, function (Mockery\MockInterface $mock): void {
            $mock->shouldReceive('sendText')->andReturn(new SmsGatewaySendResult(true, 'x', 'test'));
            $mock->shouldReceive('verifyWebhookSignature')->andReturnUsing(function ($rawBody, $headers, $secret): bool {
                $signature = is_array($headers['x-sms-signature'] ?? null)
                    ? ($headers['x-sms-signature'][0] ?? '')
                    : ($headers['x-sms-signature'] ?? '');
                $expected = hash_hmac('sha256', $rawBody, (string) $secret);
                return hash_equals($expected, (string) $signature);
            });
            $mock->shouldReceive('parseDeliveryWebhook')->andReturnUsing(function (array $payload): array {
                return [[
                    'gateway_message_id' => (string) $payload['gateway_message_id'],
                    'status' => (string) $payload['status'],
                    'error_message' => null,
                    'delivered_at' => null,
                    'provider' => 'test',
                ]];
            });
        });

        $user = User::factory()->create(['account_type' => 'tenant', 'tenant_id' => null]);
        $log = SmsMessageLog::create([
            'user_id' => $user->id,
            'campaign_id' => null,
            'customer_id' => null,
            'recipient_phone' => '+966500000099',
            'message' => 'hello',
            'status' => 'sent',
            'gateway_message_id' => 'gw-webhook-1',
        ]);

        $payload = ['gateway_message_id' => 'gw-webhook-1', 'status' => 'delivered'];
        $raw = json_encode($payload);

        $this->postJson('/api/v1/sms/webhooks/delivery', $payload, ['X-SMS-Signature' => 'invalid'])
            ->assertStatus(401);

        $signature = hash_hmac('sha256', (string) $raw, 'test-secret');
        $this->postJson('/api/v1/sms/webhooks/delivery', $payload, ['X-SMS-Signature' => $signature])
            ->assertOk();

        $log->refresh();
        $this->assertSame('delivered', $log->status);
    }
}

