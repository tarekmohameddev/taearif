<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CompressResponse
{
    /**
     * Handle an incoming request.
     * Compresses JSON responses if client accepts gzip encoding.
     * Note: This is a fallback - web server (nginx/apache) compression is preferred.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        // Only compress JSON responses for API routes
        if ($request->is('api/*') && $response->headers->get('Content-Type') === 'application/json') {
            // Check if client accepts gzip encoding
            $acceptEncoding = $request->header('Accept-Encoding', '');
            
            if (strpos($acceptEncoding, 'gzip') !== false) {
                $content = $response->getContent();
                
                // Only compress if content is large enough to benefit (typically > 1KB)
                if (strlen($content) > 1024) {
                    $compressed = gzencode($content, 6); // Compression level 6 (balanced)
                    
                    if ($compressed !== false && strlen($compressed) < strlen($content)) {
                        $response->setContent($compressed);
                        $response->headers->set('Content-Encoding', 'gzip');
                        $response->headers->set('Vary', 'Accept-Encoding');
                    }
                }
            }
        }

        return $response;
    }
}
