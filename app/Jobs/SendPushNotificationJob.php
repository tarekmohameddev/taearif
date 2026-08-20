<?php

namespace App\Jobs;

use App\Domain\Notifications\NotificationInboxService;
use App\Domain\Notifications\NotificationPreferencesService;
use App\Domain\Notifications\PushSender;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class SendPushNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public int $notificationId, public int $tokenId)
    {
        $this->afterCommit();
    }

    public function handle(
        NotificationInboxService $inbox,
        NotificationPreferencesService $preferences,
        PushSender $sender
    ): void {
        $token = DB::table('device_push_tokens')->where('id', $this->tokenId)->where('active', true)->first();
        $message = $inbox->notificationForPush($this->notificationId);
        if ($token === null || $message === null) {
            return;
        }

        $settings = $preferences->get((int) $token->user_id);
        if (! $settings['enabled'] || ! $settings[$message['category']]) {
            return;
        }

        $sender->send($token, $message, $settings);
    }
}
