<?php

namespace App\Domain\Communication\WhatsApp\Services;

use App\Domain\Communication\Contracts\CommunicationService;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\WaConversationState;
use App\Models\WaNumber;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Modules\WhatsappAI\Entities\WhatsappConversation;
use Modules\WhatsappAI\Entities\WhatsappMessage;

class SyncWhatsappAiConversationToCommunicationService
{
    public function __construct(
        private readonly CommunicationService $communicationService,
        private readonly WhatsAppWebhookService $webhookService,
    ) {}

    /**
     * Mirror a WhatsappAI conversation message into Communication v1 tables.
     *
     * @param  array<string, mixed>|null  $webhookMetadata  Meta webhook metadata (phone_number_id, display_phone_number)
     */
    public function sync(
        WhatsappConversation $aiConversation,
        WhatsappMessage $aiMessage,
        ?array $webhookMetadata = null,
        bool $incrementUnread = true,
    ): ?Message {
        $userId = (int) $aiConversation->user_id;
        if ($userId <= 0) {
            Log::warning('whatsapp_ai.communication_sync.skipped', [
                'reason' => 'missing_user_id',
                'whatsapp_conversation_id' => $aiConversation->id,
                'whatsapp_message_id' => $aiMessage->id,
            ]);

            return null;
        }

        $externalParty = $this->normalizePhone((string) $aiConversation->customer_phone);
        $content = $this->resolveContent($aiMessage);
        $providerMessageId = $this->resolveProviderMessageId($aiMessage);

        $waNumberId = $this->resolveWaNumberId($aiConversation, $webhookMetadata);

        $meta = [
            'source' => 'whatsapp_ai_webhook',
            'from' => $externalParty,
            'customer_phone' => $externalParty,
            'whatsapp_ai_conversation_id' => (int) $aiConversation->id,
            'whatsapp_ai_message_id' => (int) $aiMessage->id,
            'whatsapp_message_type' => (string) $aiMessage->message_type,
            'media_url' => $aiMessage->media_url,
            'raw_payload' => $aiMessage->raw_payload,
        ];
        if ($waNumberId !== null) {
            $meta['wa_number_id'] = $waNumberId;
        }
        if (! $incrementUnread) {
            $meta['skip_unread_increment'] = true;
        }

        $existingBefore = null;
        if ($providerMessageId !== null) {
            $existingBefore = Message::query()
                ->where('user_id', $userId)
                ->where('provider_message_id', $providerMessageId)
                ->first();
        }

        $message = $this->communicationService->recordInboundMessage(
            $userId,
            $externalParty,
            $content,
            'whatsapp',
            $providerMessageId,
            $meta,
            $aiMessage->created_at ? Carbon::parse($aiMessage->created_at) : null,
            $incrementUnread,
        );

        if ($message === null) {
            Log::warning('whatsapp_ai.communication_sync.failed', [
                'whatsapp_conversation_id' => $aiConversation->id,
                'whatsapp_message_id' => $aiMessage->id,
                'user_id' => $userId,
            ]);

            return null;
        }

        $wasNew = $existingBefore === null;

        if ($wasNew && $aiMessage->created_at !== null) {
            $this->applyHistoricalTimestamps($message, Carbon::parse($aiMessage->created_at));
        }

        Log::info('whatsapp_ai.communication_sync.completed', [
            'whatsapp_conversation_id' => $aiConversation->id,
            'whatsapp_message_id' => $aiMessage->id,
            'communication_message_id' => $message->id,
            'communication_conversation_id' => $message->conversation_id,
            'was_new' => $wasNew,
            'wa_number_id' => $waNumberId,
        ]);

        return $message->fresh();
    }

    /**
     * Mirror an outbound echo (message sent from WhatsApp Business App) into Communication v1 tables.
     *
     * @param  array<string, mixed>|null  $webhookMetadata  Meta webhook metadata (phone_number_id, display_phone_number)
     */
    public function syncOutbound(
        WhatsappConversation $aiConversation,
        WhatsappMessage $aiMessage,
        ?array $webhookMetadata = null,
    ): ?Message {
        $userId = (int) $aiConversation->user_id;
        if ($userId <= 0) {
            Log::warning('whatsapp_ai.communication_sync_outbound.skipped', [
                'reason' => 'missing_user_id',
                'whatsapp_conversation_id' => $aiConversation->id,
                'whatsapp_message_id' => $aiMessage->id,
            ]);

            return null;
        }

        $externalParty = $this->normalizePhone((string) $aiConversation->customer_phone);
        $content = $this->resolveContent($aiMessage);
        $providerMessageId = $this->resolveProviderMessageId($aiMessage);

        $waNumberId = $this->resolveWaNumberId($aiConversation, $webhookMetadata);

        $meta = [
            'source' => 'whatsapp_echo',
            'whatsapp_ai_conversation_id' => (int) $aiConversation->id,
            'whatsapp_ai_message_id' => (int) $aiMessage->id,
            'whatsapp_message_type' => (string) $aiMessage->message_type,
            'media_url' => $aiMessage->media_url,
            'raw_payload' => $aiMessage->raw_payload,
        ];
        if ($waNumberId !== null) {
            $meta['wa_number_id'] = $waNumberId;
        }

        // Check if message already exists (e.g., sent via API)
        $existing = Message::query()
            ->where('user_id', $userId)
            ->where('provider_message_id', $providerMessageId)
            ->first();

        if ($existing !== null) {
            Log::info('whatsapp_ai.communication_sync_outbound.already_exists', [
                'whatsapp_conversation_id' => $aiConversation->id,
                'whatsapp_message_id' => $aiMessage->id,
                'existing_message_id' => $existing->id,
            ]);

            return $existing;
        }

        $message = $this->communicationService->recordOutboundFromEcho(
            $userId,
            $externalParty,
            $content,
            'whatsapp',
            $providerMessageId,
            $meta,
            $aiMessage->created_at ? Carbon::parse($aiMessage->created_at) : null,
        );

        if ($message === null) {
            Log::warning('whatsapp_ai.communication_sync_outbound.failed', [
                'whatsapp_conversation_id' => $aiConversation->id,
                'whatsapp_message_id' => $aiMessage->id,
                'user_id' => $userId,
            ]);

            return null;
        }

        if ($aiMessage->created_at !== null) {
            $this->applyHistoricalTimestamps($message, Carbon::parse($aiMessage->created_at));
        }

        Log::info('whatsapp_ai.communication_sync_outbound.completed', [
            'whatsapp_conversation_id' => $aiConversation->id,
            'whatsapp_message_id' => $aiMessage->id,
            'communication_message_id' => $message->id,
            'communication_conversation_id' => $message->conversation_id,
            'wa_number_id' => $waNumberId,
        ]);

        return $message->fresh();
    }

