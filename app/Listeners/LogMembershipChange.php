<?php

namespace App\Listeners;

use App\Events\UserDowngradedToFree;
use App\Events\UserUpgradedFromFree;
use App\Models\MembershipChangeLog;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

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
            'event_timestamp' => $event->timestamp,
        ];

        if ($event instanceof UserDowngradedToFree) {
            $logData['action'] = 'downgraded_to_free';
            $logData['previous_package'] = $event->previousPackage ? $event->previousPackage->title : 'Unknown';
            $logData['new_package'] = null;
        } elseif ($event instanceof UserUpgradedFromFree) {
            $logData['action'] = 'upgraded_from_free';
            $logData['previous_package'] = null;
            $logData['new_package'] = $event->newPackage ? $event->newPackage->title : 'Unknown';
        }

        // Save to database
        MembershipChangeLog::create($logData);
    }
}
