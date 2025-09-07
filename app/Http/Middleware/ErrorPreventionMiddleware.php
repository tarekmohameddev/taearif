<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use App\Models\User\Language;

class ErrorPreventionMiddleware
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
        try {
            $response = $next($request);
            return $response;
        } catch (\ErrorException $e) {
            // Check if it's the specific error we're trying to prevent
            if (strpos($e->getMessage(), 'Attempt to read property "code" on null') !== false) {
                Log::error('Prevented null property access error', [
                    'url' => $request->fullUrl(),
                    'user_agent' => $request->userAgent(),
                    'error' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ]);
                
                // Try to fix the issue automatically
                $this->attemptAutoFix($request);
                
                // Return a user-friendly error page
                return response()->view('errors.500', [
                    'message' => 'We are currently experiencing technical difficulties. Please try again later.'
                ], 500);
            }
            
            // Re-throw other errors
            throw $e;
        }
    }
    
    private function attemptAutoFix(Request $request)
    {
        try {
            // Extract username from URL
            $path = $request->path();
            $segments = explode('/', $path);
            $username = $segments[0] ?? null;
            
            if ($username) {
                $user = User::where('username', $username)->first();
                if ($user && $user->languages()->count() == 0) {
                    // Auto-fix missing language
                    $deLang = Language::where('user_id', 0)->first();
                    if ($deLang) {
                        $lang = new Language;
                        $lang->name = $deLang->name;
                        $lang->code = $deLang->code;
                        $lang->is_default = 1;
                        $lang->rtl = $deLang->rtl;
                        $lang->user_id = $user->id;
                        $lang->keywords = $deLang->keywords;
                        $lang->save();
                        
                        Log::info("Auto-fixed missing language for user: {$user->username}");
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error('Auto-fix failed: ' . $e->getMessage());
        }
    }
}
