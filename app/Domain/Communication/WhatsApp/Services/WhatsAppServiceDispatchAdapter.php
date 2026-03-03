<?php

namespace App\Domain\Communication\WhatsApp\Services;

use App\Domain\Communication\DTOs\ProviderDispatchResult;
use App\Services\WhatsAppService;

/**
 * Wraps legacy WhatsAppService::sendMessage to return ProviderDispatchResult
 * so MessageDispatcherImpl can record attempts and persist provider_message_id consistently.
 * Legacy path does not return provider_message_id (null on success).
 */
class WhatsAppServiceDispatchAdapter
{
    public function __construct(
        private readonly WhatsAppService $whatsAppService
    ) {}

    public function send(string $phoneNumber, string $message): ProviderDispatchResult
    {
        try {
            $ok = $this->whatsAppService->sendMessage($phoneNumber, $message);
            if ($ok) {
                return ProviderDispatchResult::success(null, []);
            }
            return ProviderDispatchResult::failure(false, null, 'Legacy WhatsApp send returned false.', []);
        } catch (\Throwable $e) {
            return ProviderDispatchResult::failure(false, null, $e->getMessage(), []);
        }
    }
}
