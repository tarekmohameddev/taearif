<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1\Email;

use App\Domain\Communication\Email\Contracts\EmailGatewayClient;
use App\Models\EmailMessageLog;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Tests\TestCase;

class WebhookControllerTest extends TestCase
{
    use DatabaseTransactions;

    private function requireEmailLogTables(): void
    {
        if (!Schema::hasTable('email_message_logs')) {
            $this->markTestSkipped('email_message_logs table required.');
        }
    }

    private function mockEmailGateway(bool $validSignature = true, ?array $events = null): void
    {
        $this->mock(EmailGatewayClient::class, function (Mockery\MockInterface $mock) use ($validSignature, $events): void {
            $mock->shouldReceive('verifyWebhookSignature')->andReturnUsing(function ($rawBody, $headers, $secret) use ($validSignature): bool {
                if (!$validSignature) {
                    return false;
                }
                $sig = is_array($headers['x-email-signature'] ?? null)
                    ? ($headers['x-email-signature'][0] ?? '')
                    : ($headers['x-email-signature'] ?? '');
                return hash_equals(hash_hmac('sha256', $rawBody, (string) $secret), (string) $sig);
            });
            $mock->shouldReceive('parseDeliveryWebhook')->andReturnUsing(function (array $p) use ($events): array {
                if ($events !== null) {
                    return $events;
                }
                return [[
                    'gateway_message_id' => (string) ($p['gateway_message_id'] ?? $p['events'][0]['gateway_message_id'] ?? ''),
                    'status' => (string) ($p['status'] ?? $p['events'][0]['status'] ?? ''),
                    'error_message' => $p['error_message'] ?? null,
                    'delivered_at' => now()->toIso8601String(),
                    'provider' => 'test',
                ]];
            });
        });
    }

    /** @test */
    public function webhook_updates_log_to_delivered_and_is_idempotent(): void
    {
        $this->requireEmailLogTables();
        config()->set('communication.email.webhook_secret', 'webhook-secret');

        $this->mockEmailGateway();

        $user = User::factory()->create(['account_type' => 'tenant', 'tenant_id' => null]);
        $log = EmailMessageLog::create([
            'user_id' => $user->id,
            'campaign_id' => null,
            'recipient_email' => 'test@example.com',
            'subject' => 'Test',
            'body_html' => '<p>Hi</p>',
            'status' => 'sent',
            'gateway_message_id' => 'dlr-gw-1',
        ]);

        $payload = ['gateway_message_id' => 'dlr-gw-1', 'status' => 'delivered'];
        $raw = json_encode($payload);
        $sig = hash_hmac('sha256', (string) $raw, 'webhook-secret');

        $this->postJson('/api/v1/email/webhooks/delivery', $payload, ['X-Email-Signature' => $sig])
            ->assertOk();

        $log->refresh();
        $this->assertSame('delivered', $log->status);
        $this->assertNotNull($log->delivered_at);

        $this->postJson('/api/v1/email/webhooks/delivery', $payload, ['X-Email-Signature' => $sig])
            ->assertOk();
        $log->refresh();
        $this->assertSame('delivered', $log->status);
    }

    /** @test */
    public function webhook_returns_401_for_invalid_signature(): void
    {
        $this->requireEmailLogTables();
        config()->set('communication.email.webhook_secret', 'webhook-secret');

        $this->mock(EmailGatewayClient::class, function (Mockery\MockInterface $mock): void {
            $mock->shouldReceive('verifyWebhookSignature')->andReturn(false);
            $mock->shouldNotReceive('parseDeliveryWebhook');
        });

        $this->postJson('/api/v1/email/webhooks/delivery', [
            'gateway_message_id' => 'any',
            'status' => 'delivered',
        ], ['X-Email-Signature' => 'invalid'])
            ->assertStatus(401)
            ->assertJsonPath('code', 'INVALID_SIGNATURE');
    }

    /** @test */
    public function unknown_gateway_message_id_returns_200_with_updated_zero(): void
    {
        $this->requireEmailLogTables();
        config()->set('communication.email.webhook_secret', 'webhook-secret');

        $this->mockEmailGateway();

        $payload = ['gateway_message_id' => 'nonexistent-id', 'status' => 'delivered'];
        $raw = json_encode($payload);
        $sig = hash_hmac('sha256', (string) $raw, 'webhook-secret');

        $this->postJson('/api/v1/email/webhooks/delivery', $payload, ['X-Email-Signature' => $sig])
            ->assertOk()
            ->assertJsonPath('data.updated', 0);
    }

