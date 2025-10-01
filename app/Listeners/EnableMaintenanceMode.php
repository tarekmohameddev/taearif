<?php

namespace App\Listeners;

use App\Events\UserDowngradedToFree;
use App\Services\MembershipService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class EnableMaintenanceMode
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
     * @param UserDowngradedToFree $event
     * @return void
     */
    public function handle(UserDowngradedToFree $event)
    {
        try {
            $this->membershipService->enableMaintenanceMode($event->user);
            
            Log::info('Maintenance mode enabled for user via event', [
                'user_id' => $event->user->id,
                'username' => $event->user->username,
                'previous_package' => $event->previousPackage ? $event->previousPackage->title : 'Unknown',
                'timestamp' => $event->timestamp
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to enable maintenance mode via event', [
                'user_id' => $event->user->id,
                'error' => $e->getMessage()
            ]);
        }
    }
}
