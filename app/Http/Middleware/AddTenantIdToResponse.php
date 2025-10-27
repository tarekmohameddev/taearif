<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AddTenantIdToResponse
{
    /**
     * Add tenant_id to all JSON responses
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        // Extract tenant_id from route parameter
        $tenantId = $request->route('tenantId');

        // Only modify JSON responses
        if ($response instanceof JsonResponse && !empty($tenantId)) {
            $data = $response->getData(true);
            
            // Add tenant_id at the root level if not already present
            if (!isset($data['tenant_id'])) {
                $data = array_merge(['tenant_id' => $tenantId], $data);
                $response->setData($data);
            }
        }

        return $response;
    }
}