    private function resolveProviderMessageId(WhatsappMessage $aiMessage): string
    {
        if ($aiMessage->whatsapp_message_id !== null && trim((string) $aiMessage->whatsapp_message_id) !== '') {
            return (string) $aiMessage->whatsapp_message_id;
        }

        return 'whatsapp_ai:'.$aiMessage->id;
    }

    private function resolveWaNumberId(WhatsappConversation $aiConversation, ?array $webhookMetadata): ?int
    {
        $userId = (int) $aiConversation->user_id;
        if ($webhookMetadata === null || $webhookMetadata === []) {
            return $this->resolveWaNumberIdFromWhatsappUser($aiConversation);
        }

        $tenant = $this->webhookService->resolveTenantFromPayload([
            'metadata' => $webhookMetadata,
            'phone_number_id' => $webhookMetadata['phone_number_id'] ?? null,
            'display_phone_number' => $webhookMetadata['display_phone_number'] ?? null,
        ], 'meta');

        if ($tenant === null || (int) ($tenant['user_id'] ?? 0) !== $userId) {
            return null;
        }

        $waNumberId = (int) ($tenant['wa_number_id'] ?? 0);

        if ($waNumberId > 0) {
            return $waNumberId;
        }

        return $this->resolveWaNumberIdFromWhatsappUser($aiConversation);
    }

    private function resolveWaNumberIdFromWhatsappUser(WhatsappConversation $aiConversation): ?int
    {
        $whatsappUser = $aiConversation->relationLoaded('whatsappUser')
            ? $aiConversation->whatsappUser
            : $aiConversation->whatsappUser()->first();

        if (! $whatsappUser) {
            return null;
        }

        $query = WaNumber::query()
            ->where('user_id', (int) $aiConversation->user_id)
            ->where('provider', 'meta');

        if (! empty($whatsappUser->phone_id)) {
            $waNumber = (clone $query)
                ->where('phone_number_id', (string) $whatsappUser->phone_id)
                ->first();
            if ($waNumber) {
                return (int) $waNumber->id;
            }
        }

        if (! empty($whatsappUser->number)) {
            $normalized = $this->normalizePhone((string) $whatsappUser->number);
            $waNumber = (clone $query)
                ->where('phone_number', $normalized)
                ->first();
            if ($waNumber) {
                return (int) $waNumber->id;
            }
        }

        return null;
    }

    private function resolveContent(WhatsappMessage $aiMessage): string
    {
        $content = is_string($aiMessage->content) ? trim($aiMessage->content) : '';
        if ($content !== '') {
            return $content;
        }

        return match ((string) $aiMessage->message_type) {
            'image' => '[image]',
            'audio' => '[audio]',
            'video' => '[video]',
            'document' => '[document]',
            'location' => '[location]',
            'contacts' => '[contacts]',
            'reaction' => '[reaction]',
            'edit' => '[edit]',
            default => '[message]',
        };
    }

    private function normalizePhone(string $value): string
    {
        $value = trim($value);
        $value = preg_replace('/[\s\-]+/', '', $value) ?? $value;
        if (preg_match('/^\+?\d+$/', $value)) {
            $value = ltrim($value, '+');
            if (strlen($value) > 0 && $value[0] !== '0') {
                $value = '+'.$value;
            }
        }

        return $value;
    }

    private function applyHistoricalTimestamps(Message $message, Carbon $createdAt): void
    {
        $message->forceFill([
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ])->saveQuietly();

        $conversation = Conversation::find($message->conversation_id);
        if ($conversation !== null) {
            $lastAt = $conversation->last_message_at;
            if ($lastAt === null || $createdAt->greaterThan(Carbon::parse($lastAt))) {
                $conversation->forceFill(['last_message_at' => $createdAt])->saveQuietly();
            }
        }

        $state = WaConversationState::where('conversation_id', $message->conversation_id)->first();
        if ($state !== null) {
            $preview = is_scalar($message->content)
                ? (string) $message->content
                : (string) json_encode($message->content ?? '');
            $lastTime = $state->last_message_time;
            if ($lastTime === null || $createdAt->greaterThan(Carbon::parse($lastTime))) {
                $state->forceFill([
                    'last_message_preview' => Str::limit($preview, 500),
                    'last_message_time' => $createdAt,
                ])->saveQuietly();
            }
        }
    }
}
