<?php

namespace App\Providers;

use App\Models\ApiCustomer;
use App\Models\User;
use App\Models\Api\ApiInstallation;
use App\Models\Api\ApiSidebarItem;
use App\Models\Api\PropertyRequestAutoCustomerSetting;
use App\Models\User\RealestateManagement\ApiUserCategory;
use App\Models\User\RealestateManagement\UserFacade;
use App\Observers\CrmCardObserver;
use App\Models\Api\Crm\CrmCard;
use App\Models\Logs\CustomerLog;
use App\Models\Logs\ProjectLog;
use App\Models\Logs\PropertyLog;
use App\Models\Membership;

use App\Observers\ProjectObserver;
use App\Observers\PropertyObserver;
use App\Observers\UserObserver;
use App\Observers\ApiInstallationObserver;
use App\Observers\ApiSidebarItemObserver;
use App\Observers\ApiUserCategoryObserver;
use App\Observers\UserFacadeObserver;
use App\Observers\PropertyRequestAutoCustomerSettingObserver;
use Illuminate\Support\Facades\Event;
use App\Events\PropertyStatusChanged;
use App\Events\TenantActivityOccurred;
use App\Events\UserDowngradedToFree;
use App\Events\UserUpgradedFromFree;
use App\Listeners\CloseCrmDealsOnPropertySold;
use App\Listeners\NotifyTeamOnStatusChange;
use App\Observers\ApiCustomerObserver;
use App\Observers\MembershipObserver;
use Illuminate\Auth\Events\Registered;

use App\Listeners\PersistTenantActivity;
use App\Listeners\WriteTenantActivityLog;
use App\Listeners\EnableMaintenanceMode;
use App\Listeners\DisableMaintenanceMode;
use App\Listeners\LogMembershipChange;
use App\Listeners\GiveWelcomeCredits;
use App\Listeners\ClearUserProfilePermissionCaches;
use App\Models\User\RealestateManagement\Project;
use App\Models\User\RealestateManagement\Property;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Spatie\Permission\Events\PermissionAttached;
use Spatie\Permission\Events\PermissionDetached;
use Spatie\Permission\Events\RoleAttached;
use Spatie\Permission\Events\RoleDetached;
use App\Models\Api\ApiDomainSetting;
use App\Models\User\BasicSetting;
use App\Observers\ApiDomainSettingObserver;
use App\Observers\UserBasicSettingObserver;

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

        // RBAC changes should invalidate cached /api/user payload
        RoleAttached::class => [
            ClearUserProfilePermissionCaches::class,
        ],
        RoleDetached::class => [
            ClearUserProfilePermissionCaches::class,
        ],
        PermissionAttached::class => [
            ClearUserProfilePermissionCaches::class,
        ],
        PermissionDetached::class => [
            ClearUserProfilePermissionCaches::class,
        ],

        // Phase 4 Communication automation (commit-safe, queued)
        \App\Domain\Communication\Events\MessageReceived::class => [
            \App\Domain\Communication\Listeners\HandleMessageReceived::class,
        ],
        \App\Domain\Communication\Events\MessageSent::class => [
            \App\Domain\Communication\Listeners\HandleMessageSent::class,
            \App\Domain\Communication\Listeners\SyncWhatsAppConversationStateOnMessageSent::class,
        ],
        \App\Domain\Communication\Events\ConversationOpened::class => [
            \App\Domain\Communication\Listeners\HandleConversationOpened::class,
        ],

        PropertyStatusChanged::class => [
            NotifyTeamOnStatusChange::class,
            CloseCrmDealsOnPropertySold::class,
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
        
        // Cache invalidation observers
        // Senior Rule: "If data can change → it MUST have forget() somewhere"
        User::observe(UserObserver::class);
        ApiInstallation::observe(ApiInstallationObserver::class);
        ApiSidebarItem::observe(ApiSidebarItemObserver::class);
        ApiUserCategory::observe(ApiUserCategoryObserver::class);
        UserFacade::observe(UserFacadeObserver::class);
        PropertyRequestAutoCustomerSetting::observe(PropertyRequestAutoCustomerSettingObserver::class);

        // Profile payload dependencies for /api/user
        ApiDomainSetting::observe(ApiDomainSettingObserver::class);
        BasicSetting::observe(UserBasicSettingObserver::class);
    }
}
