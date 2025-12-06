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
    * List businesses for the current user.
    *
    * GET /me/businesses
    */
    public function listBusinesses(string $accessToken): array
    {
        $response = Http::get($this->graphUrl('/me/businesses'), [
            'access_token' => $accessToken,
        ]);

        if (!$response->successful()) {
            Log::error('MetaGraphService.listBusinesses failed', [
                'status' => $response->status(),
                'body' => $response->json(),
            ]);

            throw new \RuntimeException('Failed to list Meta businesses.');
        }

        return $response->json();
    }

    /**
    * List WhatsApp Business Accounts owned by a Business.
    *
    * GET /{business_id}/owned_whatsapp_business_accounts
    */
    public function listWhatsAppBusinessAccounts(string $accessToken, string $businessId): array
    {
        $response = Http::get($this->graphUrl("/{$businessId}/owned_whatsapp_business_accounts"), [
            'access_token' => $accessToken,
        ]);

        if (!$response->successful()) {
            Log::error('MetaGraphService.listWhatsAppBusinessAccounts failed', [
                'business_id' => $businessId,
                'status' => $response->status(),
                'body' => $response->json(),
            ]);

            throw new \RuntimeException('Failed to list WhatsApp Business Accounts.');
        }

        return $response->json();
    }

    /**
    * List phone numbers for a WhatsApp Business Account.
    *
    * GET /{waba_id}/phone_numbers
    */
    public function listPhoneNumbers(string $accessToken, string $wabaId): array
    {
        $response = Http::get($this->graphUrl("/{$wabaId}/phone_numbers"), [
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
}


