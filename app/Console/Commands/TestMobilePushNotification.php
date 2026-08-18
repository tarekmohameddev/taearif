<?php

namespace App\Console\Commands;

use App\Domain\Notifications\ApnsHttp2Client;
use App\Domain\Notifications\FcmHttpV1Client;
use App\Domain\Notifications\PushSender;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class TestMobilePushNotification extends Command
{
    protected $signature = 'push:test
                            {--token= : Raw FCM or APNs device token}
                            {--provider=fcm : Provider when using --token (fcm or apns)}
                            {--user= : User ID — send to active registered device tokens}
                            {--device= : Device ID filter when using --user}
                            {--title=Taearif test : Notification title}
                            {--body=Push notification test from server : Notification body}';

    protected $description = 'Send a test mobile push notification via FCM or APNs';

    public function handle(FcmHttpV1Client $fcm, PushSender $sender): int
    {
        $this->line('Checking push configuration…');
        if (! $this->verifyFcmConfig()) {
            return Command::FAILURE;
        }

        $title = (string) $this->option('title');
        $body = (string) $this->option('body');
        $message = [
            'id' => 0,
            'type' => 'SYSTEM',
            'category' => 'SYSTEM',
            'title' => $title,
            'body' => $body,
            'deepLink' => 'taearif://test',
            'entityType' => 'test',
            'entityId' => 0,
            'requestId' => 'test_0',
            'customerId' => null,
        ];
        $preferences = ['sound' => true, 'badge' => true, 'popup' => true];

        $token = trim((string) $this->option('token'));
        $userId = $this->option('user');

        if ($token !== '') {
            return $this->sendRawToken($fcm, $token, (string) $this->option('provider'), $message, $preferences);
        }

        if ($userId !== null && $userId !== '') {
            return $this->sendForUser($sender, (int) $userId, $message, $preferences);
        }

        $this->error('Provide --token=<device-token> or --user=<user-id>.');
        $this->line('Examples:');
        $this->line('  php artisan push:test --token=<fcm-token>');
        $this->line('  php artisan push:test --user=1205');
        $this->line('  php artisan push:test --user=1205 --device=iphone-12');

        return Command::FAILURE;
    }

    private function verifyFcmConfig(): bool
    {
        try {
            $raw = config('notifications.fcm.service_account_json');
            if (! is_string($raw) || trim($raw) === '') {
                throw new RuntimeException('FCM_SERVICE_ACCOUNT_JSON is empty.');
            }
            if (is_file($raw)) {
                $decoded = json_decode((string) file_get_contents($raw), true);
            } else {
                $decoded = json_decode($raw, true);
            }
            if (! is_array($decoded) || empty($decoded['project_id'])) {
                throw new RuntimeException('FCM service account JSON is invalid.');
            }
            $this->info("FCM project: {$decoded['project_id']}");

            return true;
        } catch (\Throwable $exception) {
            $this->error('FCM is not configured: '.$exception->getMessage());
            $this->line('Set FCM_SERVICE_ACCOUNT_JSON in .env to the service account JSON path or contents.');

            return false;
        }
    }

    private function sendRawToken(
        FcmHttpV1Client $fcm,
        string $token,
        string $provider,
        array $message,
        array $preferences
    ): int {
        $provider = strtolower($provider);
        if (! in_array($provider, ['fcm', 'apns'], true)) {
            $this->error('Invalid --provider. Use fcm or apns.');

            return Command::FAILURE;
        }

        $notification = ['title' => $message['title'], 'body' => $message['body']];
        $data = [
            'notificationId' => '0',
            'type' => 'SYSTEM',
            'category' => 'SYSTEM',
            'deepLink' => 'taearif://test',
            'entityType' => 'test',
            'entityId' => '0',
            'requestId' => 'test_0',
            'customerId' => '',
            'sound' => 'true',
            'badge' => 'true',
            'popup' => 'true',
        ];

        $this->info("Sending test push via {$provider} to raw token…");

        try {
            $result = $provider === 'apns'
                ? app(ApnsHttp2Client::class)->send($token, $notification, $data, $preferences)
                : $fcm->send($token, $notification, $data);
        } catch (\Throwable $exception) {
            $this->error('Send failed: '.$exception->getMessage());

            return Command::FAILURE;
        }

        return $this->reportResult($result);
    }

    private function sendForUser(PushSender $sender, int $userId, array $message, array $preferences): int
    {
        $query = DB::table('device_push_tokens')
            ->where('user_id', $userId)
            ->where('active', true);

        $deviceId = trim((string) $this->option('device'));
        if ($deviceId !== '') {
            $query->where('device_id', $deviceId);
        }

        $tokens = $query->get();
        if ($tokens->isEmpty()) {
            $this->error("No active push tokens found for user {$userId}.");
            $this->line('The mobile app must POST /api/v1/devices/push-tokens after login.');

            return Command::FAILURE;
        }

        $this->info("Sending to {$tokens->count()} token(s) for user {$userId}…");
        $failures = 0;

        foreach ($tokens as $token) {
            $this->line("- device {$token->device_id} ({$token->provider}/{$token->platform})");
            try {
                $result = $sender->send($token, $message, $preferences);
                if (! ($result['ok'] ?? false)) {
                    $failures++;
                }
                $this->reportResult($result, false);
            } catch (\Throwable $exception) {
                $failures++;
                $this->error('  Send failed: '.$exception->getMessage());
            }
        }

        return $failures === 0 ? Command::SUCCESS : Command::FAILURE;
    }

    private function reportResult(array $result, bool $returnCode = true): int
    {
        if ($result['ok'] ?? false) {
            $this->info('Push accepted by provider.');
            if (! empty($result['response'])) {
                $this->line(json_encode($result['response'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            }

            return Command::SUCCESS;
        }

        $this->error('Push rejected by provider.');
        $this->line('HTTP status: '.($result['status'] ?? 'unknown'));
        if (! empty($result['response'])) {
            $this->line(json_encode($result['response'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        }
        if ($result['invalid'] ?? false) {
            $this->warn('Token appears invalid or unregistered (UNREGISTERED).');
        }

        return $returnCode ? Command::FAILURE : 0;
    }
}
