<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Communication\Sms;

use App\Domain\Communication\Sms\Contracts\SmsGatewayClient;
use App\Domain\Communication\Sms\DTOs\SmsGatewaySendResult;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class SmsGatewayClientContractTest extends TestCase
{
    /** @test */
    public function sms_gateway_client_interface_has_required_methods(): void
    {
        $ref = new ReflectionClass(SmsGatewayClient::class);
        $this->assertTrue($ref->hasMethod('sendText'));
        $this->assertTrue($ref->hasMethod('verifyWebhookSignature'));
        $this->assertTrue($ref->hasMethod('parseDeliveryWebhook'));
    }

    /** @test */
    public function send_text_signature_accepts_to_body_from_meta_returns_send_result(): void
    {
        $ref = new ReflectionClass(SmsGatewayClient::class);
        $method = $ref->getMethod('sendText');
        $this->assertSame('sendText', $method->getName());
        $params = $method->getParameters();
        $this->assertCount(4, $params);
        $this->assertSame('to', $params[0]->getName());
        $this->assertSame('body', $params[1]->getName());
        $this->assertSame('from', $params[2]->getName());
        $this->assertSame('meta', $params[3]->getName());
        $this->assertSame(SmsGatewaySendResult::class, (string) $method->getReturnType());
    }

    /** @test */
    public function verify_webhook_signature_returns_bool(): void
    {
        $ref = new ReflectionClass(SmsGatewayClient::class);
        $method = $ref->getMethod('verifyWebhookSignature');
        $this->assertSame('bool', (string) $method->getReturnType());
    }

    /** @test */
    public function sms_gateway_send_result_has_expected_properties(): void
    {
        $result = new SmsGatewaySendResult(true, 'id-1', 'test', null, []);
        $this->assertTrue($result->success);
        $this->assertSame('id-1', $result->gatewayMessageId);
        $this->assertSame('test', $result->provider);
        $this->assertNull($result->error);
        $this->assertIsArray($result->rawResponse);
    }
}
