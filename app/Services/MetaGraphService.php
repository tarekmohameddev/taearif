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
    public function exchangeCodeForToken(string $code, array $logContext = []): array
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

        $body = $response->json();
        $body = is_array($body) ? $body : [];
        $this->logMetaResponse(
            $response->successful() ? 'info' : 'error',
            'MetaGraphService.exchangeCodeForToken response',
            $logContext,
            $response->status(),
            [
                'has_access_token' => !empty($body['access_token']),
                'token_type' => $body['token_type'] ?? null,
                'expires_in' => $body['expires_in'] ?? null,
                'error' => $this->sanitizeMetaError($body['error'] ?? null),
                'response_keys' => array_keys($body),
            ]
        );

        if (!$response->successful()) {

            throw new \RuntimeException('Failed to exchange authorization code for access token.');
        }

        return $response->json();
    }

    /**
    * Exchange a short-lived user access token for a long-lived token.
    *
    * @see https://developers.facebook.com/docs/facebook-login/guides/access-tokens/get-long-lived
    */
    public function exchangeForLongLivedToken(string $shortLivedToken, array $logContext = []): array
    {
        $appId = Config::get('services.meta.app_id');
        $appSecret = Config::get('services.meta.app_secret');

        $response = Http::asForm()->get($this->graphUrl('/oauth/access_token'), [
            'grant_type' => 'fb_exchange_token',
            'client_id' => $appId,
            'client_secret' => $appSecret,
            'fb_exchange_token' => $shortLivedToken,
        ]);

        $body = $response->json();
        $body = is_array($body) ? $body : [];
        $this->logMetaResponse(
            $response->successful() ? 'info' : 'error',
            'MetaGraphService.exchangeForLongLivedToken response',
            $logContext,
            $response->status(),
            [
                'has_access_token' => !empty($body['access_token']),
                'token_type' => $body['token_type'] ?? null,
                'expires_in' => $body['expires_in'] ?? null,
                'error' => $this->sanitizeMetaError($body['error'] ?? null),
                'response_keys' => array_keys($body),
            ]
        );

        if (!$response->successful()) {

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
    public function debugToken(string $accessToken, array $logContext = []): array
    {
        $appToken = $this->getAppToken();

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $appToken,
        ])->get($this->graphUrl('/debug_token'), [
            'input_token' => $accessToken,
        ]);

        $result = $response->json();
        $result = is_array($result) ? $result : [];
        $data = $result['data'] ?? [];
        $this->logMetaResponse(
            $response->successful() ? 'info' : 'error',
            'MetaGraphService.debugToken response',
            $logContext,
            $response->status(),
            [
                'is_valid' => $data['is_valid'] ?? null,
                'app_id' => $data['app_id'] ?? null,
                'user_id' => $data['user_id'] ?? null,
                'expires_at' => $data['expires_at'] ?? null,
                'data_access_expires_at' => $data['data_access_expires_at'] ?? null,
                'scopes' => $data['scopes'] ?? [],
                'granular_scopes' => $data['granular_scopes'] ?? [],
                'error' => $this->sanitizeMetaError($result['error'] ?? null),
            ]
        );

        if (!$response->successful()) {

            throw new \RuntimeException('Failed to debug token.');
        }

        return $result;
    }

    /**
     * Extract every WABA ID granted through WhatsApp granular scopes.
     *
     * @return array<int,string>
     */
    public function extractWabaIdsFromDebugToken(array $debugTokenResponse): array
    {
        $data = $debugTokenResponse['data'] ?? [];
        $granularScopes = is_array($data['granular_scopes'] ?? null)
            ? $data['granular_scopes']
            : [];
        $whatsappScopes = ['whatsapp_business_management', 'whatsapp_business_messaging'];
        $wabaIds = [];

        foreach ($granularScopes as $scope) {
            if (! is_array($scope) || ! in_array($scope['scope'] ?? '', $whatsappScopes, true)) {
                continue;
            }

            foreach ((array) ($scope['target_ids'] ?? []) as $targetId) {
                $targetId = trim((string) $targetId);

                if ($targetId !== '') {
                    $wabaIds[$targetId] = $targetId;
                }
            }
        }

        return array_values($wabaIds);
    }

    /**
     * Backwards-compatible single-WABA accessor for existing signup callers.
     */
    public function extractWabaIdFromDebugToken(array $debugTokenResponse): ?string
    {
        return $this->extractWabaIdsFromDebugToken($debugTokenResponse)[0] ?? null;
    }

    /**
    * List phone numbers for a WhatsApp Business Account.
    *
    * GET /{waba_id}/phone_numbers?access_token={access_token}
    * Authorization: Bearer {APP_TOKEN}
    */
    public function listPhoneNumbers(string $accessToken, string $wabaId, array $logContext = []): array
    {
        $appToken = $this->getAppToken();

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $appToken,
        ])->get($this->graphUrl("/{$wabaId}/phone_numbers"), [
            'access_token' => $accessToken,
        ]);

        $body = $response->json();
        $body = is_array($body) ? $body : [];
        $phones = array_map(static fn (array $phone): array => [
            'id' => $phone['id'] ?? null,
            'display_phone_number' => $phone['display_phone_number'] ?? null,
            'verified_name' => $phone['verified_name'] ?? null,
            'quality_rating' => $phone['quality_rating'] ?? null,
            'code_verification_status' => $phone['code_verification_status'] ?? null,
            'platform_type' => $phone['platform_type'] ?? null,
        ], is_array($body['data'] ?? null) ? $body['data'] : []);

        $this->logMetaResponse(
            $response->successful() ? 'info' : 'error',
            'MetaGraphService.listPhoneNumbers response',
            array_merge($logContext, ['waba_id' => $wabaId]),
            $response->status(),
            [
                'phone_count' => count($phones),
                'phones' => $phones,
                'error' => $this->sanitizeMetaError($body['error'] ?? null),
            ]
        );

        if (!$response->successful()) {

            throw new \RuntimeException('Failed to list WhatsApp Business Account phone numbers.');
        }

        return $response->json();
    }

    /**
    * Subscribe app to WABA for webhooks.
    *
    * POST /{waba_id}/subscribed_apps?subscribed_fields=...
    * Authorization: Bearer {access_token}
    *
    * This subscribes the app to receive webhooks for the WhatsApp Business Account.
    * Required for receiving incoming messages and status updates.
    */
    public function subscribeAppToWaba(string $accessToken, string $wabaId, array $logContext = []): array
    {
        // Tell Meta which webhook fields we want delivered for this WABA.
        // See: https://developers.facebook.com/docs/whatsapp/cloud-api/webhooks/components
        $subscribedFields = implode(',', [
            'messages',
            'message_deliveries',
            'message_reads',
            'message_reactions',
            'message_echoes',
            'smb_message_echoes',
        ]);

        // Meta expects subscribed_fields as a query string on this POST endpoint.
        $url = $this->graphUrl("/{$wabaId}/subscribed_apps?subscribed_fields=" . urlencode($subscribedFields));

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $accessToken,
        ])->post($url);

        $body = $response->json();
        $body = is_array($body) ? $body : [];
        $this->logMetaResponse(
            $response->successful() ? 'info' : 'error',
            'MetaGraphService.subscribeAppToWaba response',
            array_merge($logContext, ['waba_id' => $wabaId]),
            $response->status(),
            [
                'success' => $body['success'] ?? null,
                'error' => $this->sanitizeMetaError($body['error'] ?? null),
                'response_keys' => array_keys($body),
            ]
        );

        if (!$response->successful()) {

            throw new \RuntimeException('Failed to subscribe app to WhatsApp Business Account.');
        }

        return $body;
    }

    private function sanitizeMetaError($error): ?array
    {
        if (!is_array($error)) {
            return null;
        }

        return [
            'message' => $error['message'] ?? null,
            'type' => $error['type'] ?? null,
            'code' => $error['code'] ?? null,
            'error_subcode' => $error['error_subcode'] ?? null,
            'is_transient' => $error['is_transient'] ?? null,
            'error_user_title' => $error['error_user_title'] ?? null,
            'error_user_msg' => $error['error_user_msg'] ?? null,
            'fbtrace_id' => $error['fbtrace_id'] ?? null,
        ];
    }

    private function logMetaResponse(
        string $level,
        string $message,
        array $context,
        int $status,
        array $response
    ): void {
        try {
            Log::log($level, $message, array_merge($context, [
                'http_status' => $status,
                'meta_response' => $response,
            ]));
        } catch (\Throwable $e) {
            // Diagnostics must never interrupt Meta signup.
        }
    }
}
