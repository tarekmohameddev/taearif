<?php

namespace App\Domain\Communication\Services;

use App\Domain\Communication\Contracts\MessageDispatcher;
use App\Domain\Communication\DTOs\ProviderDispatchResult;
use App\Domain\Communication\Exceptions\ProviderSendFailedException;
use App\Domain\Communication\WhatsApp\Services\WhatsAppChannelSender;
use App\Domain\Communication\WhatsApp\Services\WhatsAppServiceDispatchAdapter;
use App\Models\Message;
use App\Models\WaNumber;
use Illuminate\Support\Facades\Log;

class MessageDispatcherImpl implements MessageDispatcher
{
    public function __construct(
        private readonly WhatsAppServiceDispatchAdapter $whatsAppServiceAdapter,
        private readonly WhatsAppChannelSender $whatsAppChannelSender,
        private readonly ?DeliveryAttemptRecorder $deliveryAttemptRecorder = null
    ) {}

    public function dispatch(Message $message): void
    {
        $conversation = $message->conversation;
        if (! $conversation) {
            $conversation = $message->conversation()->first();
        }
        if (! $conversation || trim((string) $conversation->external_party_identifier) === '') {
            $message->update(['status' => 'failed']);
            throw new ProviderSendFailedException('Conversation or destination phone missing.');
        }

        if ($message->status !== 'queued') {
            Log::warning('Message not queued, aborting dispatch', [
                'channel' => 'communication_dispatch',
                'message_id' => $message->id,
                'status' => $message->status,
            ]);
            throw new ProviderSendFailedException('Message not queued, aborting dispatch.');
        }

        $phone = $conversation->external_party_identifier;
        $content = is_scalar($message->content) ? (string) $message->content : (string) json_encode($message->content ?? '');
        $meta = is_array($message->meta) ? $message->meta : [];
        $waNumberId = isset($meta['wa_number_id']) ? (int) $meta['wa_number_id'] : null;

        $result = null;
        try {
            if ($waNumberId !== null && $waNumberId > 0) {
                $waNumber = WaNumber::find($waNumberId);
                if (! $waNumber) {
                    $message->update(['status' => 'failed']);
                    throw new ProviderSendFailedException('WhatsApp number not found for dispatch.');
                }
                $result = $this->whatsAppChannelSender->send($waNumber, $phone, $content);
            } else {
                if (($meta['source'] ?? null) === 'ai') {
                    $message->update(['status' => 'failed']);
                    throw new ProviderSendFailedException('AI-sourced message has no wa_number_id: cannot determine sender. Set wa_number_id in message meta before dispatching AI replies.');
                }
                $result = $this->whatsAppServiceAdapter->send($phone, $content);
            }
        } catch (ProviderSendFailedException $e) {
            $message->update(['status' => 'failed']);
            if ($this->shouldRecordAttempts()) {
                $this->recordFailureAttempt($message, $waNumberId, ProviderDispatchResult::failure(false, null, $e->getMessage(), []));
            }
            throw $e;
        } catch (\Throwable $e) {
            $message->update(['status' => 'failed']);
            if ($this->shouldRecordAttempts()) {
                $this->recordFailureAttempt($message, $waNumberId, ProviderDispatchResult::failure(false, null, $e->getMessage(), []));
            }
            throw new ProviderSendFailedException('WhatsApp send exception: ' . $e->getMessage(), 0, $e);
        }

        if ($result->success) {
            $providerMessageId = $result->provider_message_id ?? null;
            $affected = Message::where('id', $message->id)
                ->where('status', 'queued')
                ->update([
                    'status' => 'sent',
                    'provider_message_id' => $providerMessageId,
                ]);

            if ($affected === 0) {
                Log::warning('Message already updated, possible race condition', [
                    'channel' => 'communication_dispatch',
                    'message_id' => $message->id,
                ]);
                throw new ProviderSendFailedException('Message state conflict: already updated.');
            }

            if ($this->shouldRecordAttempts()) {
                $this->deliveryAttemptRecorder->recordMessageAttempt(
                    $message->fresh(),
                    'whatsapp',
                    $this->resolveProvider($waNumberId),
                    $result,
                    false,
                    $waNumberId > 0 ? $waNumberId : null,
                    null,
                    $result->raw_response
                );
            }
            return;
        }

        $message->update(['status' => 'failed']);
        if ($this->shouldRecordAttempts()) {
            $this->recordFailureAttempt($message, $waNumberId, $result);
        }
        throw new ProviderSendFailedException($result->error_message ?? 'WhatsApp send returned failure.');
    }

    private function shouldRecordAttempts(): bool
    {
        return config('communication.reliability.enabled', false) && $this->deliveryAttemptRecorder !== null;
    }

    private function recordFailureAttempt(Message $message, ?int $waNumberId, ProviderDispatchResult $result): void
    {
        $m = $message->fresh();
        if (! $m) {
            return;
        }
        $this->deliveryAttemptRecorder->recordMessageAttempt(
            $m,
            'whatsapp',
            $this->resolveProvider($waNumberId),
            $result,
            false,
            $waNumberId > 0 ? $waNumberId : null,
            null,
            $result->raw_response
        );
    }

    private function resolveProvider(?int $waNumberId): string
    {
        if ($waNumberId !== null && $waNumberId > 0) {
            $wa = WaNumber::find($waNumberId);
            return $wa ? strtolower((string) $wa->provider) : 'meta';
        }
        return config('communication.whatsapp.provider', 'meta');
    }
}
