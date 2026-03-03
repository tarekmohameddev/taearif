<?php

declare(strict_types=1);

namespace Tests\Feature\V1\WhatsApp;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class WhatsAppWebhookVerifyTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function verify_returns_challenge_when_token_matches(): void
    {
        Config::set('communication.whatsapp.webhook_verify_token', 'my-secret-token');

        $res = $this->get('/api/v1/whatsapp/webhook/verify?' . http_build_query([
            'hub_mode' => 'subscribe',
            'hub_verify_token' => 'my-secret-token',
            'hub_challenge' => 'challenge-123',
        ]));

        $res->assertOk();
        $res->assertHeader('Content-Type', 'text/plain; charset=UTF-8');
        $res->assertSee('challenge-123');
    }

    /** @test */
    public function verify_returns_403_when_token_mismatch(): void
    {
        Config::set('communication.whatsapp.webhook_verify_token', 'my-secret-token');

        $res = $this->get('/api/v1/whatsapp/webhook/verify?' . http_build_query([
            'hub_mode' => 'subscribe',
            'hub_verify_token' => 'wrong-token',
            'hub_challenge' => 'challenge-123',
        ]));

        $res->assertStatus(403);
    }
}
