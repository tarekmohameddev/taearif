<?php

namespace Modules\WhatsappAI\Jobs;

use App\Domain\RealEstateAgent\Brain\Employee;
use App\Models\Message;
use App\Models\WaAiConfig;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\WhatsappAI\Entities\WhatsappMessage;
use OpenAI;

class TranscribeAudio implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Allow two attempts; a transient network/API failure on the first try
     * should not permanently block transcription.
     */
    public int $tries = 2;

    /**
     * Give the job up to 2 minutes — audio download + Whisper can be slow.
     */
    public int $timeout = 120;

    public function __construct(private int $messageId) {}

    public function handle(): void
    {
        $message = WhatsappMessage::with(['conversation.whatsappUser'])
            ->find($this->messageId);

        if (!$message || $message->message_type !== 'audio') {
            return;
        }

        // Skip if already transcribed (content is no longer the placeholder).
        if ($message->content !== '[Audio message]') {
            return;
        }

        $whatsappUser = $message->conversation?->whatsappUser;

        // Prefer the dedicated access_token; fall back to the general token field.
        $accessToken = $whatsappUser?->access_token ?? $whatsappUser?->token;

        if (!$accessToken) {
            Log::warning('TranscribeAudio: no Meta access token available — skipping transcription', [
                'message_id'       => $this->messageId,
                'whatsapp_user_id' => $whatsappUser?->id,
            ]);
            return;
        }

        // The media ID is stored in the raw webhook payload under audio.id.
        $payload = $message->raw_payload ?? [];
        $mediaId  = $payload['audio']['id'] ?? null;

        if (!$mediaId) {
            Log::warning('TranscribeAudio: no media ID in raw_payload', ['message_id' => $this->messageId]);
            return;
        }

        $tmpPath = null;

        try {
            // 1. Resolve the temporary download URL from Meta's Graph API.
            $graphVersion = config('whatsappai.graph_api_version', 'v18.0');
            $metaResponse = Http::withToken($accessToken)
                ->get("https://graph.facebook.com/{$graphVersion}/{$mediaId}");

            if (!$metaResponse->ok()) {
                Log::error('TranscribeAudio: Meta API failed to return media URL', [
                    'message_id' => $this->messageId,
                    'media_id'   => $mediaId,
                    'status'     => $metaResponse->status(),
                    'body'       => $metaResponse->body(),
                ]);
                return;
            }

            $mediaUrl = $metaResponse->json('url');
            $mimeType = $metaResponse->json('mime_type', 'audio/ogg');

            if (!$mediaUrl) {
                Log::error('TranscribeAudio: Meta API returned no download URL', [
                    'message_id' => $this->messageId,
                    'media_id'   => $mediaId,
                ]);
                return;
            }

            // 2. Download the audio binary.
            $audioResponse = Http::withToken($accessToken)->get($mediaUrl);

            if (!$audioResponse->ok()) {
                Log::error('TranscribeAudio: failed to download audio from Meta', [
                    'message_id' => $this->messageId,
                    'status'     => $audioResponse->status(),
                ]);
                return;
            }

            // Write to a temp file so we can stream it to the Whisper API.
            $extension = $this->mimeToExtension($mimeType);
            $tmpPath   = sys_get_temp_dir() . '/wa_audio_' . $this->messageId . '_' . uniqid() . '.' . $extension;
            file_put_contents($tmpPath, $audioResponse->body());

            // 3. Transcribe with OpenAI Whisper.
            $apiKey = config('openai.api_key');

            if (empty($apiKey) || !str_starts_with($apiKey, 'sk-')) {
                Log::error('TranscribeAudio: OpenAI API key is missing or invalid', [
                    'message_id' => $this->messageId,
                ]);
                return;
            }

            $client       = OpenAI::client($apiKey);
            $whisperModel = config('whatsappai.whisper_model', 'whisper-1');

            $response = $client->audio()->transcribe([
                'model'           => $whisperModel,
                'file'            => fopen($tmpPath, 'r'),
                'response_format' => 'json',
                // No 'language' key — let Whisper auto-detect to support any language.
            ]);

            $text = trim($response->text ?? '');

            if (empty($text)) {
                Log::info('TranscribeAudio: Whisper returned empty transcription', [
                    'message_id' => $this->messageId,
                ]);
                return;
            }

            // 4. Persist transcription in the module message. Prefix makes it clear the text is from audio.
            $message->update(['content' => '[صوتي: ' . $text . ']']);

            Log::info('TranscribeAudio: transcription saved', [
                'message_id' => $this->messageId,
                'length'     => mb_strlen($text),
            ]);

            // 5. Write transcription back to the Communication v1 Message so the bot can read it.
            $v1Message = $this->writeBackToV1Message($message, $text);

            // 6. Trigger bot turn now that transcription is complete.
            $this->triggerBotIfEnabled($message, $text, $v1Message);

        } catch (\Throwable $e) {
            Log::error('TranscribeAudio: unexpected error', [
                'message_id' => $this->messageId,
                'error'      => $e->getMessage(),
            ]);

            // Re-throw so the job retries (up to $this->tries times).
            throw $e;
        } finally {
            if ($tmpPath && file_exists($tmpPath)) {
                @unlink($tmpPath);
            }
        }
    }

    /**
     * Resolve the Communication v1 Message that corresponds to this WhatsappAI module message.
     *
     * Prefer the canonical link written by SyncWhatsappAiConversationToCommunicationService
     * (meta.whatsapp_ai_message_id). Never fall back to "latest audio for this tenant" —
     * that pairs the wrong conversation when multiple voice notes are in flight.
     */
    private function findV1Message(WhatsappMessage $waMessage): ?Message
    {
        $userId = (int) ($waMessage->conversation?->user_id ?? 0);
        if ($userId <= 0) {
            return null;
        }

        // Canonical link from the sync service
        $message = Message::query()
            ->where('user_id', $userId)
            ->where('meta->whatsapp_ai_message_id', $waMessage->id)
            ->first();

        if ($message !== null) {
            return $message;
        }

        // Provider (Meta) message id when present
        $providerId = trim((string) ($waMessage->whatsapp_message_id ?? ''));
        if ($providerId !== '') {
            $message = Message::query()
                ->where('user_id', $userId)
                ->where('provider_message_id', $providerId)
                ->first();

            if ($message !== null) {
                return $message;
            }
        }

        // Scoped fallback: only within the same WhatsApp AI conversation, never tenant-wide
        $aiConversationId = (int) ($waMessage->conversation_id ?? 0);
        if ($aiConversationId <= 0) {
            return null;
        }

        return Message::query()
            ->where('user_id', $userId)
            ->where('meta->whatsapp_ai_conversation_id', $aiConversationId)
            ->where(function ($q) {
                $q->where('meta->whatsapp_message_type', 'audio')
                    ->orWhere('meta->type', 'audio');
            })
            ->where(function ($q) {
                $q->where('meta->transcription_status', 'pending')
                    ->orWhere('content', '[Audio message]');
            })
            ->orderByDesc('id')
            ->first();
    }

    /**
     * Write the transcribed text back to the Communication v1 Message record
     * so the bot context builder can see the actual words.
     */
    private function writeBackToV1Message(WhatsappMessage $waMessage, string $text): ?Message
    {
        try {
            $v1Message = $this->findV1Message($waMessage);

            if ($v1Message === null) {
                Log::warning('TranscribeAudio: v1 message not found for write-back', [
                    'whatsapp_message_id' => $waMessage->id,
                ]);
                return null;
            }

            $meta = is_array($v1Message->meta) ? $v1Message->meta : [];
            $meta['type'] = $meta['type'] ?? 'audio';
            $meta['transcription_status'] = 'done';
            $v1Message->update([
                'content' => '[صوتي: ' . $text . ']',
                'meta'    => $meta,
            ]);

            return $v1Message->fresh();
        } catch (\Throwable $e) {
            Log::warning('TranscribeAudio: failed to write back to v1 message', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * After transcription is done, trigger a bot turn if the number has an active bot config.
     */
    private function triggerBotIfEnabled(WhatsappMessage $waMessage, string $text, ?Message $v1Message = null): void
    {
        try {
            $userId = $waMessage->conversation?->user_id ?? 0;
            if ($userId === 0) { return; }

            // Reuse the already-resolved v1 message; never pick "latest audio for tenant".
            $v1Message ??= $this->findV1Message($waMessage);

            if ($v1Message === null) {
                Log::warning('TranscribeAudio: v1 message not found for bot trigger', [
                    'whatsapp_message_id' => $waMessage->id,
                ]);
                return;
            }

            $meta = is_array($v1Message->meta) ? $v1Message->meta : [];
            $waNumberId = (int) ($meta['wa_number_id'] ?? 0);
            if ($waNumberId === 0) { return; }

            $botConfig = WaAiConfig::where('user_id', $userId)
                ->where('wa_number_id', $waNumberId)
                ->where('enabled', true)
                ->first();

            if ($botConfig === null) { return; }
            if (! in_array($botConfig->autonomy_level, ['shadow', 'autonomous'], true)) { return; }

            // Ensure content is persisted before handing off (writeBack may have already done this).
            $expectedContent = '[صوتي: ' . $text . ']';
            if ((string) $v1Message->content !== $expectedContent
                || ($meta['transcription_status'] ?? null) !== 'done') {
                $meta['type'] = $meta['type'] ?? 'audio';
                $meta['transcription_status'] = 'done';
                $v1Message->update([
                    'content' => $expectedContent,
                    'meta'    => $meta,
                ]);
            }

            app(Employee::class)->runTurn(
                $userId,
                (int) $v1Message->conversation_id,
                $waNumberId,
                (string) ($meta['from'] ?? $waMessage->conversation?->customer_phone ?? ''),
                $v1Message,
            );
        } catch (\Throwable $e) {
            Log::warning('TranscribeAudio: bot trigger failed', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Map a MIME type to a file extension that Whisper accepts.
     * WhatsApp voice notes are typically ogg/opus.
     */
    private function mimeToExtension(string $mimeType): string
    {
        return match (true) {
            str_contains($mimeType, 'ogg')  => 'ogg',
            str_contains($mimeType, 'mp4')  => 'mp4',
            str_contains($mimeType, 'mpeg') => 'mp3',
            str_contains($mimeType, 'mp3')  => 'mp3',
            str_contains($mimeType, 'wav')  => 'wav',
            str_contains($mimeType, 'webm') => 'webm',
            str_contains($mimeType, 'm4a')  => 'm4a',
            str_contains($mimeType, 'amr')  => 'amr',
            default                         => 'ogg',
        };
    }
}
