<?php

declare(strict_types=1);

namespace Tests\Traits;

use App\Domain\Communication\Email\Contracts\EmailDispatcher;
use App\Domain\Communication\Sms\Contracts\SmsGatewayClient;
use App\Domain\Communication\Sms\DTOs\SmsGatewaySendResult;
use App\Services\WhatsAppService;
use Mockery;

/**
 * Helpers to mock external services (WhatsApp, SMS, Email) in tests.
 * Use these so tests do not hit real gateways or third-party APIs.
 *
 * @see docs/testing-mocks-guide.md
 */
trait MocksExternalServices
{
    /** Mock WhatsApp so sendMessage is not called for real. */
    protected function mockWhatsAppService(bool $success = true): void
    {
        $this->mock(WhatsAppService::class, function (Mockery\MockInterface $mock) use ($success): void {
            $mock->shouldReceive('sendMessage')->andReturn($success);
        });
    }

    /** Mock SMS gateway so sendText and webhook helpers are no-ops / controlled. */
    protected function mockSmsGatewayClient(bool $sendSuccess = true, ?string $messageId = 'gw-test'): void
    {
        $this->mock(SmsGatewayClient::class, function (Mockery\MockInterface $mock) use ($sendSuccess, $messageId): void {
            $mock->shouldReceive('sendText')
                ->andReturn(new SmsGatewaySendResult($sendSuccess, $messageId, 'test'));
            $mock->shouldReceive('verifyWebhookSignature')->andReturnTrue();
            $mock->shouldReceive('parseDeliveryWebhook')->andReturn([]);
        });
    }

    /** Mock email dispatcher so campaign send does not hit real gateway. */
    protected function mockEmailDispatcherForCampaignSend(): void
    {
        $this->mock(EmailDispatcher::class, function (Mockery\MockInterface $mock): void {
            $mock->shouldReceive('dispatchCampaign')->andReturnNull();
        });
    }
}
