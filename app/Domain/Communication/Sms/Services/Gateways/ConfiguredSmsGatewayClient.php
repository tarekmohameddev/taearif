<?php

namespace App\Domain\Communication\Sms\Services\Gateways;

use App\Domain\Communication\Sms\Contracts\SmsGatewayClient;
use App\Domain\Communication\Sms\Contracts\SmsGatewayDriver;
use App\Domain\Communication\Sms\Contracts\SmsGatewayReadiness;
use App\Domain\Communication\Sms\DTOs\SmsGatewaySendResult;
use Illuminate\Support\Facades\App;

/** Resolves only explicitly registered provider adapters; no simulated/default success path exists. */
class ConfiguredSmsGatewayClient implements SmsGatewayClient, SmsGatewayReadiness
{
    private function driver(): ?SmsGatewayDriver
    {
        $name = $this->configuredProvider();
        $class = $name ? config("communication.sms.drivers.{$name}") : null;
        if (! is_string($class) || ! class_exists($class)) return null;
        $driver = App::make($class);
        return $driver instanceof SmsGatewayDriver ? $driver : null;
    }

    public function isReady(): bool
    {
        $driver = $this->driver();
        return $driver !== null && $this->driverIsReady($driver);
    }

    private function driverIsReady(SmsGatewayDriver $driver): bool
    {
        return (bool) config('communication.enabled', false)
            && (bool) config('communication.sms.enabled', false)
            && $driver->providerName() === $this->configuredProvider()
            && $driver->isConfigured();
    }

    public function configuredProvider(): ?string
    {
        $provider = trim((string) config('communication.sms.provider', ''));
        return $provider !== '' ? $provider : null;
    }

    public function sendText(string $to, string $body, ?string $from = null, array $meta = []): SmsGatewaySendResult
    {
        $driver = $this->driver();
        if ($driver === null || ! $this->driverIsReady($driver)) {
            return new SmsGatewaySendResult(false, null, $this->configuredProvider() ?? 'unconfigured', 'sms_gateway_unavailable');
        }
        $result = $driver->sendText($to, $body, $from, $meta);
        if ($result->success && (empty($result->gatewayMessageId) || $result->provider !== $driver->providerName())) {
            return new SmsGatewaySendResult(false, null, $driver->providerName(), 'sms_provider_response_invalid');
        }
        return $result;
    }

    public function verifyWebhookSignature(string $rawBody, array $headers, string $secret): bool
    {
        $driver = $this->driver();
        return $driver !== null && $this->driverIsReady($driver) && $driver->verifyWebhookSignature($rawBody, $headers, $secret);
    }

    public function parseDeliveryWebhook(array $payload): array
    {
        $driver = $this->driver();
        return $driver !== null && $this->driverIsReady($driver) ? $driver->parseDeliveryWebhook($payload) : [];
    }
}
