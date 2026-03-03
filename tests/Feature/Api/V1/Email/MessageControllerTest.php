<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1\Email;

use App\Domain\Communication\Email\Contracts\EmailDispatcher;
use App\Models\Api\markting\MarketingChannelPricing;
use App\Models\Api\markting\UserCredit;
use App\Models\EmailMessageLog;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Mockery;
use Tests\TestCase;

class MessageControllerTest extends TestCase
{
    use DatabaseTransactions;

    private function requireEmailTables(): void
    {
        foreach (['email_message_logs', 'idempotency_keys', 'user_credits'] as $table) {
            if (!Schema::hasTable($table)) {
                $this->markTestSkipped("{$table} table required.");
            }
        }
    }

    private function requireEmailPricing(): void
    {
        if (!Schema::hasTable('marketing_channel_pricing')) {
            $this->markTestSkipped('marketing_channel_pricing table required.');
        }
        MarketingChannelPricing::updateOrCreate(
            ['channel_type' => 'email'],
            [
                'credits_per_message' => 1,
                'price_per_credit' => 0.05,
                'effective_price_per_message' => 0.05,
                'currency' => 'SAR',
                'is_active' => true,
                'description' => 'Email (test)',
            ]
        );
    }

    private function createTenant(): User
    {
        return User::factory()->create(['account_type' => 'tenant', 'tenant_id' => null]);
    }

    /** @test */
    public function test_it_can_send_a_direct_transactional_message(): void
    {
        $this->requireEmailTables();
        $this->requireEmailPricing();

        $tenant = $this->createTenant();
        UserCredit::getOrCreateForUser($tenant->id)->update([
            'total_credits' => 10,
            'used_credits' => 0,
            'reserved_credits' => 0,
        ]);

        $this->mock(EmailDispatcher::class, function (Mockery\MockInterface $mock): void {
            $mock->shouldReceive('dispatchSingleLog')
                ->andReturnUsing(function (int $logId): void {
                    EmailMessageLog::where('id', $logId)->update([
                        'status' => 'sent',
                        'gateway_message_id' => 'gw-' . $logId,
                        'provider' => 'test',
                        'sent_at' => now(),
                    ]);
                });
        });

        Sanctum::actingAs($tenant);
        $key = 'single-' . uniqid();
        $res = $this->postJson('/api/v1/email/messages/send', [
            'recipient_email' => 'direct@example.com',
            'subject' => 'Direct Subject',
            'body_html' => '<p>Hello</p>',
        ], ['Idempotency-Key' => $key]);

        $res->assertStatus(202)
            ->assertJsonPath('status', true)
            ->assertJsonPath('data.recipient_email', 'direct@example.com')
            ->assertJsonPath('data.status', 'sent');

        $this->assertDatabaseHas('email_message_logs', [
            'recipient_email' => 'direct@example.com',
            'subject' => 'Direct Subject',
            'status' => 'sent',
            'campaign_id' => null,
        ]);
    }

    /** @test */
    public function test_it_returns_422_when_missing_receiver_email(): void
    {
        $this->requireEmailTables();

        Sanctum::actingAs($this->createTenant());
        $res = $this->postJson('/api/v1/email/messages/send', [
            'recipient_email' => '',
            'subject' => 'S',
            'body_html' => '<p>B</p>',
        ], ['Idempotency-Key' => 'missing-email-' . uniqid()]);

        $res->assertStatus(422);
        $res->assertJsonValidationErrors(['recipient_email']);
    }

    /** @test */
    public function test_it_returns_422_when_missing_subject(): void
    {
        $this->requireEmailTables();
        $this->requireEmailPricing();

        $tenant = $this->createTenant();
        UserCredit::getOrCreateForUser($tenant->id)->update([
            'total_credits' => 10,
            'used_credits' => 0,
            'reserved_credits' => 0,
        ]);

        Sanctum::actingAs($tenant);
        $res = $this->postJson('/api/v1/email/messages/send', [
            'recipient_email' => 'valid@example.com',
            'subject' => '',
            'body_html' => '<p>B</p>',
        ], ['Idempotency-Key' => 'missing-subject-' . uniqid()]);

        $res->assertStatus(422)->assertJsonPath('message', 'Validation failed');
    }

    /** @test */
    public function test_it_returns_400_when_insufficient_credits(): void
    {
        $this->requireEmailTables();
        $this->requireEmailPricing();

        $tenant = $this->createTenant();
        UserCredit::getOrCreateForUser($tenant->id)->update([
            'total_credits' => 0,
            'used_credits' => 0,
            'reserved_credits' => 0,
        ]);

        Sanctum::actingAs($tenant);
        $res = $this->postJson('/api/v1/email/messages/send', [
            'recipient_email' => 'valid@example.com',
            'subject' => 'S',
            'body_html' => '<p>B</p>',
        ], ['Idempotency-Key' => 'no-credits-' . uniqid()]);

        $res->assertStatus(400)->assertJsonPath('code', 'INSUFFICIENT_CREDITS');
    }

    /** @test */
    public function test_it_returns_422_when_missing_idempotency_key(): void
    {
        $this->requireEmailTables();

        Sanctum::actingAs($this->createTenant());
        $res = $this->postJson('/api/v1/email/messages/send', [
            'recipient_email' => 'valid@example.com',
            'subject' => 'S',
            'body_html' => '<p>B</p>',
        ]);

        $res->assertStatus(422);
        $res->assertJsonValidationErrors(['Idempotency-Key']);
    }
}
