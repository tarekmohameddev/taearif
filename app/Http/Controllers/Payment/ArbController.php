<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Language;
use App\Models\Package;
use App\Models\Membership;
use App\Models\PaymentGateway;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use App\Models\Api\ApiInstallation;
use App\Models\Api\AppPaymentTransaction;
use App\Services\InstallationStateMachine;
use App\Services\MembershipService;
use App\Enums\InstallStatus;
use Illuminate\Support\Facades\DB;

class ArbController extends Controller
{
    protected array $data = [];
    public $arb;

    public function __construct()
    {
        $currentLang = session()->has('lang') ?
            (Language::where('code', session()->get('lang'))->first())
            : (Language::where('is_default', 1)->first());
        $be = $currentLang->basic_extended;

        $paymentMethod = PaymentGateway::where('keyword', 'arb')->first();
        $paydata = $paymentMethod->convertAutoData();
    }

    public function paymentProcess(Request $request, $_amount, $_success_url, $_cancel_url, $_title,$user_id,string $context = 'MEMBERSHIP', int $app_id  = 0)
    {
        /* ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        ~~~~~~~~~~~~~~~~~ Buy Plan Info ~~~~~~~~~~~~~~
        ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~*/
       // Session::put('request', $request->all());

        /* ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        ~~~~~~~~~~~~~~~~~ Payment Gateway Info ~~~~~~~~~~~~~~
        ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~*/
        $paymentMethod = PaymentGateway::where('keyword', 'arb')->first();
        $paydata = $paymentMethod->convertAutoData();


        $random_1 = rand(999, 9999);
        $random_2 = rand(9999, 99999);
        $paymentFor = 'membership';
        $name = $paymentFor == 'membership' ? $request->first_name . ' ' . $request->last_name : auth()->user()->first_name . ' ' . auth()->user()->first_name;

        $phone = $paymentFor == 'membership' ? $request->phone : auth()->user()->phone;

        // $package_id_request = (int)$request->package_id;
        $package_id_request = (int) ($request->package_id ?? 0);

       // $package = Package::find($package_id_request); // Replace 1 with your package ID
        // $package = Package::where('id', $package_id_request)->first();
        $package = $package_id_request ? Package::find($package_id_request) : null;
        // \Log::info('debug ' . $_amount);
        $price = $_amount;
        if ($package) {
            // Membership flow
            $price       = $_amount;
            $paymentFor  = 'membership';
            $name        = $name;
            $phone       = $phone;
        } else {
            // App-purchase flow
            $price       = (float) $_amount;          // ← use the param we passed in
            $paymentFor  = 'app';
            $name        = auth()->user()->name ?? 'Unknown';
            $phone       = auth()->user()->phone ?? '';
        }
        // if ($package) {

        // } else {
        //     return 'error';
        // }

        //#######################################################################
        $trackId = uniqid($price * time());
        $data = [
            'id'          => $paydata['tranportal_id'],
            'password'    => $paydata['tranportal_password'],
            'action'      => '1',
            'trackId'     => $trackId,
            'amt'         => (float) $price,
            'currencyCode'=> '682',
            'langid'      => 'ar',
            'responseURL' => $_success_url,
            'errorURL'    => $_cancel_url,
            'udf1'        => $package_id_request,
            'udf2'        => $user_id,
            'udf3'        => $context,
            'udf4'        => $app_id,
            'udf5'        => $request->period ?? 1, // Pass period information
        ];

       // $data = $data + $this->generateUdfs($data);
        // log::info($data);
        $data = $this->createRequestBody($this->wrapData($data), $_success_url, $_cancel_url);

        // log::info($data);

        $configName = 'bank_hosted_endpoint';

        $response = Http::withBody($data, 'application/json')
            ->withOptions(['verify' => false])
            ->post($paydata["mode"] == 'live'
            ? $paydata["live_$configName"]
            : $paydata["test_$configName"]
            );


        $response = $response->json('0');

        if ($response['status'] == '1') {
            [$paymentID, , $baseURL] = explode(':', $response['result']);
            $baseURL = 'https:'.$baseURL;
            $paymentID = '?PaymentID='.$paymentID;
            $return_object = array(
                'redirect_url'=> $baseURL.$paymentID,
                'payment_token' => $paymentID
            );

            return $return_object;
          //  return redirect($baseURL.$paymentID);
        } else {
            return 'error';
          //  return redirect(route('membership.arb.cancel'));
        }
    }

