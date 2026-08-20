<?php

namespace App\Domain\Notifications;

use GuzzleHttp\Client;
use RuntimeException;

class ApnsHttp2Client
{
    public function __construct(private ?Client $http = null)
    {
        $this->http ??= new Client(['timeout' => 10, 'version' => 2.0]);
    }

    public function send(string $deviceToken, array $notification, array $data, array $preferences = []): array
    {
        $config = config('notifications.apns');
        foreach (['key_p8', 'key_id', 'team_id', 'bundle_id'] as $key) {
            if (empty($config[$key])) {
                throw new RuntimeException("APNs {$key} is not configured.");
            }
        }

        $host = ($config['environment'] ?? 'production') === 'sandbox'
            ? 'https://api.sandbox.push.apple.com'
            : 'https://api.push.apple.com';
        $aps = [
            'alert' => ['title' => $notification['title'], 'body' => $notification['body']],
            'sound' => ($preferences['sound'] ?? true) ? 'default' : null,
            'badge' => ($preferences['badge'] ?? true) ? 1 : 0,
        ];

        $response = $this->http->post($host.'/3/device/'.$deviceToken, [
            'headers' => [
                'authorization' => 'bearer '.$this->providerToken($config),
                'apns-topic' => $config['bundle_id'],
                'apns-push-type' => 'alert',
                'apns-priority' => '10',
            ],
            'json' => array_merge(['aps' => array_filter($aps, fn ($value) => $value !== null)], $this->stringify($data)),
            'http_errors' => false,
        ]);

        $body = json_decode((string) $response->getBody(), true) ?: [];
        return [
            'ok' => $response->getStatusCode() === 200,
            'invalid' => ($body['reason'] ?? null) === 'BadDeviceToken'
                || ($body['reason'] ?? null) === 'Unregistered',
            'status' => $response->getStatusCode(),
            'response' => $body,
        ];
    }

    private function providerToken(array $config): string
    {
        $header = $this->base64Url(json_encode(['alg' => 'ES256', 'kid' => $config['key_id']]));
        $claims = $this->base64Url(json_encode(['iss' => $config['team_id'], 'iat' => time()]));
        $unsigned = $header.'.'.$claims;
        $key = str_replace('\\n', "\n", $config['key_p8']);
        if (is_file($key)) {
            $key = (string) file_get_contents($key);
        }
        if (! openssl_sign($unsigned, $derSignature, $key, OPENSSL_ALGO_SHA256)) {
            throw new RuntimeException('Unable to sign APNs provider token.');
        }
        return $unsigned.'.'.$this->base64Url($this->derToJose($derSignature, 64));
    }

    private function derToJose(string $der, int $length): string
    {
        $offset = 2;
        if (ord($der[1]) > 0x80) {
            $offset = 2 + (ord($der[1]) & 0x7f);
        }
        if (ord($der[$offset]) !== 0x02) {
            throw new RuntimeException('Invalid APNs ECDSA signature.');
        }
        $rLength = ord($der[$offset + 1]);
        $r = substr($der, $offset + 2, $rLength);
        $offset += 2 + $rLength;
        $sLength = ord($der[$offset + 1]);
        $s = substr($der, $offset + 2, $sLength);
        $partLength = intdiv($length, 2);
        return str_pad(ltrim($r, "\0"), $partLength, "\0", STR_PAD_LEFT)
            .str_pad(ltrim($s, "\0"), $partLength, "\0", STR_PAD_LEFT);
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
