<?php

namespace App\Domain\Communication\Email\Contracts;

use App\Domain\Communication\Email\DTOs\EmailGatewaySendResult;

interface EmailGatewayClient
{
    public function sendEmail(
        string $toEmail,
        string $subject,
        string $bodyHtml,
        ?string $bodyText = null,
        ?string $fromEmail = null,
        ?string $fromName = null,
        array $meta = []
    ): EmailGatewaySendResult;

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

