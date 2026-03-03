<?php

namespace App\Domain\Communication\Sms\Contracts;

use App\Domain\Communication\Sms\DTOs\SmsGatewaySendResult;

interface SmsGatewayClient
{
    public function sendText(string $to, string $body, ?string $from = null, array $meta = []): SmsGatewaySendResult;

    public function verifyWebhookSignature(string $rawBody, array $headers, string $secret): bool;

    /**
     * @return array<int, array{
     *     gateway_message_id:string,
     *     status:string,
     *     error_message:?string,
     *     delivered_at:?string,
     *     provider:string
     * }>
     */
    public function parseDeliveryWebhook(array $payload): array;
}

