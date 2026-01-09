<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Models\User\BasicSetting;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Models\Api\ApiThemeSettings;
use App\Models\UserTheme;
use App\Domain\Themes\Services\ThemeService;
use App\Http\Controllers\Payment\ArbController;
use App\Http\Controllers\Payment\MyFatoorahController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ThemeSettingsController extends Controller
{
    protected ThemeService $themeService;

    public function __construct(ThemeService $themeService)
    {
        $this->themeService = $themeService;
    }

    /**
     * Standardized success response
     */
    private function successResponse($data = null, string $message = 'Success', int $statusCode = 200)
    {
        $response = [
            'success' => true,
            'message' => $message,
        ];

        if ($data !== null) {
            $response['data'] = $data;
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Standardized error response
     */
    private function errorResponse(string $message, int $statusCode = 400, array $errors = [])
    {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if (!empty($errors)) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $statusCode);
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

    public function index(Request $request)
    {
        $user = $request->user();
        $activeTheme = $user->userbasicsettings->first()?->theme ?? 'modern';
        
        // Ensure basic setting exists
        $basicSetting = $user->userbasicsettings()->first();
        if (!$basicSetting) {
            $user->userbasicsettings()->create(['theme' => $activeTheme]);
        } elseif (!$basicSetting->theme) {
            $basicSetting->theme = $activeTheme;
            $basicSetting->save();
        }

        $filters = [
            'category' => $request->input('category', 'all'),
        ];

        $themes = $this->themeService->getAllThemes($user, $filters);

        $categories = ApiThemeSettings::select('category')
            ->whereNotNull('category')
            ->distinct()
            ->pluck('category')
            ->map(function ($category) {
                return ['id' => $category, 'name' => $category];
            })
            ->prepend(['id' => 'all', 'name' => 'جميع السمات']);

        return response()->json([
            'activeTheme' => $activeTheme,
            'themes' => $themes,
            'categories' => $categories,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    /**
     * Set active theme for the authenticated user
     * Checks if user has access (free or purchased) before activating
     */
    public function setActiveTheme(Request $request)
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return $this->errorResponse('Unauthorized', 401);
            }

            $request->validate([
                'theme_id' => 'required|exists:api_themes_settings,theme_id',
            ]);

            $themeId = $request->theme_id;
            $theme = ApiThemeSettings::where('theme_id', $themeId)->firstOrFail();

            // Check if theme is enabled
            if (!$theme->isEnabled()) {
                return $this->errorResponse('Theme is not available', 403);
            }

            // Check if user has access (free or purchased)
            if (!$theme->userHasAccess($user->id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have access to this theme. Please purchase it first.',
                    'requires_purchase' => true,
                    'theme_id' => $themeId,
                    'price' => $theme->price,
                    'currency' => $theme->currency,
                ], 403);
            }

            // Update user's active theme
            DB::transaction(function () use ($user, $themeId) {
                $basicSetting = BasicSetting::firstOrCreate(
                    ['user_id' => $user->id],
                    ['theme' => $themeId]
                );

                if ($basicSetting->theme !== $themeId) {
                    $basicSetting->theme = $themeId;
                    $basicSetting->save();
                }
            });

            return $this->successResponse([
                'id' => $theme->theme_id,
                'name' => $theme->name,
                'description' => $theme->description,
                'thumbnail' => asset($theme->thumbnail),
                'category' => $theme->category,
                'is_free' => $theme->isFree(),
                'has_access' => true,
            ], 'Theme activated successfully');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Theme not found', 404);
        } catch (\Exception $e) {
            Log::error("Set Active Theme Error: " . $e->getMessage(), [
                'user_id' => Auth::id(),
                'theme_id' => $request->input('theme_id'),
            ]);
            return $this->errorResponse('An error occurred while activating the theme', 500);
        }
    }

    /**
     * Initiate theme purchase
     * Includes transaction handling and standardized error responses
     */
    public function purchase(Request $request)
    {
        try {
            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthorized access',
                    'payment_url' => null,
                    'payment_token' => null
                ], 401);
            }

            $request->validate([
                'theme_id' => 'required|exists:api_themes_settings,theme_id',
        ]);

            $canPurchase = $this->themeService->canPurchaseTheme($user, $request->theme_id);

            if (!$canPurchase['can_purchase']) {
                return response()->json([
                    'status' => 'error',
                    'message' => $canPurchase['reason'],
                    'payment_url' => null,
                    'payment_token' => null
                ], 400);
            }

            // Create pending purchase (wrapped in transaction in service)
            $userTheme = $this->themeService->createPendingPurchase($user, $request->theme_id);

            // Initiate payment (similar to WhatsApp addon)
            $paymentResult = $this->initiatePayment($userTheme, $user);

            if ($paymentResult['success']) {
                return response()->json([
                    'status' => 'success',
                    'payment_url' => $paymentResult['redirect_url'],
                    'payment_token' => $paymentResult['payment_token'] ?? null,
                    'user_theme_id' => $userTheme->id,
                    'amount' => $userTheme->amount_paid,
                    'currency' => $userTheme->currency,
                ], 200);
            }

            // Payment init failed - update status in transaction
            DB::transaction(function () use ($userTheme) {
                $userTheme->update(['status' => UserTheme::STATUS_REJECTED]);
            });

            return response()->json([
                'status' => 'error',
                'message' => 'Payment initialization failed: ' . ($paymentResult['error'] ?? 'Unknown error'),
                'payment_url' => null,
                'payment_token' => null
            ], 422);
        } catch (\App\Exceptions\BusinessLogicException $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
                'payment_url' => null,
                'payment_token' => null
            ], 400);
        } catch (\App\Exceptions\ResourceNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
                'payment_url' => null,
                'payment_token' => null
            ], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->errors(),
                'payment_url' => null,
                'payment_token' => null
            ], 422);
        } catch (\Exception $e) {
            Log::error("Theme Purchase Error: " . $e->getMessage(), [
                'user_id' => $request->user()?->id,
                'theme_id' => $request->input('theme_id'),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while processing your request',
                'payment_url' => null,
                'payment_token' => null
            ], 500);
        }
    }

    /**
     * Initiate payment for theme purchase
     * Uses ARB payment gateway (same as api/make-payment)
     */
    private function initiatePayment(UserTheme $userTheme, $user)
    {
        try {
            $amount = $userTheme->amount_paid;
            $title = "Theme Purchase: {$userTheme->theme->name}";
            
            // Generate callback URLs (use absolute URLs like make-payment does)
            $successUrl = route('api.themes.payment.success', [
                'user_theme_id' => $userTheme->id,
                'gateway' => 'arb'
            ], true);
            $cancelUrl = route('api.themes.payment.cancel', [
                'user_theme_id' => $userTheme->id,
                'gateway' => 'arb'
            ], true);

            // Always use ARB payment gateway (same as api/make-payment)
            $arb = app(ArbController::class);
            
            // Prepare request with user details (same format as make-payment)
            $dummyReq = new Request([
                'first_name' => $user->first_name ?? $user->fname ?? explode(' ', $user->name ?? 'User')[0] ?? 'User',
                'last_name' => $user->last_name ?? $user->lname ?? (count(explode(' ', $user->name ?? '')) > 1 ? explode(' ', $user->name, 2)[1] : ''),
                'phone' => $user->phone ?? '',
                'package_id' => 0,
            ]);

            Log::info('Initiating ARB payment for theme purchase', [
                'user_id' => $user->id,
                'user_theme_id' => $userTheme->id,
                'theme_id' => $userTheme->theme_id,
                'amount' => $amount,
                'success_url' => $successUrl,
                'cancel_url' => $cancelUrl,
            ]);

            $result = $arb->paymentProcess(
                $dummyReq,
                $amount,
                $successUrl,
                $cancelUrl,
                $title,
                $user->id,
                'THEME_PURCHASE',
                0
            );

            // Check for error first (paymentProcess returns 'error' string on failure)
            if ($result === 'error') {
                Log::error('ARB Payment Process returned error for theme purchase', [
                    'user_id' => $user->id,
                    'user_theme_id' => $userTheme->id,
                    'amount' => $amount,
                    'theme_id' => $userTheme->theme_id,
                ]);
                return ['success' => false, 'error' => 'ARB payment gateway returned error'];
            }

            // Check if result has redirect_url (success case)
            if (isset($result['redirect_url']) && !empty($result['redirect_url'])) {
                Log::info('ARB payment initiated successfully for theme purchase', [
                    'user_id' => $user->id,
                    'user_theme_id' => $userTheme->id,
                    'payment_url' => $result['redirect_url'],
                ]);
                return [
                    'success' => true,
                    'redirect_url' => $result['redirect_url'],
                    'payment_token' => $result['payment_token'] ?? null,
                ];
            }

            Log::error('ARB Payment Process returned invalid result for theme purchase', [
                'user_id' => $user->id,
                'user_theme_id' => $userTheme->id,
                'result' => $result,
                'result_type' => gettype($result),
            ]);
            return ['success' => false, 'error' => 'ARB payment gateway returned invalid response'];
        } catch (\Exception $e) {
            Log::error("Theme Payment Init Error: " . $e->getMessage(), [
                'user_id' => $user->id ?? null,
                'user_theme_id' => $userTheme->id ?? null,
                'trace' => $e->getTraceAsString(),
            ]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Payment success callback
     * Returns JSON response when Accept: application/json header is present (for API calls)
     * Otherwise redirects to frontend (for browser redirects from payment gateway)
     */
    public function paymentSuccess(Request $request, $user_theme_id, $gateway)
    {
        try {
            $userTheme = UserTheme::with('theme')->findOrFail($user_theme_id);
            $wantsJson = $request->wantsJson() || $request->expectsJson() || ($request->has('format') && $request->get('format') === 'json');

            // Check if already activated
            if ($userTheme->status === UserTheme::STATUS_ACTIVE) {
                if ($wantsJson) {
                    return response()->json([
                        'status' => 'success',
                        'message' => 'Theme already activated',
                        'theme_id' => $userTheme->theme_id,
                        'user_theme_id' => $userTheme->id,
                    ], 200);
                }
                return $this->redirectToFrontend('success', 'Theme already activated');
            }

            if ($userTheme->status !== UserTheme::STATUS_PENDING) {
                if ($wantsJson) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Invalid purchase status'
                    ], 400);
                }
                return $this->redirectToFrontend('error', 'Invalid purchase status');
            }

            // Verify payment based on gateway
            $verified = false;
            $transactionId = null;

            if ($gateway === 'test') {
                // Secure 'test' gateway: only allow in local environment
                if (config('app.env') === 'local') {
                    $verified = true;
                    $transactionId = 'TEST_' . time();
                } else {
                    if ($wantsJson) {
                        return response()->json([
                            'status' => 'error',
                            'message' => 'Test gateway only available in local environment'
                        ], 403);
                    }
                    return $this->redirectToFrontend('error', 'Test gateway only available in local environment');
                }
            } elseif ($gateway === 'myfatoorah') {
                $paymentId = $request->paymentId;
                if ($paymentId) {
                    // Verify with MyFatoorah API
                    try {
                        $paymentMethod = \App\Models\PaymentGateway::where('keyword', 'myfatoorah')->first();
                        if ($paymentMethod) {
                            $paydata = $paymentMethod->convertAutoData();
                            Config::set('myfatorah.token', $paydata['token']);
                            
                            $myfatoorah = \Basel\MyFatoorah\MyFatoorah::getInstance($paydata['sandbox_status'] == 1);
                            $result = $myfatoorah->getPaymentStatus('paymentId', $paymentId);

                            if ($result && $result['IsSuccess'] == true && $result['Data']['InvoiceStatus'] == "Paid") {
                                $verified = true;
                                $transactionId = $paymentId;
                            }
                        }
                    } catch (\Exception $e) {
                        Log::error('MyFatoorah Verification Error: ' . $e->getMessage());
                    }
                }
            } elseif ($gateway === 'arb') {
                // Log ARB callback for debugging
                Log::info('ARB Theme Payment callback', [
                    'user_theme_id' => $user_theme_id,
                    'query' => $request->query(),
                    'all' => $request->all(),
                ]);

                $paymentMethod = \App\Models\PaymentGateway::where('keyword', 'arb')->first();
                if ($request->has('trandata') && $paymentMethod) {
                    $paydata = $paymentMethod->convertAutoData();
                    $arb = app(ArbController::class);
                    $decrypted = $arb->decryption($request->trandata, $paydata['resource_key']);
                    
                    if ($decrypted) {
                        $raw = urldecode($decrypted);
                        $dataArr = json_decode($raw, true);
                        
                        if (!empty($dataArr) && is_array($dataArr)) {
                            $paymentData = $dataArr[0];
                            if (isset($paymentData['result']) && $paymentData['result'] === 'CAPTURED') {
                                $verified = true;
                                $transactionId = $paymentData['transId'] ?? null;
                            }
                        }
                    }
                }
            }

            if ($verified) {
                // Activate purchase (wrapped in transaction in service)
                try {
                    $this->themeService->activatePurchase(
                        $userTheme->id,
                        $transactionId ?? 'N/A',
                        $gateway
                    );

                    // Refresh the model to get updated data
                    $userTheme->refresh();

                    if ($wantsJson) {
                        return response()->json([
                            'status' => 'success',
                            'message' => 'Theme purchased successfully',
                            'theme_id' => $userTheme->theme_id,
                            'theme_name' => $userTheme->theme->name ?? null,
                            'user_theme_id' => $userTheme->id,
                            'transaction_id' => $transactionId ?? 'N/A',
                            'amount_paid' => $userTheme->amount_paid,
                            'currency' => $userTheme->currency,
                        ], 200);
                    }

                    return $this->redirectToFrontend('success', 'Theme purchased successfully', [
                        'theme_id' => $userTheme->theme_id
                    ]);
                } catch (\App\Exceptions\BusinessLogicException $e) {
                    Log::warning("Theme Purchase Activation Failed: " . $e->getMessage(), [
                        'user_theme_id' => $userTheme->id,
                        'user_id' => $userTheme->user_id,
                    ]);
                    
                    if ($wantsJson) {
                        return response()->json([
                            'status' => 'error',
                            'message' => $e->getMessage()
                        ], 400);
                    }
                    return $this->redirectToFrontend('error', $e->getMessage());
                }
            }

            Log::warning('Theme Payment Verification Failed', [
                'user_theme_id' => $userTheme->id,
                'gateway' => $gateway,
                'request_data' => $request->all(),
            ]);

            if ($wantsJson) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Payment verification failed'
                ], 400);
            }

            return $this->redirectToFrontend('error', 'Payment verification failed');
        } catch (ModelNotFoundException $e) {
            Log::error("Theme Payment Success: Purchase not found", [
                'user_theme_id' => $user_theme_id,
            ]);
            
            if ($request->wantsJson() || $request->expectsJson() || ($request->has('format') && $request->get('format') === 'json')) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Purchase record not found'
                ], 404);
            }
            return $this->redirectToFrontend('error', 'Purchase record not found');
        } catch (\Exception $e) {
            Log::error("Theme Payment Success Error: " . $e->getMessage(), [
                'user_theme_id' => $user_theme_id,
                'gateway' => $gateway,
                'trace' => $e->getTraceAsString(),
            ]);
            
            if ($request->wantsJson() || $request->expectsJson() || ($request->has('format') && $request->get('format') === 'json')) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'System error occurred'
                ], 500);
            }
            return $this->redirectToFrontend('error', 'System error occurred');
        }
    }

    /**
     * Payment cancel callback
     * Returns JSON response when Accept: application/json header is present (for API calls)
     * Otherwise redirects to frontend (for browser redirects from payment gateway)
     */
    public function paymentCancel(Request $request, $user_theme_id, $gateway)
    {
        try {
            $userTheme = UserTheme::findOrFail($user_theme_id);
            $wantsJson = $request->wantsJson() || $request->expectsJson() || ($request->has('format') && $request->get('format') === 'json');
            
            // Ownership verification: The purchase record itself serves as verification
            // Payment gateway will only redirect to this URL for the correct purchase
            
            DB::transaction(function () use ($userTheme) {
                if ($userTheme->status === UserTheme::STATUS_PENDING) {
                    $userTheme->update(['status' => UserTheme::STATUS_REJECTED]);
                    
                    Log::info('Theme purchase cancelled', [
                        'user_theme_id' => $userTheme->id,
                        'user_id' => $userTheme->user_id,
                        'theme_id' => $userTheme->theme_id,
                    ]);
                }
            });

            if ($wantsJson) {
                return response()->json([
                    'status' => 'cancelled',
                    'message' => 'Payment was cancelled',
                    'theme_id' => $userTheme->theme_id,
                    'user_theme_id' => $userTheme->id,
                ], 200);
            }

            return $this->redirectToFrontend('cancelled', 'Payment was cancelled');
        } catch (ModelNotFoundException $e) {
            Log::error("Theme Payment Cancel: Purchase not found", [
                'user_theme_id' => $user_theme_id,
            ]);
            
            if ($request->wantsJson() || $request->expectsJson() || ($request->has('format') && $request->get('format') === 'json')) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Purchase record not found'
                ], 404);
            }
            return $this->redirectToFrontend('error', 'Purchase record not found');
        } catch (\Exception $e) {
            Log::error("Theme Payment Cancel Error: " . $e->getMessage(), [
                'user_theme_id' => $user_theme_id,
                'gateway' => $gateway,
                'trace' => $e->getTraceAsString(),
            ]);
            
            if ($request->wantsJson() || $request->expectsJson() || ($request->has('format') && $request->get('format') === 'json')) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'System error occurred'
                ], 500);
            }
            return $this->redirectToFrontend('error', 'System error occurred');
        }
    }

    /**
     * Helper method to redirect to frontend with consistent URL format
     * Uses FRONTEND_URL from .env file
     */
    private function redirectToFrontend(string $status, string $message, array $additionalParams = []): \Illuminate\Http\RedirectResponse
    {
        $frontendUrl = env('FRONTEND_URL', 'http://localhost:3000');
        $params = array_merge([
            'status' => $status,
            'message' => $message,
        ], $additionalParams);

        $queryString = http_build_query($params);
        return redirect($frontendUrl . '/themes?' . $queryString);
    }

}
