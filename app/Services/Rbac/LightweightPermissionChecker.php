<?php

declare(strict_types=1);

namespace App\Services\Rbac;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Spatie's permission cache hydrates every role/permission/pivot into Eloquent
 * models. With thousands of tenant roles that exceeds PHP memory and pins CPU.
 * This checker answers "does this user have this permission?" with a small
 * cached list of permission names fetched via raw SQL.
 */
final class LightweightPermissionChecker
{
    private const CACHE_TTL_SECONDS = 120;

    /**
     * Abilities that must fall through to Laravel policies, not RBAC.
     * These match the Gate::before exclusion in AuthServiceProvider.
     */
    private const POLICY_ABILITIES = ['control', 'disable', 'enable', 'toggle'];

    public function userHasPermission(User $user, string $permission): bool
    {
        if ($permission === '' || in_array($permission, self::POLICY_ABILITIES, true)) {
            return false;
        }

        if ($user->isTenant()) {
            return true;
        }

        return in_array($permission, $this->permissionNamesFor($user), true);
    }

    /**
     * @return list<string>
     */
    public function permissionNamesFor(User $user): array
    {
        $userId = (int) $user->id;
        $teamId = $user->tenantOwnerId();
        $cacheKey = "rbac:perm_names:{$userId}:{$teamId}";

        return Cache::remember($cacheKey, self::CACHE_TTL_SECONDS, function () use ($user, $userId, $teamId): array {
            $modelType = $user->getMorphClass();

            $viaRoles = DB::table('api_model_has_roles as mhr')
                ->join('api_role_has_permissions as rhp', 'rhp.role_id', '=', 'mhr.role_id')
                ->join('api_permissions as p', 'p.id', '=', 'rhp.permission_id')
                ->where('mhr.model_id', $userId)
                ->where('mhr.model_type', $modelType)
                ->where('mhr.team_id', $teamId)
                ->pluck('p.name');

            $direct = DB::table('api_model_has_permissions as mhp')
                ->join('api_permissions as p', 'p.id', '=', 'mhp.permission_id')
                ->where('mhp.model_id', $userId)
                ->where('mhp.model_type', $modelType)
                ->where('mhp.team_id', $teamId)
                ->pluck('p.name');

            return $viaRoles->merge($direct)->unique()->values()->all();
        });
    }

    public static function forgetFor(int $userId, int $teamId): void
    {
        Cache::forget("rbac:perm_names:{$userId}:{$teamId}");
    }
}
