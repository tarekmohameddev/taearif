<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\JsonResponse;

trait ResolvesTenant
{
    /** Get the tenant (owner) id for the current sanctum-authenticated user. */
    protected function tenantId(): int
    {
        $user = auth('sanctum')->user() ?? auth()->user();
        if (!$user) {
            abort(response()->json(['status' => 'error', 'message' => 'Unauthenticated'], 401));
        }

        // Use User model's tenantOwnerId method which handles both tenants and employees
        if ($user instanceof \App\Models\User) {
            return $user->tenantOwnerId();
        }

        // Otherwise it’s a tenant owner (App\Models\User)
        return (int) $user->id;
    }

    /** Convenience: return the current authenticated user. */
    protected function currentUser()
    {
        return auth('sanctum')->user() ?? auth()->user();
    }
}
