<?php

namespace App\Http\Middleware;

use App\Http\Controllers\Api\V1\TenantWebsite\Concerns\ResolvesTenant;
use App\Models\Api\GeneralSetting;
use App\Models\User;
use Closure;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;

class TenantSiteMaintenance
{
    use ResolvesTenant;

    /**
     * Return JSON 503 for public tenant-website traffic when that tenant's
     * api_general_settings.maintenance_mode flag is on.
     *
     * Owner, employees of that tenant, and admin impersonation tokens pass through.
     * Routes with no resolvable tenant (getTenant, catalog, save-pages) fail open.
     */
    public function handle(Request $request, Closure $next)
    {
        $tenant = $this->tenantFromRequest($request);

        if (!$tenant) {
            return $next($request);
        }

        $setting = GeneralSetting::where('user_id', $tenant->id)->first();

        if (!$setting || !$setting->maintenance_mode) {
            return $next($request);
        }

        if ($this->requesterMayBypass($tenant)) {
            return $next($request);
        }

        return response()->json([
            'maintenance' => true,
            'message'     => __('This website is currently under maintenance'),
        ], 503);
    }

    /**
     * {tenantId} is a username or custom domain, not a primary key.
     */
    protected function tenantFromRequest(Request $request): ?User
    {
        $tenantId = $request->route('tenantId');

        if ($tenantId !== null && $tenantId !== '') {
            try {
                return $this->resolveTenant($request, (string) $tenantId);
            } catch (ModelNotFoundException $e) {
                // Fall through to host-based tenant, then fail open.
            }
        }

        $hostTenant = $request->attributes->get('tenant_user');

        return $hostTenant instanceof User ? $hostTenant : null;
    }

    /**
     * Allow the tenant owner, their employees, and admin impersonation tokens.
     * Any other authenticated user (including other tenants) is still blocked.
     */
    protected function requesterMayBypass(User $tenant): bool
    {
        $user = auth('sanctum')->user();

        if (!$user) {
            return false;
        }

        if ((int) $user->id === (int) $tenant->id) {
            return true;
        }

        if ($this->isEmployeeOfTenant($user, $tenant)) {
            return true;
        }

        return $this->isImpersonatingTenant($user, $tenant);
    }

    protected function isEmployeeOfTenant($user, User $tenant): bool
    {
        return !empty($user->tenant_id) && (int) $user->tenant_id === (int) $tenant->id;
    }

    /**
     * secretLogin mints a Sanctum token on the tenant with ability `impersonate`.
     * ImpersonationService / ImpersonationController mint tokens named impersonated-by-*
     * (often with ['*']). Those tokens authenticate as the impersonated user, so the
     * owner-id check above already covers them; this is an explicit extra signal.
     */
    protected function isImpersonatingTenant($user, User $tenant): bool
    {
        if (!method_exists($user, 'currentAccessToken')) {
            return false;
        }

        $token = $user->currentAccessToken();
        if (!$token) {
            return false;
        }

        $name = (string) ($token->name ?? '');
        $abilities = $token->abilities ?? [];
        $isImpersonationToken = str_starts_with($name, 'impersonated-by')
            || in_array('impersonate', $abilities, true);

        if (!$isImpersonationToken) {
            return false;
        }

        return (int) $user->id === (int) $tenant->id
            || $this->isEmployeeOfTenant($user, $tenant);
    }
}
