<?php

namespace App\Domain\Communication\Services;

use App\Domain\Communication\DTOs\ProviderDispatchResult;
use App\Domain\Communication\Sms\Contracts\SmsGatewayClient;
use App\Domain\Communication\WhatsApp\Services\WhatsAppChannelSender;
use App\Domain\Communication\WhatsApp\Services\WhatsAppServiceDispatchAdapter;
use App\Models\Message;
use App\Models\SmsMessageLog;
use App\Models\WaNumber;

/**
 * Transport-only retry sender. Does not touch credits or CommunicationService.
 * Used by the retry worker to resend failed attempts.
 */
class CommunicationRetrySender
{
    public function __construct(
        private readonly WhatsAppChannelSender $whatsAppChannelSender,
        private readonly WhatsAppServiceDispatchAdapter $whatsAppServiceAdapter,
        private readonly SmsGatewayClient $smsGatewayClient
    ) {}

    public function retryMessage(Message $message): ProviderDispatchResult
    {
        $conversation = $message->conversation ?? $message->conversation()->first();
        if (! $conversation || trim((string) $conversation->external_party_identifier) === '') {
            return ProviderDispatchResult::failure(false, null, 'Conversation or destination phone missing.', []);
        }

        $phone = $conversation->external_party_identifier;
        $content = is_scalar($message->content) ? (string) $message->content : (string) json_encode($message->content ?? '');
        $meta = is_array($message->meta) ? $message->meta : [];
        $waNumberId = isset($meta['wa_number_id']) ? (int) $meta['wa_number_id'] : null;

        try {
            if ($waNumberId !== null && $waNumberId > 0) {
                $waNumber = WaNumber::find($waNumberId);
                if (! $waNumber) {
                    return ProviderDispatchResult::failure(false, null, 'WhatsApp number not found.', []);
                }
                return $this->whatsAppChannelSender->send($waNumber, $phone, $content);
            }
            return $this->whatsAppServiceAdapter->send($phone, $content);
        } catch (\Throwable $e) {
            return ProviderDispatchResult::failure(false, null, $e->getMessage(), []);
        }
    }

    public function retrySmsLog(SmsMessageLog $log): ProviderDispatchResult
    {
        $result = $this->smsGatewayClient->sendText(
            $log->recipient_phone,
            $log->message,
            null,
            ['log_id' => $log->id, 'campaign_id' => $log->campaign_id]
        );

        if ($result->success) {
            return ProviderDispatchResult::success(
                $result->gatewayMessageId,
                $result->rawResponse
            );
        }

        return ProviderDispatchResult::failure(
            $result->isTransientFailure,
            null,
            $result->error ?? 'sms_provider_failed',
            $result->rawResponse
        );
    }
}
