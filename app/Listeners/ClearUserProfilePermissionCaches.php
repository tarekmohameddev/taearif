<?php

namespace App\Listeners;

use App\Models\User;
use App\Support\CacheInvalidationHelper;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Events\PermissionAttached;
use Spatie\Permission\Events\PermissionDetached;
use Spatie\Permission\Events\RoleAttached;
use Spatie\Permission\Events\RoleDetached;

/**
 * Clears cached /api/user payload + cached permissions payload when RBAC changes.
 *
 * This complements Spatie's internal permission cache flush: we also cache a
 * user-specific permissions array in AuthController::getUserProfile().
 */
class ClearUserProfilePermissionCaches
{
    /**
     * @param  RoleAttached|RoleDetached|PermissionAttached|PermissionDetached  $event
     */
    public function handle(object $event): void
    {
        $model = $event->model ?? null;
        if (!$model instanceof Model) {
            return;
        }

        // We only cache /api/user for our User model.
        if (!$model instanceof User) {
            return;
        }

        $userId = (int) $model->id;
        $ownerId = (int) ($model->tenant_id ?: $userId);

        // Profile payload includes permissions list, so clear BOTH keys.
        CacheInvalidationHelper::clearUserPermissionsCache($userId, $ownerId);
        CacheInvalidationHelper::clearUserProfileCache($userId, $ownerId);

        // Side menus are permission-sensitive too.
        CacheInvalidationHelper::clearSideMenusCache($userId, $ownerId);
    }
}

