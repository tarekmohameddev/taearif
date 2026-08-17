<?php

namespace App\Domain\Notifications;

use Illuminate\Support\Facades\Log;

class PushSender
{
    public function __construct(
        private FcmHttpV1Client $fcm,
        private ApnsHttp2Client $apns,
        private DevicePushTokenService $tokens
    ) {
    }

    public function send(object $token, array $message, array $preferences): array
    {
        $notification = ['title' => $message['title'], 'body' => $message['body']];
        $data = [
            'notificationId' => (string) $message['id'],
            'type' => (string) $message['type'],
            'category' => (string) $message['category'],
            'deepLink' => (string) $message['deepLink'],
            'entityType' => (string) $message['entityType'],
            'entityId' => (string) $message['entityId'],
            'requestId' => (string) $message['requestId'],
            'customerId' => $message['customerId'] === null ? '' : (string) $message['customerId'],
            'sound' => ($preferences['sound'] ?? true) ? 'true' : 'false',
            'badge' => ($preferences['badge'] ?? true) ? 'true' : 'false',
            'popup' => ($preferences['popup'] ?? true) ? 'true' : 'false',
        ];

        $result = $token->provider === 'apns'
            ? $this->apns->send($token->token, $notification, $data, $preferences)
            : $this->fcm->send($token->token, $notification, $data);

        if ($result['invalid'] ?? false) {
            $this->tokens->deactivateById((int) $token->id);
        }
        if (! ($result['ok'] ?? false)) {
            Log::warning('Mobile push provider rejected notification', [
                'provider' => $token->provider,
                'token_id' => $token->id,
                'notification_id' => $message['id'],
                'status' => $result['status'] ?? null,
            ]);
        }

        return $result;
    }
}
