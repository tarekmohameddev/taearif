<?php

namespace App\Providers;

use App\Models\ApiCustomer;
use App\Observers\CrmCardObserver;
use App\Models\Api\Crm\CrmCard;
use App\Models\Logs\CustomerLog;
use App\Models\Logs\ProjectLog;
use App\Models\Logs\PropertyLog;
use App\Models\Membership;

use App\Observers\ProjectObserver;
use App\Observers\PropertyObserver;
use Illuminate\Support\Facades\Event;
use App\Events\TenantActivityOccurred;
use App\Events\UserDowngradedToFree;
use App\Events\UserUpgradedFromFree;
use App\Observers\ApiCustomerObserver;
use App\Observers\MembershipObserver;
use Illuminate\Auth\Events\Registered;

use App\Listeners\PersistTenantActivity;
use App\Listeners\WriteTenantActivityLog;
use App\Listeners\EnableMaintenanceMode;
use App\Listeners\DisableMaintenanceMode;
use App\Listeners\LogMembershipChange;
use App\Listeners\GiveWelcomeCredits;
use App\Models\User\RealestateManagement\Project;
use App\Models\User\RealestateManagement\Property;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use App\Http\Middleware\SetTenantForPermissions; // the middleware we added earlier
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
            GiveWelcomeCredits::class,
        ],
        TenantActivityOccurred::class => [
            PersistTenantActivity::class,
            // WriteTenantActivityLog::class,
        ],
        UserDowngradedToFree::class => [
            EnableMaintenanceMode::class,
            LogMembershipChange::class,
        ],
        UserUpgradedFromFree::class => [
            DisableMaintenanceMode::class,
            LogMembershipChange::class,
        ],
    ];


    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot(): void
    {
        ApiCustomer::observe(ApiCustomerObserver::class);
        Project::observe(ProjectObserver::class);
        Property::observe(PropertyObserver::class);
        CrmCard::observe(CrmCardObserver::class);
        \App\Models\WhatsappAddon::observe(\App\Observers\WhatsappAddonObserver::class);
        \App\Models\Membership::observe(MembershipObserver::class);
    }
}
