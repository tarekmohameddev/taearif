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
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;

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

        // ── JSON for API calls ──
        if ($request->expectsJson() || $request->is('api/*')) {
            // Validation (you also have invalidJson; this is just a safeguard)
            if ($exception instanceof ValidationException) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Validation failed',
                    'errors'  => $exception->errors(),
                ], 422);
            }

            // Not found (model/route)
            if ($exception instanceof ModelNotFoundException) {
                return response()->json(['status' => 'error', 'message' => 'Resource not found'], 404);
            }
            if ($exception instanceof NotFoundHttpException || $exception instanceof RouteNotFoundException) {
                return response()->json(['status' => 'error', 'message' => 'Not found'], 404);
            }

            // Auth
            if ($exception instanceof AuthenticationException) {
                return response()->json(['status' => 'error', 'message' => 'Unauthenticated'], 401);
            }

            // DB
            if ($exception instanceof QueryException) {
                return response()->json([
                    'status'    => 'error',
                    'message'   => 'Database error',
                    'sql_error' => config('app.debug') ? $exception->getMessage() : null,
                ], 500);
            }

            // Fallback - Log the error for debugging
            \Log::error('Unhandled API exception', [
                'exception' => get_class($exception),
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'user_id' => auth()->id(),
            ]);

            // Always show the actual error message for better debugging
            // Use a generic message only if exception message is empty
            $errorMessage = $exception->getMessage() ?: 'An unexpected error occurred';

            return response()->json([
                'status'    => 'error',
                'message'   => $errorMessage,
                'exception' => config('app.debug') ? class_basename($exception) : null,
            ], 500);
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
            'message' => 'Validation failed',
            'errors'  => $exception->errors(),
        ], $exception->status);
    }

}
