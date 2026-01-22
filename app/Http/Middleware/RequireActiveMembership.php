<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use App\Models\Membership;

class RequireActiveMembership
{
	public function handle(Request $request, Closure $next)
	{
		$user = Auth::user();
		if (!$user) {
			return response()->json(['message' => 'Unauthenticated'], 401);
		}

		$owner = method_exists($user, 'tenantOwner') ? $user->tenantOwner() : $user;
		
		// Cache membership check for 5 minutes to avoid repeated database queries
		// Key format standardized with MembershipCacheService (colon separator)
		$cacheKey = "active_membership:{$owner->id}";
		$active = Cache::remember($cacheKey, 300, function () use ($owner) {
			$membership = Membership::where('user_id', $owner->id)
				->orderByDesc('expire_date')
				->first();
			
			return $membership && now()->lte($membership->expire_date) && (int) $membership->status === 1;
		});
		
		if (!$active) {
			return response()->json(['message' => 'No active package.'], 402);
		}

		return $next($request);
	}
} 