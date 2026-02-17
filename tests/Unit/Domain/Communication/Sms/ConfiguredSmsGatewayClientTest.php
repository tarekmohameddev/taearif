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
    public function send_text_returns_noop_success_when_provider_null_or_noop(): void
    {
        Config::set('communication.sms.provider', null);
        Config::set('communication.enabled', true);
        Config::set('communication.sms.enabled', true);

        $client = new ConfiguredSmsGatewayClient();
        $result = $client->sendText('+966501234567', 'Hello', null, []);

        $this->assertTrue($result->success);
        $this->assertNotNull($result->gatewayMessageId);
        $this->assertStringStartsWith('noop-', $result->gatewayMessageId);
        $this->assertSame('noop', $result->provider);
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
        $this->assertSame('sms_disabled', $result->error);
    }

    /** @test */
    public function verify_webhook_signature_returns_true_for_valid_hmac(): void
    {
        Config::set('communication.sms.webhook_secret', 'secret');
        $raw = '{"gateway_message_id":"x","status":"delivered"}';
        $sig = hash_hmac('sha256', $raw, 'secret');
        $headers = ['X-SMS-Signature' => $sig];

        $this->assertTrue($this->client->verifyWebhookSignature($raw, $headers, 'secret'));
    }

    /** @test */
    public function verify_webhook_signature_returns_false_for_invalid_signature(): void
    {
        $raw = '{"gateway_message_id":"x","status":"delivered"}';
        $headers = ['X-SMS-Signature' => 'invalid'];

        $this->assertFalse($this->client->verifyWebhookSignature($raw, $headers, 'secret'));
    }

    /** @test */
    public function parse_delivery_webhook_returns_single_record_for_flat_payload(): void
    {
        $payload = [
            'gateway_message_id' => 'msg-1',
            'status' => 'delivered',
        ];
        $records = $this->client->parseDeliveryWebhook($payload);
        $this->assertCount(1, $records);
        $this->assertSame('msg-1', $records[0]['gateway_message_id']);
        $this->assertSame('delivered', $records[0]['status']);
    }
}
