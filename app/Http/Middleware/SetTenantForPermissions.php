<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Spatie\Permission\PermissionRegistrar;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Services\Rbac\BootstrapTenantRbac;

class SetTenantForPermissions
{
    public function __construct(protected PermissionRegistrar $registrar) {}

    public function handle(Request $request, Closure $next)
    {
        // Short-circuit CORS preflight to avoid extra DB / RBAC work
        if ($request->isMethod('OPTIONS')) {
            return $next($request);
        }

        /** @var mixed $user */
        $user = $request->user();
        if (!$user || !$user instanceof User) {
            return $next($request);
        }

        // 1) Resolve the tenant owner ONCE
        $owner = $this->resolveOwner($user);
        if ($owner) {
            // 2) Set Spatie team context ONCE
            $teamId = (int) $owner->id;
            $this->registrar->setPermissionsTeamId($teamId);

            // 3) Self-heal RBAC if needed (idempotent + locked)
            $this->maybeBootstrapRbac($owner);
        }

        return $next($request);
    }

    private function resolveOwner(User $user): ?User
    {
        $acct = $user->account_type ?? 'tenant';

        if ($acct === 'tenant') {
            return $user;
        }

        if ($acct === 'employee') {
            // Check if relation is already loaded (most efficient)
            if (method_exists($user, 'tenant') && $user->relationLoaded('tenant') && $user->tenant) {
                return $user->tenant;
            }
            
            // Try to access relation if it exists (may trigger lazy load)
            if (method_exists($user, 'tenant') && $user->tenant) {
                return $user->tenant;
            }
            
            // Cache tenant lookup to avoid repeated database queries
            // OPTIMIZATION: Select only needed columns to reduce data transfer
            if ($user->tenant_id) {
                $cacheKey = "tenant_user_{$user->tenant_id}";
                return Cache::remember($cacheKey, 60, function () use ($user) {
                    return User::select('id', 'username', 'account_type', 'rbac_version', 'rbac_seeded_at')
                        ->find($user->tenant_id);
                });
            }
        }

        return null;
    }

    private function maybeBootstrapRbac(User $owner): void
    {
        $current  = (int) ($owner->rbac_version ?? 0);
        $target   = (int) config('rbac.version', 1);
        if ($current >= $target) return;

        $lockKey      = "rbac:seed:tenant:{$owner->id}";
        $lockSeconds  = (int) config('rbac.lock_seconds', 60);
        $blockSeconds = (int) config('rbac.block_seconds', 10);

        Cache::lock($lockKey, $lockSeconds)->block($blockSeconds, function () use ($owner, $target) {
            $fresh = $owner->fresh();
            if ((int) ($fresh->rbac_version ?? 0) >= $target) return;

            app(BootstrapTenantRbac::class)->run($fresh);

            $fresh->forceFill([
                'rbac_version'   => $target,
                'rbac_seeded_at' => now(),
            ])->saveQuietly();
        });
    }
}
