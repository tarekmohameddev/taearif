<?php

namespace App\Services;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MetaGraphService
{
    /**
    * Get base Graph API versioned URL.
    */
    protected function graphUrl(string $path): string
    {
        $version = Config::get('services.meta.api_version', 'v20.0');
        $path = ltrim($path, '/');

        return "https://graph.facebook.com/{$version}/{$path}";
    }

    /**
    * Get the app token for API calls.
    */
    protected function getAppToken(): string
    {
        $appToken = Config::get('services.meta.app_token');

        if (!$appToken) {
            throw new \RuntimeException('META_APP_TOKEN is not configured in .env');
        }

        return $appToken;
    }

    /**
    * Exchange authorization code for a short-lived user access token.
    *
    * @see https://developers.facebook.com/docs/facebook-login/guides/advanced/manual-flow
    */
    public function exchangeCodeForToken(string $code): array
    {
        $appId = Config::get('services.meta.app_id');
        $appSecret = Config::get('services.meta.app_secret');
        $redirectUri = Config::get('services.meta.redirect_uri');

        $response = Http::asForm()->post($this->graphUrl('/oauth/access_token'), [
            'client_id' => $appId,
            'client_secret' => $appSecret,
            'redirect_uri' => $redirectUri,
            'code' => $code,
        ]);

        if (!$response->successful()) {
            Log::error('MetaGraphService.exchangeCodeForToken failed', [
                'status' => $response->status(),
                'body' => $response->json(),
            ]);

            throw new \RuntimeException('Failed to exchange authorization code for access token.');
        }

        return $response->json();
    }

    /**
    * Exchange a short-lived user access token for a long-lived token.
    *
    * @see https://developers.facebook.com/docs/facebook-login/guides/access-tokens/get-long-lived
    */
    public function exchangeForLongLivedToken(string $shortLivedToken): array
    {
        $appId = Config::get('services.meta.app_id');
        $appSecret = Config::get('services.meta.app_secret');

        $response = Http::asForm()->get($this->graphUrl('/oauth/access_token'), [
            'grant_type' => 'fb_exchange_token',
            'client_id' => $appId,
            'client_secret' => $appSecret,
            'fb_exchange_token' => $shortLivedToken,
        ]);

        if (!$response->successful()) {
            Log::error('MetaGraphService.exchangeForLongLivedToken failed', [
                'status' => $response->status(),
                'body' => $response->json(),
            ]);

            throw new \RuntimeException('Failed to exchange short-lived token for long-lived token.');
        }

        return $response->json();
    }

    /**
    * Debug token to get WABA ID from granular scopes.
    *
    * GET /debug_token?input_token={access_token}
    * Authorization: Bearer {APP_TOKEN}
    *
    * Returns granular_scopes with whatsapp_business_management/whatsapp_business_messaging
    * which contain target_ids with the WABA ID.
    */
    public function debugToken(string $accessToken): array
    {
        $appToken = $this->getAppToken();

        Log::info('MetaGraphService.debugToken starting', [
            'access_token_prefix' => substr($accessToken, 0, 20) . '...',
        ]);

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $appToken,
        ])->get($this->graphUrl('/debug_token'), [
            'input_token' => $accessToken,
        ]);

        if (!$response->successful()) {
            Log::error('MetaGraphService.debugToken failed', [
                'status' => $response->status(),
                'body' => $response->json(),
            ]);

            throw new \RuntimeException('Failed to debug token.');
        }

        $result = $response->json();

        Log::info('MetaGraphService.debugToken response', [
            'data' => $result['data'] ?? null,
        ]);

        return $result;
    }

    /**
    * Extract WABA ID from debug_token response.
    *
    * Looks for whatsapp_business_management or whatsapp_business_messaging scope
    * and returns the first target_id which is the WABA ID.
    */
    public function extractWabaIdFromDebugToken(array $debugTokenResponse): ?string
    {
        $data = $debugTokenResponse['data'] ?? [];
        $granularScopes = $data['granular_scopes'] ?? [];

        $whatsappScopes = ['whatsapp_business_management', 'whatsapp_business_messaging'];

        foreach ($granularScopes as $scope) {
            $scopeName = $scope['scope'] ?? '';
            if (in_array($scopeName, $whatsappScopes, true)) {
                $targetIds = $scope['target_ids'] ?? [];
                if (!empty($targetIds)) {
                    return $targetIds[0];
                }
            }
        }

        return null;
    }

    /**
    * List phone numbers for a WhatsApp Business Account.
    *
    * GET /{waba_id}/phone_numbers?access_token={access_token}
    * Authorization: Bearer {APP_TOKEN}
    */
    public function listPhoneNumbers(string $accessToken, string $wabaId): array
    {
        $appToken = $this->getAppToken();

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $appToken,
        ])->get($this->graphUrl("/{$wabaId}/phone_numbers"), [
            'access_token' => $accessToken,
        ]);

        if (!$response->successful()) {
            Log::error('MetaGraphService.listPhoneNumbers failed', [
                'waba_id' => $wabaId,
                'status' => $response->status(),
                'body' => $response->json(),
            ]);

            throw new \RuntimeException('Failed to list WhatsApp Business Account phone numbers.');
        }

        return $response->json();
    }

    /**
    * Subscribe app to WABA for webhooks.
    *
    * POST /{waba_id}/subscribed_apps
    * Authorization: Bearer {access_token}
    *
    * This subscribes the app to receive webhooks for the WhatsApp Business Account.
    * Required for receiving incoming messages and status updates.
    */
    public function subscribeAppToWaba(string $accessToken, string $wabaId): array
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $accessToken,
        ])->post($this->graphUrl("/{$wabaId}/subscribed_apps"));

        if (!$response->successful()) {
            Log::error('MetaGraphService.subscribeAppToWaba failed', [
                'waba_id' => $wabaId,
                'status' => $response->status(),
                'body' => $response->json(),
            ]);

            throw new \RuntimeException('Failed to subscribe app to WhatsApp Business Account.');
        }

        Log::info('MetaGraphService.subscribeAppToWaba success', [
            'waba_id' => $wabaId,
            'response' => $response->json(),
        ]);

        return $response->json();
    }
}
