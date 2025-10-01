<?php

namespace App\Listeners;

use App\Events\UserUpgradedFromFree;
use App\Services\MembershipService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class DisableMaintenanceMode
{
    protected $membershipService;

    /**
     * Create the event listener.
     *
     * @param MembershipService $membershipService
     */
    public function __construct(MembershipService $membershipService)
    {
        $this->membershipService = $membershipService;
    }

    /**
     * Handle the event.
     *
     * @param UserUpgradedFromFree $event
     * @return void
     */
    public function handle(UserUpgradedFromFree $event)
    {
        try {
            $this->membershipService->disableMaintenanceMode($event->user);
            
            Log::info('Maintenance mode disabled for user via event', [
                'user_id' => $event->user->id,
                'username' => $event->user->username,
                'new_package' => $event->newPackage ? $event->newPackage->title : 'Unknown',
                'timestamp' => $event->timestamp
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to disable maintenance mode via event', [
                'user_id' => $event->user->id,
                'error' => $e->getMessage()
            ]);
        }
    }
}
