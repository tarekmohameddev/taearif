<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Models\User\BasicSetting;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Theme\CancelThemePaymentRequest;
use App\Http\Requests\Api\Theme\PurchaseThemeRequest;
use App\Http\Requests\Api\Theme\SetActiveThemeRequest;
use App\Http\Requests\Api\Theme\ThemePaymentSuccessRequest;
use App\Models\Api\ApiThemeSettings;
use App\Models\UserTheme;
use App\Models\Language;
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
    public function setActiveTheme(SetActiveThemeRequest $request)
    {
        try {
            $validated = $request->validated();
            $user = Auth::user();

            if (!$user) {
                return $this->errorResponse('Unauthorized', 401);
            }

            $themeId = $validated['theme_id'];
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
                'theme_id' => request()->input('theme_id'),
            ]);
            return $this->errorResponse('An error occurred while activating the theme', 500);
        }
    }

    /**
     * Initiate theme purchase
     * Includes transaction handling and standardized error responses
     */
    public function purchase(PurchaseThemeRequest $request)
    {
        try {
            $validated = $request->validated();
            $user = auth()->user();

            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthorized access',
                    'payment_url' => null,
                    'payment_token' => null
                ], 401);
            }

            $canPurchase = $this->themeService->canPurchaseTheme($user, $validated['theme_id']);

            if (!$canPurchase['can_purchase']) {
                return response()->json([
                    'status' => 'error',
                    'message' => $canPurchase['reason'],
                    'payment_url' => null,
                    'payment_token' => null
                ], 400);
            }

            // Create pending purchase (wrapped in transaction in service)
            $userTheme = $this->themeService->createPendingPurchase($user, $validated['theme_id']);

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
                'user_id' => auth()->id(),
                'theme_id' => $validated['theme_id'] ?? null,
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
     * Payment success callback - activate theme purchase.
     */
    public function paymentSuccess(ThemePaymentSuccessRequest $request, $user_theme_id, $gateway)
    {
        try {
            $userTheme = UserTheme::with('theme')->findOrFail($user_theme_id);
            if ($userTheme->status === UserTheme::STATUS_ACTIVE) {
                return $this->finalizeRedirect(true, 'Already activated');
            }

            if ($userTheme->status !== UserTheme::STATUS_PENDING) {
                return $this->finalizeRedirect(false, 'Invalid purchase status');
            }

            // Verify payment based on gateway
            $verified = false;
            $transactionId = null;

            if ($gateway === 'test') {
                if (config('app.env') === 'local') {
                    $verified = true;
                    $transactionId = 'TEST_' . time();
                }
            } elseif ($gateway === 'myfatoorah') {
                $paymentId = request()->input('paymentId');
                if ($paymentId) {
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
                    'query' => request()->query(),
                    'all' => request()->all(),
                ]);

                $paymentMethod = \App\Models\PaymentGateway::where('keyword', 'arb')->first();
                if (request()->has('trandata') && $paymentMethod) {
                    $paydata = $paymentMethod->convertAutoData();
                    $arb = app(ArbController::class);
                    $decrypted = $arb->decryption((string) request()->input('trandata'), $paydata['resource_key']);
                    
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

                    return $this->finalizeRedirect(true, 'تم الدفع بنجاح');
                } catch (\App\Exceptions\BusinessLogicException $e) {
                    Log::warning("Theme Purchase Activation Failed: " . $e->getMessage(), [
                        'user_theme_id' => $userTheme->id,
                        'user_id' => $userTheme->user_id,
                    ]);
                    return $this->finalizeRedirect(false, $e->getMessage());
                }
            }

            return $this->finalizeRedirect(false, 'فشل التحقق من الدفع');

        } catch (\Exception $e) {
            Log::error("Theme Payment Success Error: " . $e->getMessage());
            return $this->finalizeRedirect(false, "خطأ في النظام");
        }
    }

    /**
     * Payment cancel callback.
     */
    public function paymentCancel(CancelThemePaymentRequest $request, $user_theme_id, $gateway)
    {
        $userTheme = UserTheme::find($user_theme_id);
        if ($userTheme && $userTheme->status === UserTheme::STATUS_PENDING) {
            $userTheme->update(['status' => UserTheme::STATUS_REJECTED]);
        }
        return $this->finalizeRedirect(false, 'تم إلغاء الدفع');
    }

    /**
     * Finalize payment redirect - return HTML view
     * Matches EmployeeAddonController implementation
     */
    private function finalizeRedirect($success, $message)
    {
        // Get language and basic settings for the views
        $currentLang = Language::where('is_default', 1)->first();
        $bs = $currentLang ? $currentLang->basic_setting : \App\Models\BasicSetting::first();
        
        if (!$success) {
            return view('front.failed', [
                'bs' => $bs,
                'rtl' => $bs->rtl ?? 0
            ]);
        }

        // Return success page that notifies parent window (React/Next.js frontend)
        // The view will send postMessage("payment_success") to notify the frontend
        return view('front.success', [
            'bs' => $bs,
            'rtl' => $bs->rtl ?? 0
        ]);
    }

}
