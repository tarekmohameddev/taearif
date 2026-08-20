<?php

namespace App\Exceptions;

use App\Models\Language;
use App\Models\User;
use App\Models\User\BasicSetting;
use App\Models\User\Language as UserLanguage;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Throwable;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use App\Exceptions\Api\ApiException;
use App\Http\Responses\ApiResponse;
use App\Services\Payment\PaymentIframeResult;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
     */
    protected $dontFlash = [
        'password',
        'password_confirmation',
    ];

    /**
     * Report or log an exception.
     *
     * @param  \Throwable  $exception
     * @return void
     *
     * @throws \Throwable
     */
    public function report(Throwable $exception)
    {
        parent::report($exception);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Throwable  $exception
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Throwable
     */
    public function render($request, Throwable $exception)
    {
        if (
            !$request->expectsJson()
            && $request->routeIs(
                'api.credits.payment.success',
                'api.credits.payment.cancel',
                'api.credits.payment.failed'
            )
        ) {
            return app(PaymentIframeResult::class)->respond($request, [
                'status' => 'failed',
                'gateway' => $request->route('gateway'),
                'transaction_id' => $request->route('transaction_id'),
            ]);
        }

        // ── JSON for API calls ──
        if ($request->expectsJson() || $request->is('api/*')) {
            // NEW: Use ApiResponse helper for consistent error handling

            // If it's our custom ApiException, let it render itself (includes security)
            if ($exception instanceof ApiException) {
                return $exception->render();
            }

            // Handle BusinessLogicException (includes marketplace exceptions)
            if ($exception instanceof \App\Exceptions\BusinessLogicException) {
                return $exception->render();
            }

            // Validation (you also have invalidJson; this is just a safeguard)
            if ($exception instanceof ValidationException) {
                return response()->json([
                    'status'  => 'error',
                    'code'    => 'VALIDATION_FAILED',
                    'message' => 'Validation failed',
                    'errors'  => $exception->errors(),
                    'timestamp' => now()->toIso8601String(),
                ], 422);
            }

            // Not found (model/route)
            if ($exception instanceof ModelNotFoundException) {
                return response()->json([
                    'status' => 'error',
                    'code' => 'RESOURCE_NOT_FOUND',
                    'message' => 'Resource not found',
                    'timestamp' => now()->toIso8601String(),
                ], 404);
            }
            if ($exception instanceof NotFoundHttpException || $exception instanceof RouteNotFoundException) {
                return response()->json([
                    'status' => 'error',
                    'code' => 'ENDPOINT_NOT_FOUND',
                    'message' => 'Endpoint not found',
                    'timestamp' => now()->toIso8601String(),
                ], 404);
            }

            // Auth
            if ($exception instanceof AuthenticationException) {
                return response()->json([
                    'status' => 'error',
                    'code' => 'UNAUTHORIZED',
                    'message' => 'Authentication required',
                    'timestamp' => now()->toIso8601String(),
                ], 401);
            }

            // Authorization/Permission denied
            if ($exception instanceof AuthorizationException) {
                \Log::warning('Authorization failed', [
                    'exception_class' => get_class($exception),
                    'message' => $exception->getMessage(),
                    'url' => $request->fullUrl(),
                    'method' => $request->method(),
                    'user_id' => auth()->id(),
                    'ip' => $request->ip(),
                ]);

                return response()->json([
                    'status' => 'error',
                    'code' => 'FORBIDDEN',
                    'message' => 'You do not have permission to perform this action',
                    'timestamp' => now()->toIso8601String(),
                ], 403);
            }

            // DB - SECURITY: Never expose SQL in production
            if ($exception instanceof QueryException) {
                \Log::error('Database error', [
                    'exception_class' => get_class($exception),
                    'message' => $exception->getMessage(),
                    'sql' => $exception->getSql() ?? 'N/A',
                    'bindings' => $exception->getBindings() ?? [],
                    'url' => $request->fullUrl(),
                    'user_id' => auth()->id(),
                    'tenant_id' => $request->route('tenantId'),
                ]);

                return response()->json([
                    'status'    => 'error',
                    'code'      => 'DATABASE_ERROR',
                    'message'   => config('app.debug')
                        ? 'Database error: ' . $exception->getMessage()
                        : 'A database error occurred. Please try again later.',
                    'timestamp' => now()->toIso8601String(),
                ], 500);
            }

            // Rate limiting (throttle middleware) — expected, not an application bug
            if ($exception instanceof ThrottleRequestsException) {
                \Log::warning('Rate limit exceeded', [
                    'url' => $request->fullUrl(),
                    'method' => $request->method(),
                    'tenant_id' => $request->route('tenantId'),
                    'user_id' => auth()->id(),
                    'ip' => $request->ip(),
                ]);

                $payload = [
                    'status' => 'error',
                    'code' => 'RATE_LIMITED',
                    'message' => 'Too many requests. Please wait a moment and try again.',
                    'timestamp' => now()->toIso8601String(),
                ];
                if ($request->route('tenantId')) {
                    $payload['tenant_id'] = $request->route('tenantId');
                }

                return response()->json($payload, 429, $exception->getHeaders());
            }

            // Fallback - Log the error for debugging
            \Log::error('Unhandled API exception', [
                'exception_class' => get_class($exception),
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'user_id' => auth()->id(),
                'tenant_id' => $request->route('tenantId'),
                'ip' => $request->ip(),
            ]);

            // SECURITY: Sanitize error message in production
            $errorMessage = config('app.debug')
                ? $exception->getMessage()
                : $this->sanitizeErrorMessage($exception->getMessage());

            return response()->json([
                'status'    => 'error',
                'code'      => 'INTERNAL_ERROR',
                'message'   => $errorMessage,
                'exception' => config('app.debug') ? class_basename($exception) : null,
                'timestamp' => now()->toIso8601String(),
            ], 500);
        }

        // Handle BusinessLogicException for web requests
        if ($exception instanceof \App\Exceptions\BusinessLogicException) {
            \Log::warning('Business logic violation', [
                'exception_class' => get_class($exception),
                'message' => $exception->getMessage(),
                'error_code' => $exception->getErrorCode(),
                'url' => $request->fullUrl(),
                'user_id' => auth()->id(),
            ]);

            // For AJAX requests, return JSON
            if ($request->expectsJson() || $request->ajax()) {
                return $exception->render();
            }

            // For regular web requests, flash error and redirect back
            \Session::flash('error', $exception->getMessage());
            return back();
        }

        // Handle ResourceNotFoundException for web requests
        if ($exception instanceof \App\Exceptions\ResourceNotFoundException) {
            \Log::warning('Resource not found', [
                'exception_class' => get_class($exception),
                'message' => $exception->getMessage(),
                'url' => $request->fullUrl(),
                'user_id' => auth()->id(),
            ]);

            // For AJAX requests, return JSON
            if ($request->expectsJson() || $request->ajax()) {
                return $exception->render();
            }

            // For regular web requests, flash error and redirect back
            \Session::flash('error', $exception->getMessage());
            return back();
        }

        //check if exception is an instance of ModelNotFoundException.
        if ($exception instanceof ModelNotFoundException) {
            // normal 404 view page feedback
            // path based user URL


            if ((str_replace("www.", "", Request::getHost()) == env('WEBSITE_HOST') && strpos(Request::route()->getPrefix(), '{username}') !== false)) {
                $user = User::where('username', Request::route('username'));
                if ($user->count() > 0) {
                    $user = $user->first();
                    $userBs = $user->basic_setting;
                    $keywords = $this->userLocal($user);
                    return response()->view('errors.user-404', ['userBs' => $userBs, 'keywords' => $keywords], 404);
                } else {
                    $this->adminLocal();
                    return response()->view('errors.404', [], 404);
                }
            }
            // custom domain & subdomain based user URL
            elseif (Request::getHost() != env('WEBSITE_HOST')) {
                // if its a subdomain
                if (strpos(Request::getHost(), env('WEBSITE_HOST')) !== false) {
                    // if subdomain based URL, get username & fetch user & user_basic_settings
                    $host = Request::getHost();
                    $host = str_replace("www.", "", $host);
                    $hostArr = explode('.', $host);
                    $username = $hostArr[0];
                    $user = User::where('username', $username)->first();
                    if ($user->count() > 0) {
                        $userBs = $user->first()->basic_setting;
                        $keywords = $this->userLocal($user);
                        return response()->view('errors.user-404', ['userBs' => $userBs, 'keywords' => $keywords], 404);
                    }
                } else {
                    $host = Request::getHost();
                    // Always include 'www.' at the begining of host
                    if (substr($host, 0, 4) == 'www.') {
                        $host = $host;
                    } else {
                        $host = 'www.' . $host;
                    }

                    $user = User::whereHas('user_custom_domains', function ($q) use ($host) {
                        $q->where('status', '=', 1)
                            ->where(function ($query) use ($host) {
                                $query->where('requested_domain', '=', $host)
                                    ->orWhere('requested_domain', '=', str_replace("www.", "", $host));
                            });
                        // fetch the custom domain , if it matches 'with www.' URL or 'without www.' URL
                    })->first();
                    if ($user->count() > 0) {
                        $user = $user->first();
                        $userBs = $user->basic_setting;
                        $keywords = $this->userLocal($user);
                        return response()->view('errors.user-404', ['userBs' => $userBs, 'keywords' => $keywords], 404);
                    } else {
                        $this->adminLocal();
                        return response()->view('errors.404', [], 404);
                    }
                }
            }
            // main website 404 page
            else {
                $this->adminLocal();
                return response()->view('errors.404', [], 404);
            }
        }
        return parent::render($request, $exception);
    }

    private function adminLocal()
    {
        if (session()->has('lang')) {
            app()->setLocale(session()->get('lang'));
        } else {
            $defaultLang = Language::where('is_default', 1)->first();
            if (!empty($defaultLang)) {
                app()->setLocale($defaultLang->code);
            }
        }
    }

    private function userLocal($user)
    {
        if (session()->has('user_lang')) {
            $code = session()->get('user_lang');
            $lan = UserLanguage::where([['user_id', $user->id], ['code', $code]])->first();
            return json_decode($lan->keywords, true);
        } else {
            $lan = UserLanguage::where([['user_id', $user->id], ['is_default', 1]])->first();

            return json_decode($lan->keywords, true);
        }
    }

    protected function invalidJson($request, ValidationException $exception)
    {
        return response()->json([
            'status'  => 'error',
            'code'    => 'VALIDATION_FAILED',
            'message' => 'Validation failed',
            'errors'  => $exception->errors(),
            'timestamp' => now()->toIso8601String(),
        ], $exception->status);
    }

    /**
     * Sanitize error message to prevent sensitive data exposure
     *
     * SECURITY: Removes file paths, SQL, credentials from error messages
     *
     * @param string $message
     * @return string
     */
    private function sanitizeErrorMessage(string $message): string
    {
        // If message is empty, return generic message
        if (empty($message)) {
            return 'An unexpected error occurred';
        }

        // List of patterns to remove (security-sensitive)
        $patterns = [
            // File paths (Windows & Linux)
            '#([A-Z]:\\\\|/)[\w\\/\\\\\-\.]+#i',
            // SQL queries
            '#SELECT .+ FROM .+#i',
            '#INSERT INTO .+#i',
            '#UPDATE .+ SET .+#i',
            '#DELETE FROM .+#i',
            // Database connection strings
            '#mysql:host=.+#i',
            '#pgsql:host=.+#i',
            // Passwords/tokens in messages
            '#password[\'"]?\s*[:=]\s*[\'"]?.+[\'"]?#i',
            '#token[\'"]?\s*[:=]\s*[\'"]?.+[\'"]?#i',
            // Email addresses
            '#[\w\.-]+@[\w\.-]+\.\w+#',
        ];

        $sanitized = $message;
        foreach ($patterns as $pattern) {
            $sanitized = preg_replace($pattern, '[REDACTED]', $sanitized);
        }

        // If message was completely sanitized, return generic
        if (trim($sanitized) === '[REDACTED]' || empty(trim($sanitized))) {
            return 'An error occurred. Please contact support.';
        }

        // Limit message length
        if (strlen($sanitized) > 200) {
            $sanitized = substr($sanitized, 0, 197) . '...';
        }

        return $sanitized;
    }

}
