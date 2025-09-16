<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use App\Models\User;

class SetTenantSessionDomain
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        // Get the current domain from the request
        $currentDomain = $request->getHost();
        $parsedUrl = parse_url($request->url());
        $host = $parsedUrl['host'] ?? '';
        
        // Remove www. prefix for comparison
        $cleanHost = str_replace('www.', '', $host);
        
        // Check if this is a custom domain (not the main website host)
        $websiteHost = env('WEBSITE_HOST', 'taearif.com');
        
        if (strpos($cleanHost, $websiteHost) === false) {
            // This is a custom domain - set session domain to the custom domain
            Config::set('session.domain', $cleanHost);
            Config::set('session.secure', $request->secure());
            Config::set('session.same_site', 'lax');
            
            // Also set cookie domain for CSRF token
            Config::set('session.cookie', 'taearif_session_' . md5($cleanHost));
        } else {
            // This is a subdomain or path-based URL
            if ($cleanHost !== $websiteHost) {
                // Subdomain: username.taearif.com
                Config::set('session.domain', '.' . $websiteHost);
                Config::set('session.secure', $request->secure());
                Config::set('session.same_site', 'lax');
            } else {
                // Main domain: taearif.com
                Config::set('session.domain', $websiteHost);
                Config::set('session.secure', $request->secure());
                Config::set('session.same_site', 'lax');
            }
        }
        
        return $next($request);
    }
}
