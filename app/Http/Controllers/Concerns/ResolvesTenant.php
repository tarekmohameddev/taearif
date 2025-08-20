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

        // If an employee is logged in, it belongs to a tenant via user_id
        if ($user instanceof \App\Models\Api\Employee) {
            return (int) $user->user_id;
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