    public function paymentProcessForApp(\App\Models\User $user, \App\Models\Api\ApiApp $app): array
    {
        $dummyReq = new \Illuminate\Http\Request([
            'first_name' => $user->name,
            'last_name'  => '',
            'phone'      => $user->phone ?? '',
            'package_id' => 0,
        ]);

        return $this->paymentProcess(
            $dummyReq,
            $app->price,
            route('membership.arb.success', [], true),
            route('membership.arb.cancel',  [], true),
            "شراء تطبيق {$app->name}",
            $user->id,
            'APP',
            $app->id
        );

    }

    public function paymentProcessForCredits(\App\Models\User $user, float $amount, int $credits, string $paymentMethod = 'arb', ?int $transactionId = null): array
    {
        $dummyReq = new \Illuminate\Http\Request([
            'first_name' => $user->name,
            'last_name'  => '',
            'phone'      => $user->phone ?? '',
            'package_id' => 0,
        ]);

        // Use actual transaction id so success/cancel callbacks update the correct record
        $tid = $transactionId ?? ('TEMP_' . time());
        $successUrl = route('api.credits.payment.success', [
            'transaction_id' => $tid,
            'gateway' => $paymentMethod
        ]);
        $cancelUrl = route('api.credits.payment.cancel', [
            'transaction_id' => $tid,
            'gateway' => $paymentMethod
        ]);

        return $this->paymentProcess(
            $dummyReq,
            $amount,
            $successUrl,
            $cancelUrl,
            "شراء {$credits} رصيد",
            $user->id,
            'CREDITS',
            0 // No app_id for credits
        );
    }

    public function membershipPaymentSuccess(Request $request, string $gateway)
    {
        if ($gateway !== 'arb') {
            return $this->paymentIframeResponse(false, 'Unsupported payment gateway');
        }

        $paymentData = $this->decryptPaymentDataFromRequest($request);
        if ($paymentData === null) {
            Log::warning('ARB membership success callback: could not decrypt trandata', [
                'request_keys' => array_keys($request->all()),
            ]);

            return $this->paymentIframeResponse(false, 'Payment verification failed');
        }

        $context = $paymentData['udf3'] ?? 'MEMBERSHIP';
        if ($context === 'APP') {
            return $this->handleAppPayment($request, $paymentData);
        }

        return $this->handleMembershipPayment($request, $paymentData);
    }

    public function membershipPaymentFailed(Request $request, string $gateway)
    {
        if ($gateway !== 'arb') {
            return $this->paymentIframeResponse(false, 'Unsupported payment gateway');
        }

        $paymentData = $this->decryptPaymentDataFromRequest($request);
        if ($paymentData !== null) {
            $context = $paymentData['udf3'] ?? 'MEMBERSHIP';
            if ($context === 'APP') {
                return $this->handleAppPayment($request, $paymentData);
            }

            if ($this->membershipPaymentSucceeded($paymentData)) {
                return $this->handleMembershipPayment($request, $paymentData);
            }

            Log::info('ARB membership failed callback', [
                'result' => $paymentData['result'] ?? null,
                'error' => $paymentData['error'] ?? null,
                'user_id' => $paymentData['udf2'] ?? null,
                'package_id' => $paymentData['udf1'] ?? null,
            ]);
        }

        return $this->paymentIframeResponse(false, 'Payment was cancelled or failed');
    }

    public function failedPayment(Request $request)
    {
        return $this->membershipPaymentFailed($request, 'arb');
    }

    public function successPayment(Request $request)
    {
        return $this->membershipPaymentSuccess($request, 'arb');
    }

