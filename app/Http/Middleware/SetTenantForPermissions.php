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
    public function handle(Request $request, Closure $next)
    {
        if ($user = $request->user()) {
            $tenantId = method_exists($user, 'tenantOwnerId') ? $user->tenantOwnerId() : ($user->tenant_id ?: $user->id);
            app(PermissionRegistrar::class)->setPermissionsTeamId($tenantId);
        }

        /** @var User|null $user */
        $user = $request->user();
        if (!$user) {
            return $next($request);
        }

        // Resolve the tenant owner for this request
        $owner = $this->resolveOwner($user);
        if (!$owner) {
            // Nothing to scope; let it pass
            return $next($request);
        }

        // Scope Spatie's team to the owner (tenant)
        app(PermissionRegistrar::class)->setPermissionsTeamId((int) $owner->id);

        // Self-heal RBAC for legacy tenants
        $this->maybeBootstrapRbac($owner);


        return $next($request);
    }

    private function resolveOwner(User $user): ?User
    {
        // If this is a tenant/owner account
        if (method_exists($user, 'isTenant') ? $user->isTenant() : (($user->account_type ?? 'tenant') === 'tenant')) {
            return $user;
        }

        // If employee, use their tenant owner (relation or tenant_id)
        if (method_exists($user, 'isEmployee') ? $user->isEmployee() : (($user->account_type ?? '') === 'employee')) {
            // Prefer relation if you have it, fallback to id
            if (method_exists($user, 'tenant') && $user->relationLoaded('tenant')) {
                return $user->tenant;
            }
            if (method_exists($user, 'tenant') && $user->tenant) {
                return $user->tenant;
            }
            if ($user->tenant_id) {
                return User::find($user->tenant_id);
            }
        }

        return null;
    }

    private function maybeBootstrapRbac(User $owner): void
    {
        $current  = (int) ($owner->rbac_version ?? 0);
        $target   = (int) config('rbac.version', 1);

        if ($current >= $target) {
            return; // up-to-date
        }

        $lockKey      = "rbac:seed:tenant:{$owner->id}";
        $lockSeconds  = (int) config('rbac.lock_seconds', 30);
        $blockSeconds = (int) config('rbac.block_seconds', 5);

        try {
            Cache::lock($lockKey, $lockSeconds)->block($blockSeconds, function () use ($owner, $target) {
                // Re-check inside the lock (another request may have finished it)
                $fresh = $owner->fresh();
                if ((int) ($fresh->rbac_version ?? 0) >= $target) {
                    return;
                }

                /** @var BootstrapTenantRbac $bootstrap */
                $bootstrap = app(BootstrapTenantRbac::class);

                // Should be idempotent and merge-friendly
                $bootstrap->run($fresh);

                $fresh->forceFill([
                    'rbac_version'   => $target,
                    'rbac_seeded_at' => now(),
                ])->saveQuietly();

                Log::info('RBAC bootstrapped/updated for tenant', [
                    'tenant_id'   => $fresh->id,
                    'to_version'  => $target,
                ]);
            });
        } catch (\Throwable $e) {
            // Never block the request; just log
            Log::error('RBAC bootstrap failed', [
                'tenant_id' => $owner->id,
                'message'   => $e->getMessage(),
            ]);
        }
    }

}