    /** @test */
    public function status_bounced_mapped_to_failed(): void
    {
        $this->requireEmailLogTables();
        config()->set('communication.email.webhook_secret', 'webhook-secret');

        $this->mockEmailGateway(true, [[
            'gateway_message_id' => 'bounce-1',
            'status' => 'bounced',
            'error_message' => 'Mailbox full',
            'delivered_at' => null,
            'provider' => 'test',
        ]]);

        $user = User::factory()->create(['account_type' => 'tenant', 'tenant_id' => null]);
        $log = EmailMessageLog::create([
            'user_id' => $user->id,
            'campaign_id' => null,
            'recipient_email' => 'bounce@example.com',
            'subject' => 'Test',
            'body_html' => '<p>Hi</p>',
            'status' => 'sent',
            'gateway_message_id' => 'bounce-1',
        ]);

        $payload = ['gateway_message_id' => 'bounce-1', 'status' => 'bounced', 'error_message' => 'Mailbox full'];
        $raw = json_encode($payload);
        $sig = hash_hmac('sha256', (string) $raw, 'webhook-secret');

        $this->postJson('/api/v1/email/webhooks/delivery', $payload, ['X-Email-Signature' => $sig])
            ->assertOk()
            ->assertJsonPath('data.updated', 1);

        $log->refresh();
        $this->assertSame('failed', $log->status);
        $this->assertSame('Mailbox full', $log->error_message);
    }

    /** @test */
    public function status_spam_mapped_to_failed(): void
    {
        $this->requireEmailLogTables();
        config()->set('communication.email.webhook_secret', 'webhook-secret');

        $this->mockEmailGateway(true, [[
            'gateway_message_id' => 'spam-1',
            'status' => 'spam',
            'error_message' => 'Marked as spam',
            'delivered_at' => null,
            'provider' => 'test',
        ]]);

        $user = User::factory()->create(['account_type' => 'tenant', 'tenant_id' => null]);
        $log = EmailMessageLog::create([
            'user_id' => $user->id,
            'campaign_id' => null,
            'recipient_email' => 'spam@example.com',
            'subject' => 'Test',
            'body_html' => '<p>Hi</p>',
            'status' => 'sent',
            'gateway_message_id' => 'spam-1',
        ]);

        $payload = ['gateway_message_id' => 'spam-1', 'status' => 'spam'];
        $raw = json_encode($payload);
        $sig = hash_hmac('sha256', (string) $raw, 'webhook-secret');

        $this->postJson('/api/v1/email/webhooks/delivery', $payload, ['X-Email-Signature' => $sig])
            ->assertOk()
            ->assertJsonPath('data.updated', 1);

        $log->refresh();
        $this->assertSame('failed', $log->status);
    }

    /** @test */
    public function delivered_to_failed_blocked_by_status_transition_guard(): void
    {
        $this->requireEmailLogTables();
        config()->set('communication.email.webhook_secret', 'webhook-secret');

        $this->mockEmailGateway(true, [[
            'gateway_message_id' => 'guard-1',
            'status' => 'failed',
            'error_message' => 'Late failure',
            'delivered_at' => null,
            'provider' => 'test',
        ]]);

        $user = User::factory()->create(['account_type' => 'tenant', 'tenant_id' => null]);
        $log = EmailMessageLog::create([
            'user_id' => $user->id,
            'campaign_id' => null,
            'recipient_email' => 'guard@example.com',
            'subject' => 'Test',
            'body_html' => '<p>Hi</p>',
            'status' => 'delivered',
            'gateway_message_id' => 'guard-1',
        ]);

        $payload = ['gateway_message_id' => 'guard-1', 'status' => 'failed'];
        $raw = json_encode($payload);
        $sig = hash_hmac('sha256', (string) $raw, 'webhook-secret');

        $this->postJson('/api/v1/email/webhooks/delivery', $payload, ['X-Email-Signature' => $sig])
            ->assertOk()
            ->assertJsonPath('data.updated', 0);

        $log->refresh();
        $this->assertSame('delivered', $log->status);
    }

    /** @test */
    public function ambiguous_gateway_id_across_tenants_does_not_update(): void
    {
        $this->requireEmailLogTables();
        config()->set('communication.email.webhook_secret', 'webhook-secret');

        $this->mockEmailGateway();

        $userA = User::factory()->create(['account_type' => 'tenant', 'tenant_id' => null]);
        $userB = User::factory()->create(['account_type' => 'tenant', 'tenant_id' => null]);
        $sameId = 'ambiguous-gw-id';

        EmailMessageLog::create([
            'user_id' => $userA->id,
            'campaign_id' => null,
            'recipient_email' => 'a@example.com',
            'subject' => 'A',
            'body_html' => '<p>A</p>',
            'status' => 'sent',
            'gateway_message_id' => $sameId,
        ]);
        EmailMessageLog::create([
            'user_id' => $userB->id,
            'campaign_id' => null,
            'recipient_email' => 'b@example.com',
            'subject' => 'B',
            'body_html' => '<p>B</p>',
            'status' => 'sent',
            'gateway_message_id' => $sameId,
        ]);

        $payload = ['gateway_message_id' => $sameId, 'status' => 'delivered'];
        $raw = json_encode($payload);
        $sig = hash_hmac('sha256', (string) $raw, 'webhook-secret');

        $this->postJson('/api/v1/email/webhooks/delivery', $payload, ['X-Email-Signature' => $sig])
            ->assertOk()
            ->assertJsonPath('data.updated', 0);

        $this->assertSame(2, EmailMessageLog::where('gateway_message_id', $sameId)->where('status', 'sent')->count());
    }
}
