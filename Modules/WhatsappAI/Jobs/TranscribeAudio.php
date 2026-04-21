<?php

namespace Modules\WhatsappAI\Jobs;

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

            // 4. Persist transcription. Prefix makes it clear the text is from audio.
            $message->update(['content' => '[صوتي: ' . $text . ']']);

            Log::info('TranscribeAudio: transcription saved', [
                'message_id' => $this->messageId,
                'length'     => mb_strlen($text),
            ]);

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
