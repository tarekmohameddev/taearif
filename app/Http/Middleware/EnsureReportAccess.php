<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Grants access to the Reports module and sets a scope attribute used by
 * report services to determine whether the user sees all data or only their own.
 *
 * - Tenant owner (isTenant): scope = 'all'
 * - Employee: scope = 'self' (services filter to their own records)
 *
 * Unauthenticated / inactive users are rejected.
 */
class EnsureReportAccess
{
    public function handle(Request $request, Closure $next): mixed
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['status' => 'error', 'message' => 'Unauthenticated'], 401);
        }

        if (method_exists($user, 'isTenant') && $user->isTenant()) {
            $request->attributes->set('report_scope', 'all');
            $request->attributes->set('report_user_id', $user->id);
            return $next($request);
        }

        // Employee: can see reports but only their own rows in agent/employee tables
        if (method_exists($user, 'isEmployee') && $user->isEmployee()) {
            $request->attributes->set('report_scope', 'self');
            $request->attributes->set('report_user_id', $user->tenantOwnerId());
            $request->attributes->set('report_actor_id', $user->id);
            return $next($request);
        }

        return response()->json(['status' => 'error', 'message' => 'Forbidden'], 403);
    }
}
