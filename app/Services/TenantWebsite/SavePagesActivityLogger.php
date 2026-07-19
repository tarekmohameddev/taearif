<?php

namespace App\Services\TenantWebsite;

use App\Models\TenantWebsiteSavePagesLog;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class SavePagesActivityLogger
{
    /**
     * Persist a save-pages activity log entry. Never throws: a logging failure
     * must not fail the underlying save-pages request.
     *
     * @param  array<string, mixed>  $loginSessionMeta
     * @param  array<string, mixed>  $before
     * @param  array<string, mixed>  $after
     */
    public function log(User $tenant, string $tenantIdValue, array $loginSessionMeta, array $before, array $after): void
    {
        try {
            $request = request();

            TenantWebsiteSavePagesLog::create([
                'tenant_id' => $tenant->id,
                'username' => $tenant->username,
                'tenant_id_value' => $tenantIdValue,
                'login_session_meta' => $loginSessionMeta,
                'server_ip' => $request?->ip(),
                'server_user_agent' => $request?->userAgent(),
                'before' => $before,
                'after' => $after,
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::error('SavePagesActivityLogger: failed to write activity log', [
                'tenant_id' => $tenant->id ?? null,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
