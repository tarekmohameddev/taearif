<?php

namespace App\Domain\Communication\WhatsApp\Services;

use App\Domain\Communication\DTOs\ProviderDispatchResult;
use App\Domain\Communication\Exceptions\ProviderSendFailedException;
use App\Domain\Communication\Services\RetryPolicyHelper;
use App\Models\WaNumber;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppChannelSender
{
    public function __construct(
        private readonly RetryPolicyHelper $retryPolicyHelper
    ) {}

    /**
     * Send a Meta-approved template message via the Template Message API.
     * Falls back to plain text send for non-meta providers (Evolution, etc.).
     *
     * @param array<int, array<string, mixed>> $components Component parameters with variable substitutions.
     */
    public function sendTemplate(
        WaNumber $waNumber,
        string $toPhone,
        string $templateName,
        string $language,
        array $components = []
    ): ProviderDispatchResult {
        $provider = strtolower((string) $waNumber->provider);
        $toPhone = $this->formatPhone($toPhone);

        if ($provider === 'meta') {
            return $this->sendTemplateViaMeta($waNumber, $toPhone, $templateName, $language, $components);
        }

        // Non-meta providers do not support the template API; callers should pre-render
        throw new ProviderSendFailedException("Provider '{$provider}' does not support the Meta Template Message API.");
    }

    /**
     * Send text message using the given WaNumber (credentials from number + config).
     * Returns ProviderDispatchResult with provider_message_id when available.
     */
    public function send(WaNumber $waNumber, string $toPhone, string $content): ProviderDispatchResult
    {
        $provider = strtolower((string) $waNumber->provider);
        $toPhone = $this->formatPhone($toPhone);

        if ($provider === 'meta') {
            return $this->sendViaMeta($waNumber, $toPhone, $content);
        }
        if ($provider === 'evolution') {
            return $this->sendViaEvolution($waNumber, $toPhone, $content);
        }

        throw new ProviderSendFailedException("Unsupported WhatsApp provider: {$provider}");
    }

    /**
     * @param array<int, array<string, mixed>> $components
     */
    private function sendTemplateViaMeta(
        WaNumber $waNumber,
        string $toPhone,
        string $templateName,
        string $language,
        array $components
    ): ProviderDispatchResult {
        $meta = is_array($waNumber->meta) ? $waNumber->meta : [];
        $accessToken = $meta['access_token'] ?? $meta['meta_access_token'] ?? null;
        $phoneNumberId = $waNumber->phone_number_id ?? $meta['phone_number_id'] ?? $meta['meta_phone_number_id'] ?? null;

        if (! $accessToken || ! $phoneNumberId) {
            Log::warning('WhatsAppChannelSender: Meta credentials missing for template send', ['wa_number_id' => $waNumber->id]);
            throw new ProviderSendFailedException('Meta WhatsApp credentials not configured for this number.');
        }

        $url = "https://graph.facebook.com/v20.0/{$phoneNumberId}/messages";
        $payload = [
            'messaging_product' => 'whatsapp',
            'to'                => ltrim($toPhone, '+'),
            'type'              => 'template',
            'template'          => [
                'name'       => $templateName,
                'language'   => ['code' => $language],
                'components' => $components,
            ],
        ];

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
                'Content-Type'  => 'application/json',
            ])->post($url, $payload);

            if ($response->successful()) {
                $body = $response->json();
                $providerMessageId = isset($body['messages'][0]['id']) ? (string) $body['messages'][0]['id'] : null;
                return ProviderDispatchResult::success($providerMessageId, $body ?? []);
            }

            $status = $response->status();
            $body = $response->json();
            $errorMessage = $body['error']['message'] ?? $response->body();
            $errorCode = $body['error']['code'] ?? null;

            Log::error('WhatsAppChannelSender: Meta template send failed', [
                'wa_number_id'  => $waNumber->id,
                'template_name' => $templateName,
                'response'      => $body,
                'status'        => $status,
            ]);

            $isTransient = $this->retryPolicyHelper->isTransient(
                'meta',
                $status,
                $errorCode !== null ? (string) $errorCode : null,
                $errorMessage
            );

            return ProviderDispatchResult::failure($isTransient, (string) $errorCode, $errorMessage, $body ?? []);
        } catch (ProviderSendFailedException $e) {
            throw $e;
        } catch (\Throwable $e) {
            $isTransient = $this->retryPolicyHelper->isTransient('meta', null, null, $e->getMessage());
            return ProviderDispatchResult::failure($isTransient, null, $e->getMessage(), []);
        }
    }

    private function sendViaMeta(WaNumber $waNumber, string $toPhone, string $content): ProviderDispatchResult
    {
        $meta = is_array($waNumber->meta) ? $waNumber->meta : [];
        $accessToken = $meta['access_token'] ?? $meta['meta_access_token'] ?? null;
        $phoneNumberId = $waNumber->phone_number_id ?? $meta['phone_number_id'] ?? $meta['meta_phone_number_id'] ?? null;

        if (! $accessToken || ! $phoneNumberId) {
            Log::warning('WhatsAppChannelSender: Meta credentials missing for wa_number', ['wa_number_id' => $waNumber->id]);
            throw new ProviderSendFailedException('Meta WhatsApp credentials not configured for this number.');
        }

        $url = "https://graph.facebook.com/v20.0/{$phoneNumberId}/messages";
        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => ltrim($toPhone, '+'),
            'type' => 'text',
            'text' => ['body' => $content],
        ];

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
                'Content-Type' => 'application/json',
            ])->post($url, $payload);

            if ($response->successful()) {
                $body = $response->json();
                $providerMessageId = null;
                if (isset($body['messages'][0]['id'])) {
                    $providerMessageId = (string) $body['messages'][0]['id'];
                }
                return ProviderDispatchResult::success($providerMessageId, $body ?? []);
            }

            $status = $response->status();
            $body = $response->json();
            $errorMessage = $body['error']['message'] ?? $response->body();
            $errorCode = $body['error']['code'] ?? null;

            Log::error('WhatsAppChannelSender: Meta send failed', [
                'wa_number_id' => $waNumber->id,
                'response' => $body,
                'status' => $status,
            ]);

            $isTransient = $this->retryPolicyHelper->isTransient(
                'meta',
                $status,
                $errorCode !== null ? (string) $errorCode : null,
                $errorMessage
            );

            return ProviderDispatchResult::failure($isTransient, (string) $errorCode, $errorMessage, $body ?? []);
        } catch (ProviderSendFailedException $e) {
            throw $e;
        } catch (\Throwable $e) {
            $isTransient = $this->retryPolicyHelper->isTransient('meta', null, null, $e->getMessage());
            return ProviderDispatchResult::failure($isTransient, null, $e->getMessage(), []);
        }
    }

    private function sendViaEvolution(WaNumber $waNumber, string $toPhone, string $content): ProviderDispatchResult
    {
        $baseUrl = rtrim((string) config('communication.whatsapp.evolution.base_url', ''), '/');
        $apiKey = config('communication.whatsapp.evolution.api_key', '');
        $instance = $waNumber->provider_account_id ?? (is_array($waNumber->meta) ? ($waNumber->meta['instance'] ?? $waNumber->meta['evolution_instance'] ?? null) : null);

        if (! $baseUrl || ! $apiKey || ! $instance) {
            Log::warning('WhatsAppChannelSender: Evolution credentials missing for wa_number', ['wa_number_id' => $waNumber->id]);
            throw new ProviderSendFailedException('Evolution WhatsApp credentials not configured for this number.');
        }

        $endpoint = "{$baseUrl}/message/sendText/{$instance}";
        $payload = [
            'number' => ltrim($toPhone, '+'),
            'text' => $content,
            'options' => ['delay' => 1200, 'presence' => 'composing'],
        ];

        try {
            $response = Http::withHeaders([
                'apikey' => $apiKey,
                'Content-Type' => 'application/json',
            ])->post($endpoint, $payload);

            if ($response->successful()) {
                $body = $response->json();
                $providerMessageId = null;
                if (isset($body['key']['id'])) {
                    $providerMessageId = (string) $body['key']['id'];
                } elseif (isset($body['id'])) {
                    $providerMessageId = (string) $body['id'];
                }
                return ProviderDispatchResult::success($providerMessageId, $body ?? []);
            }

            $status = $response->status();
            $body = $response->json();
            $errorMessage = $body['message'] ?? $response->body();
            $errorCode = $body['error'] ?? $body['code'] ?? null;

            Log::error('WhatsAppChannelSender: Evolution send failed', [
                'wa_number_id' => $waNumber->id,
                'response' => $body,
                'status' => $status,
            ]);

            $isTransient = $this->retryPolicyHelper->isTransient(
                'evolution',
                $status,
                $errorCode !== null ? (string) $errorCode : null,
                $errorMessage
            );

            return ProviderDispatchResult::failure($isTransient, $errorCode !== null ? (string) $errorCode : null, $errorMessage, $body ?? []);
        } catch (ProviderSendFailedException $e) {
            throw $e;
        } catch (\Throwable $e) {
            $isTransient = $this->retryPolicyHelper->isTransient('evolution', null, null, $e->getMessage());
            return ProviderDispatchResult::failure($isTransient, null, $e->getMessage(), []);
        }
    }

    private function formatPhone(string $value): string
    {
        $value = preg_replace('/[\s\-]+/', '', $value);
        if (preg_match('/^\+?\d+$/', $value)) {
            $value = ltrim($value, '+');
            if (strlen($value) > 0 && $value[0] !== '0') {
                $value = '+' . $value;
            }
        }
        return $value;
    }
}
