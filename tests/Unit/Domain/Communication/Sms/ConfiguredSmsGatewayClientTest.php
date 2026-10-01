<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Communication\Sms;

use App\Domain\Communication\Sms\Services\Gateways\ConfiguredSmsGatewayClient;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class ConfiguredSmsGatewayClientTest extends TestCase
{
    private ConfiguredSmsGatewayClient $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = app(ConfiguredSmsGatewayClient::class);
    }

    /** @test */
    public function send_text_fails_closed_when_provider_is_not_registered(): void
    {
        Config::set('communication.sms.provider', null);
        Config::set('communication.enabled', true);
        Config::set('communication.sms.enabled', true);

        $client = new ConfiguredSmsGatewayClient();
        $result = $client->sendText('+966501234567', 'Hello', null, []);

        $this->assertFalse($result->success);
        $this->assertNull($result->gatewayMessageId);
        $this->assertSame('unconfigured', $result->provider);
    }

    /** @test */
    public function send_text_returns_disabled_when_sms_not_enabled(): void
    {
        Config::set('communication.sms.provider', 'twilio');
        Config::set('communication.enabled', true);
        Config::set('communication.sms.enabled', false);

        $client = new ConfiguredSmsGatewayClient();
        $result = $client->sendText('+966501234567', 'Hello', null, []);

        $this->assertFalse($result->success);
        $this->assertSame('sms_gateway_unavailable', $result->error);
    }

    /** @test */
    public function verify_webhook_signature_fails_closed_without_registered_provider(): void
    {
        Config::set('communication.sms.webhook_secret', 'secret');
        $raw = '{"gateway_message_id":"x","status":"delivered"}';
        $sig = hash_hmac('sha256', $raw, 'secret');
        $headers = ['X-SMS-Signature' => $sig];

        $this->assertFalse($this->client->verifyWebhookSignature($raw, $headers, 'secret'));
    }

    /** @test */
    public function verify_webhook_signature_returns_false_for_invalid_signature(): void
    {
        $raw = '{"gateway_message_id":"x","status":"delivered"}';
        $headers = ['X-SMS-Signature' => 'invalid'];

        $this->assertFalse($this->client->verifyWebhookSignature($raw, $headers, 'secret'));
    }

    /** @test */
    public function parse_delivery_webhook_returns_no_records_without_registered_provider(): void
    {
        $payload = [
            'gateway_message_id' => 'msg-1',
            'status' => 'delivered',
        ];
        $records = $this->client->parseDeliveryWebhook($payload);
        $this->assertCount(0, $records);
    }
}
