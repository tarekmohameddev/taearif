<?php

namespace App\Listeners;

use App\Events\UserDowngradedToFree;
use App\Events\UserUpgradedFromFree;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class LogMembershipChange
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param UserDowngradedToFree|UserUpgradedFromFree $event
     * @return void
     */
    public function handle($event)
    {
        $logData = [
            'user_id' => $event->user->id,
            'username' => $event->user->username,
            'email' => $event->user->email,
            'timestamp' => $event->timestamp,
        ];

        if ($event instanceof UserDowngradedToFree) {
            $logData['action'] = 'downgraded_to_free';
            $logData['previous_package'] = $event->previousPackage ? $event->previousPackage->title : 'Unknown';
            
            Log::channel('membership_changes')->info('User downgraded to free package', $logData);
        } elseif ($event instanceof UserUpgradedFromFree) {
            $logData['action'] = 'upgraded_from_free';
            $logData['new_package'] = $event->newPackage ? $event->newPackage->title : 'Unknown';
            
            Log::channel('membership_changes')->info('User upgraded from free package', $logData);
        }
    }
}
