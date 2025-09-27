<?php

namespace App\Services\TenantWebsite;

use App\Models\Api\ApiDomainSetting;
use App\Models\User;
use Illuminate\Http\Request;

class TenantResolver
{
    public function resolve(Request $request): void
    {
        $host = $request->getHost();
        $cleanHost = str_replace('www.', '', $host);
        $websiteHost = env('WEBSITE_HOST', 'taearif.com');

        $tenantUser = null;

        if (strpos($cleanHost, $websiteHost) === false) {
            $domain = ApiDomainSetting::where('custom_name', $cleanHost)->first();
            if ($domain) {
                $tenantUser = $domain->user;
            }
        }

        if (!$tenantUser && str_ends_with($cleanHost, $websiteHost)) {
            $sub = preg_replace('/\.' . preg_quote($websiteHost, '/') . '$/', '', $cleanHost);
            if ($sub && $sub !== $websiteHost) {
                $tenantUser = User::where('username', $sub)->first();
            }
        }

        if (!$tenantUser) {
            $path = trim($request->path(), '/');
            $segments = explode('/', $path);
            if (count($segments) >= 2 && $segments[0] === 'tenant') {
                $tenantUser = User::where('username', $segments[1])->first();
            }
        }

        if ($tenantUser) {
            $request->attributes->set('tenant_user', $tenantUser);
        }
    }
}


