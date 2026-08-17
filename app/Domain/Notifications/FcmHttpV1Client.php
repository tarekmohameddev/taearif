<?php

namespace App\Domain\Notifications;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Cache;
use RuntimeException;

class FcmHttpV1Client
{
    public function __construct(private ?Client $http = null)
    {
        $this->http ??= new Client(['timeout' => 10]);
    }

    public function send(string $deviceToken, array $notification, array $data): array
    {
        $account = $this->serviceAccount();
        $response = $this->http->post(sprintf(config('notifications.fcm.endpoint'), $account['project_id']), [
            'headers' => ['Authorization' => 'Bearer '.$this->accessToken($account)],
            'json' => [
                'message' => [
                    'token' => $deviceToken,
                    'notification' => $notification,
                    'data' => $this->stringify($data),
                    'android' => ['priority' => 'HIGH'],
                ],
            ],
            'http_errors' => false,
        ]);

        $body = json_decode((string) $response->getBody(), true) ?: [];
        $errorText = json_encode($body);

        return [
            'ok' => $response->getStatusCode() >= 200 && $response->getStatusCode() < 300,
            'invalid' => str_contains((string) $errorText, 'UNREGISTERED'),
            'status' => $response->getStatusCode(),
            'response' => $body,
        ];
    }

    private function serviceAccount(): array
    {
        $raw = config('notifications.fcm.service_account_json');
        if (! is_string($raw) || trim($raw) === '') {
            throw new RuntimeException('FCM_SERVICE_ACCOUNT_JSON is not configured.');
        }
        $decoded = json_decode($raw, true);
        if (! is_array($decoded) && is_file($raw)) {
            $decoded = json_decode((string) file_get_contents($raw), true);
        }
        if (! is_array($decoded) || empty($decoded['client_email']) || empty($decoded['private_key']) || empty($decoded['project_id'])) {
            throw new RuntimeException('FCM service account JSON is invalid.');
        }
        return $decoded;
    }

    private function accessToken(array $account): string
    {
        return Cache::remember('notifications:fcm:oauth:'.sha1($account['client_email']), 3300, function () use ($account) {
            $now = time();
            $header = $this->base64Url(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
            $claims = $this->base64Url(json_encode([
                'iss' => $account['client_email'],
                'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
                'aud' => $account['token_uri'] ?? 'https://oauth2.googleapis.com/token',
                'iat' => $now,
                'exp' => $now + 3600,
            ]));
            $unsigned = $header.'.'.$claims;
            if (! openssl_sign($unsigned, $signature, str_replace('\\n', "\n", $account['private_key']), OPENSSL_ALGO_SHA256)) {
                throw new RuntimeException('Unable to sign FCM OAuth assertion.');
            }

            $response = $this->http->post($account['token_uri'] ?? 'https://oauth2.googleapis.com/token', [
                'form_params' => [
                    'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                    'assertion' => $unsigned.'.'.$this->base64Url($signature),
                ],
            ]);
            $payload = json_decode((string) $response->getBody(), true);
            if (empty($payload['access_token'])) {
                throw new RuntimeException('FCM OAuth token response was invalid.');
            }
            return $payload['access_token'];
        });
    }

    private function stringify(array $data): array
    {
        return collect($data)->map(fn ($value) => is_scalar($value) || $value === null
            ? (string) $value
            : json_encode($value))->all();
    }

    private function base64Url(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
