<?php

namespace App\Listeners;

use App\Models\User;
use App\Services\Rbac\LightweightPermissionChecker;
use App\Support\CacheInvalidationHelper;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Contracts\Role as RoleContract;
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

        // When a role's own permissions are synced ($role->syncPermissions()), Spatie fires
        // PermissionAttached/PermissionDetached with the Role as the model. The lightweight
        // per-user name-list cache won't be cleared by the User branch below, so we must
        // flush it for every employee who holds this role in this tenant.
        if ($model instanceof RoleContract) {
            $this->forgetForUsersWithRole($model);
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

        // Lightweight RBAC name-list cache must also be invalidated so the next
        // can() check reflects the grant/revoke immediately.
        LightweightPermissionChecker::forgetFor($userId, $ownerId);
    }

    /**
     * Flush the lightweight permission-name cache for every user who holds the given
     * role, so that permission changes on the role template propagate immediately.
     */
    private function forgetForUsersWithRole(RoleContract $role): void
    {
        $teamId = (int) ($role->team_id ?? 0);
        if ($teamId <= 0) {
            // Global (team_id = NULL) roles are not used in this app for per-user scoping.
            return;
        }

        $table  = config('permission.table_names.model_has_roles', 'api_model_has_roles');
        $teamFk = config('permission.column_names.team_foreign_key', 'team_id');

        $userIds = DB::table($table)
            ->where('role_id', $role->id)
            ->where($teamFk, $teamId)
            ->pluck('model_id');

        foreach ($userIds as $userId) {
            LightweightPermissionChecker::forgetFor((int) $userId, $teamId);
        }
    }
}