    protected function handleMembershipPayment(Request $request, array $paymentData)
    {
        try {
            if (!$this->membershipPaymentSucceeded($paymentData)) {
                Log::info('ARB membership payment failed or cancelled', [
                    'result' => $paymentData['result'] ?? 'unknown',
                    'error' => $paymentData['error'] ?? null,
                    'user_id' => $paymentData['udf2'] ?? null,
                    'package_id' => $paymentData['udf1'] ?? null,
                ]);

                return $this->paymentIframeResponse(false, 'Payment was cancelled or failed');
            }

            $packageId = (int) ($paymentData['udf1'] ?? 0);
            $userId = (int) ($paymentData['udf2'] ?? 0);
            $period = max(1, (int) ($paymentData['udf5'] ?? 1));
            $price = (float) ($paymentData['amt'] ?? 0);
            $transId = (string) ($paymentData['transId'] ?? '');

            if (!$packageId || !$userId || $transId === '') {
                Log::error('ARB membership payment missing required data', [
                    'package_id' => $packageId,
                    'user_id' => $userId,
                    'trans_id' => $transId,
                    'payment_data' => $paymentData,
                ]);

                return $this->paymentIframeResponse(false, 'Missing required payment data');
            }

            $existingMembership = Membership::where('transaction_id', $transId)->first();
            if ($existingMembership) {
                Log::info('Duplicate ARB membership payment callback (idempotent)', [
                    'transaction_id' => $transId,
                    'membership_id' => $existingMembership->id,
                ]);

                return $this->paymentIframeResponse(true, 'Payment already processed');
            }

            $user = User::find($userId);
            $package = Package::find($packageId);

            if (!$user || !$package) {
                Log::error('ARB membership payment user or package not found', [
                    'user_id' => $userId,
                    'package_id' => $packageId,
                ]);

                return $this->paymentIframeResponse(false, 'User or package not found');
            }

            $membershipService = app(MembershipService::class);
            $expectedAmount = $membershipService->calculateExpectedMembershipAmount($package, $period);

            if (abs($price - $expectedAmount) > 0.01) {
                Log::error('ARB membership payment amount mismatch', [
                    'expected' => $expectedAmount,
                    'received' => $price,
                    'user_id' => $userId,
                    'package_id' => $packageId,
                    'period' => $period,
                ]);

                return $this->paymentIframeResponse(false, 'Payment amount mismatch');
            }

            DB::transaction(function () use ($user, $package, $packageId, $period, $price, $transId, $paymentData, $membershipService) {
                $transactionDetails = json_encode([
                    'gateway' => 'ARB',
                    'timestamp' => now()->toDateTimeString(),
                    'user_id' => $user->id,
                    'package_id' => $packageId,
                    'period' => $period,
                    'decrypted_data' => $paymentData,
                ]);

                $membershipService->activateImmediateMembership($user, $package, [
                    'period' => $period,
                    'price' => $price > 0 ? $price : $membershipService->calculateExpectedMembershipAmount($package, $period),
                    'payment_method' => 'Arb',
                    'transaction_id' => $transId,
                    'transaction_details' => $transactionDetails,
                    'source' => 'arb',
                ]);

                $this->processAffiliateCommission($user, $package);
            });

            Log::info('ARB membership payment completed', [
                'user_id' => $userId,
                'package_id' => $packageId,
                'transaction_id' => $transId,
                'amount' => $price,
            ]);

            return $this->paymentIframeResponse(true, 'Payment processed successfully');
        } catch (\Exception $e) {
            Log::error('ARB membership payment handler error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'payment_data' => $paymentData,
            ]);

            return $this->paymentIframeResponse(false, 'Failed to process membership payment');
        }
    }

    protected function processAffiliateCommission(User $user, Package $package): void
    {
        if (!$user->referred_by) {
            return;
        }

        $affiliate = \App\Models\Api\ApiAffiliateUser::where('user_id', $user->referred_by)->first();
        if (!$affiliate) {
            return;
        }

        $validUntilToday = is_null($affiliate->to_date_value)
            || $affiliate->to_date_value->copy()->endOfDay()->gte(now());

        if ($validUntilToday) {
            $commissionRate = $affiliate->commission_percentage ?? 0.15;
            $commission = round($package->price * $commissionRate, 2);

            $affiliate->pending_amount += $commission;
            $affiliate->save();

            \App\Models\AffiliateTransaction::create([
                'affiliate_id' => $affiliate->id,
                'type' => 'pending',
                'referral_user_id' => $user->id,
                'image' => null,
                'amount' => $commission,
                'note' => "commission from username: ({$user->name}) for package: ({$package->title})",
            ]);
        }
    }

    protected function membershipPaymentSucceeded(array $paymentData): bool
    {
        return isset($paymentData['result'])
            && $paymentData['result'] === 'CAPTURED'
            && !isset($paymentData['error']);
    }

    protected function decryptPaymentDataFromRequest(Request $request): ?array
    {
        if (!$request->has('trandata')) {
            return null;
        }

        $paymentMethod = PaymentGateway::where('keyword', 'arb')->first();
        if (!$paymentMethod) {
            return null;
        }

        $paydata = $paymentMethod->convertAutoData();
        $decrypted = $this->decryption($request->input('trandata'), $paydata['resource_key']);
        if ($decrypted === false) {
            return null;
        }

        $dataArr = json_decode(urldecode($decrypted), true);
        if (empty($dataArr) || !is_array($dataArr)) {
            return null;
        }

        return $dataArr[0] ?? null;
    }

    protected function paymentIframeResponse(bool $success, string $message = '')
    {
        $postMessage = $success ? 'payment_success' : 'payment_failed';
        $safeMessage = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');

        $html = <<<HTML
<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <title>Payment Result</title>
</head>
<body>
    <p>{$safeMessage}</p>
    <script>
        window.parent.postMessage("{$postMessage}", "*");
    </script>
</body>
</html>
HTML;

        return response($html, 200)->header('Content-Type', 'text/html; charset=UTF-8');
    }

    protected function isApiRequest(Request $request): bool
    {
        return $request->expectsJson()
            || $request->wantsJson()
            || $request->is('api/*')
            || $request->header('Accept') === 'application/json';
    }

    /**
     * Handle app payment callback from ARB gateway
     *
     * @param Request $request
     * @param array $paymentData Decrypted payment data from ARB
     * @return \Illuminate\Http\RedirectResponse
     */
    protected function handleAppPayment(Request $request, array $paymentData)
    {
        $isApiRequest = $this->isApiRequest($request);

        try {
            // Validate payment was successful
            if (!isset($paymentData['result']) || $paymentData['result'] !== 'CAPTURED') {
                Log::info('ARB app payment failed or cancelled', [
                    'result' => $paymentData['result'] ?? 'unknown',
                    'payment_id' => $paymentData['transId'] ?? null,
                ]);

                if ($isApiRequest) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Payment failed or cancelled',
                        'result' => $paymentData['result'] ?? 'unknown',
                    ], 400);
                }

                return $this->paymentIframeResponse(false, 'Payment failed or cancelled');
            }

            // Extract payment information
            $paymentId = $paymentData['transId'] ?? $request->input('PaymentID');
            $appId = $paymentData['udf4'] ?? null;
            $userId = $paymentData['udf2'] ?? null;
            $paidAmount = $paymentData['amt'] ?? 0;

            // Validate required data exists
            if (!$paymentId || !$appId || !$userId) {
                Log::error('ARB app payment missing required data', [
                    'payment_id' => $paymentId,
                    'app_id' => $appId,
                    'user_id' => $userId,
                    'payment_data' => $paymentData,
                ]);

                if ($isApiRequest) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Missing required payment data',
                    ], 422);
                }

                return $this->paymentIframeResponse(false, 'Missing required payment data');
            }
            $existingTransaction = AppPaymentTransaction::where('payment_transaction_id', $paymentId)
                ->first();

            if ($existingTransaction && $existingTransaction->isCompleted()) {
                Log::info('Duplicate payment callback received (idempotent)', [
                    'payment_id' => $paymentId,
                    'transaction_id' => $existingTransaction->id,
                ]);

                if ($isApiRequest) {
                    return response()->json([
                        'status' => 'success',
                        'message' => 'Payment already processed',
                        'installation_id' => $existingTransaction->installation_id,
                        'transaction_id' => $existingTransaction->id,
                    ], 200);
                }

                return $this->paymentIframeResponse(true, 'App installed successfully');
            }

            // Find installation by invoice_id (which stores the PaymentID)
            $installation = ApiInstallation::where('invoice_id', $paymentId)
                ->where('user_id', $userId)
                ->where('app_id', $appId)
                ->with('app', 'user')
                ->first();

            if (!$installation) {
                Log::error('Installation not found for ARB app payment', [
                    'payment_id' => $paymentId,
                    'app_id' => $appId,
                    'user_id' => $userId,
                ]);

                if ($isApiRequest) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Installation not found',
                    ], 404);
                }

                return $this->paymentIframeResponse(false, 'Installation not found');
            }

            // Verify amount matches app price (prevent tampering)
            $expectedAmount = (float) $installation->app->price;
            $paidAmount = (float) $paidAmount;

            if (abs($paidAmount - $expectedAmount) > 0.01) {
                Log::warning('ARB app payment amount mismatch', [
                    'expected' => $expectedAmount,
                    'received' => $paidAmount,
                    'installation_id' => $installation->id,
                    'app_id' => $appId,
                    'user_id' => $userId,
                ]);

                if ($isApiRequest) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Payment amount mismatch',
                    ], 422);
                }

                return $this->paymentIframeResponse(false, 'Payment amount mismatch');
            }

            // Create or update transaction record
            $transaction = AppPaymentTransaction::updateOrCreate(
                ['payment_transaction_id' => $paymentId],
                [
                    'user_id' => $userId,
                    'installation_id' => $installation->id,
                    'app_id' => $appId,
                    'gateway' => 'arb',
                    'amount' => $paidAmount,
                    'currency' => 'SAR',
                    'status' => 'completed',
                    'gateway_response' => $paymentData,
                    'verified_at' => now(),
                    'metadata' => [
                        'payment_processed_at' => now()->toIso8601String(),
                        'recurring_id' => $paymentData['RecurringId'] ?? null,
                    ],
                ]
            );

            // Activate installation using state machine
            try {
                $stateMachine = app(InstallationStateMachine::class);
                $stateMachine->transition(
                    $installation,
                    InstallStatus::Installed,
                    [
                        'recurring_id' => $paymentData['RecurringId'] ?? null,
                        'payment_subscription_id' => $paymentData['RecurringId'] ?? null,
                    ]
                );

                Log::info('App installation activated via ARB payment', [
                    'installation_id' => $installation->id,
                    'user_id' => $userId,
                    'app_id' => $appId,
                    'app_name' => $installation->app->name,
                    'payment_id' => $paymentId,
                    'amount' => $paidAmount,
                    'transaction_id' => $transaction->id,
                ]);

                if ($isApiRequest) {
                    return response()->json([
                        'status' => 'success',
                        'message' => 'Payment processed successfully',
                        'installation_id' => $installation->id,
                        'transaction_id' => $transaction->id,
                        'app_name' => $installation->app->name,
                    ], 200);
                }

                return $this->paymentIframeResponse(true, 'App installed successfully');

            } catch (\App\Exceptions\Installation\InvalidStatusTransitionException $e) {
                Log::error('Invalid status transition for app installation', [
                    'installation_id' => $installation->id,
                    'current_status' => $installation->status->value,
                    'error' => $e->getMessage(),
                ]);

                // Mark transaction as failed
                $transaction->markFailed($paymentData, ['error' => $e->getMessage()]);

                if ($isApiRequest) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Invalid installation status transition',
                        'error' => $e->getMessage(),
                    ], 422);
                }

                return $this->paymentIframeResponse(false, 'Invalid installation status transition');
            } catch (\Exception $e) {
                Log::error('Failed to activate app installation via ARB payment', [
                    'installation_id' => $installation->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                // Mark transaction as failed
                $transaction->markFailed($paymentData, ['error' => $e->getMessage()]);

                if ($isApiRequest) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Failed to activate installation',
                        'error' => $e->getMessage(),
                    ], 500);
                }

                return $this->paymentIframeResponse(false, 'Failed to activate installation');
            }

        } catch (\Exception $e) {
            Log::error('Unexpected error in ARB app payment handler', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'payment_data' => $paymentData ?? [],
            ]);

            if ($isApiRequest) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Internal server error',
                ], 500);
            }

            return $this->paymentIframeResponse(false, 'Internal server error');
        }
    }

    public function decryption($code, $key): false|string
    {
        $string = hex2bin(trim($code));
        $code = unpack('C*', $string);
        $chars = array_map('chr', $code);
        $code = implode($chars);
        $code = base64_encode($code);
        $decrypted = openssl_decrypt($code, 'AES-256-CBC', $key, OPENSSL_ZERO_PADDING, 'PGKEYENCDECIVSPC');
        $pad = ord($decrypted[strlen($decrypted) - 1]);
        if ($pad > strlen($decrypted)) {
            return false;
        }
        if (strspn($decrypted, chr($pad), strlen($decrypted) - $pad) != $pad) {
            return false;
        }

        return urldecode(substr($decrypted, 0, -1 * $pad));
    }

    private function encryption(string $str, string $key): string
    {
        $blocksize = openssl_cipher_iv_length('AES-256-CBC');
        $pad = $blocksize - (strlen($str) % $blocksize);
        $str = $str.str_repeat(chr($pad), $pad);
        $encrypted = openssl_encrypt($str, 'AES-256-CBC', $key, OPENSSL_ZERO_PADDING, 'PGKEYENCDECIVSPC');
        $encrypted = base64_decode($encrypted);
        $encrypted = unpack('C*', ($encrypted));
        $chars = array_map('chr', $encrypted);
        $bin = implode($chars);
        $encrypted = bin2hex($bin);

        return urlencode($encrypted);
    }

    private function wrapData(array $data): string
    {
        $data = json_encode($data);

        return "[$data]";
    }

    /**
     * @param string $encoded_data JSON-wrapped payload (from wrapData)
     * @param string|null $responseURL When provided (e.g. credits/app flow), use this instead of membership success
     * @param string|null $errorURL When provided, use this instead of membership cancel
     */
    private function createRequestBody($encoded_data, ?string $responseURL = null, ?string $errorURL = null): string
    {
        $paymentMethod = PaymentGateway::where('keyword', 'arb')->first();
        $paydata = $paymentMethod->convertAutoData();

        $encryptedData = [
            'id' => $paydata['tranportal_id'],
            'trandata' => $this->encryption($encoded_data, $paydata['resource_key']),
            'responseURL' => $responseURL ?? route('api.membership.payment.success', ['gateway' => 'arb']),
            'errorURL' => $errorURL ?? route('api.membership.payment.failed', ['gateway' => 'arb']),
        ];

        return $this->wrapData($encryptedData);
    }
    public function generateUdfs($data)
    {
        $maxChar = 255;
        $maxudfs = 5;
        $str = base64_encode(json_encode($data));
        // split the string into chunks of 255 characters
        $chunks = str_split($str, $maxChar);
        if (count($chunks) > $maxudfs) {
            throw new \Exception('Data is too large to be sent');
        }

        $udfs = [];
        foreach ($chunks as $key => $chunk) {
            $udfs["udf".($key + 1)] = $chunk;
        }
        return $udfs;
    }

    public function handlePaymentRequest(string $data): object
    {
        // payment request handling
        return (object) [];
    }

}
