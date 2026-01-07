<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use App\Models\Api\ApiInstallation;
use App\Enums\InstallStatus;
use App\Services\InstallationStateMachine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;

/**
 * Payment Webhook Controller
 *
 * Handles payment webhooks from MyFatoorah and ARB for app installations
 * Includes security measures: rate limiting, validation, and payment verification
 */
class MyFatoorahWebhookController extends Controller
{
    protected InstallationStateMachine $stateMachine;
    protected ?string $gatewayType = null; // 'myfatoorah' or 'arb'

    public function __construct(InstallationStateMachine $stateMachine)
    {
        $this->stateMachine = $stateMachine;
    }

    /**
     * Handle webhook callback from payment gateways (MyFatoorah or ARB)
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function handle(Request $request)
    {
        // Detect gateway type
        $this->gatewayType = $request->has('trandata') ? 'arb' : 'myfatoorah';

        // Rate limiting: max 10 requests per minute per IP
        $key = 'webhook:payment:' . $this->gatewayType . ':' . $request->ip();
        if (RateLimiter::tooManyAttempts($key, 10)) {
            Log::warning('Webhook rate limit exceeded', [
                'gateway' => $this->gatewayType,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Too many requests',
            ], 429);
        }

        RateLimiter::hit($key, 60);

        try {
            // Extract and validate payload
            $payload = $this->extractPayload($request);

            if (empty($payload)) {
                Log::warning('Empty payload received', [
                    'gateway' => $this->gatewayType,
                    'request_data' => $request->all(),
                    'ip' => $request->ip(),
                ]);

                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid payload format',
                ], 422);
            }

            // Validate payload structure (flexible for both gateways)
            $validator = Validator::make($payload, [
                'PaymentId' => 'required|string',
                'result' => 'required|string',
                'udf3' => 'required|string',
                'udf4' => 'nullable|integer', // app_id
            ]);

            if ($validator->fails()) {
                Log::warning('Invalid webhook payload', [
                    'gateway' => $this->gatewayType,
                    'payload' => $payload,
                    'errors' => $validator->errors()->toArray(),
                    'ip' => $request->ip(),
                    'raw_request' => $request->all(),
                ]);

                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid payload',
                    'details' => $validator->errors()->toArray(),
                ], 422);
            }

            // Only process APP context webhooks
            if ($payload['udf3'] !== 'APP') {
                Log::debug('Webhook ignored - not APP context', [
                    'gateway' => $this->gatewayType,
                    'udf3' => $payload['udf3'],
                ]);

                return response()->json(['ignored' => true, 'reason' => 'not_app_context']);
            }

            // Find installation by invoice ID
            $installation = ApiInstallation::where('invoice_id', $payload['PaymentId'])
                ->with('app', 'user')
                ->first();

            if (!$installation) {
                Log::warning('Installation not found for webhook', [
                    'gateway' => $this->gatewayType,
                    'payment_id' => $payload['PaymentId'],
                    'ip' => $request->ip(),
                ]);

                return response()->json([
                    'status' => 'error',
                    'message' => 'Installation not found',
                ], 404);
            }

            // Verify payment (gateway-specific)
            if (!$this->verifyPayment($payload['PaymentId'], $installation)) {
                Log::error('Payment verification failed', [
                    'gateway' => $this->gatewayType,
                    'payment_id' => $payload['PaymentId'],
                    'installation_id' => $installation->id,
                ]);

                return response()->json([
                    'status' => 'error',
                    'message' => 'Payment verification failed',
                ], 422);
            }

            // Process payment result
            return $this->processPaymentResult($installation, $payload);

        } catch (\Exception $e) {
            Log::error('Webhook processing error', [
                'gateway' => $this->gatewayType,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Webhook processing failed',
            ], 500);
        }
    }

    /**
     * Extract payload from request
     * Handles both MyFatoorah and ARB payment formats
     *
     * @param Request $request
     * @return array
     */
    protected function extractPayload(Request $request): array
    {
        // Check if this is ARB payment (has trandata)
        if ($request->has('trandata')) {
            return $this->extractArbPayload($request);
        }

        // MyFatoorah format
        return $this->extractMyFatoorahPayload($request);
    }

    /**
     * Extract and decrypt ARB payment payload
     *
     * @param Request $request
     * @return array
     */
    protected function extractArbPayload(Request $request): array
    {
        try {
            $paymentMethod = \App\Models\PaymentGateway::where('keyword', 'arb')->first();

            if (!$paymentMethod) {
                Log::error('ARB payment gateway not configured');
                return [];
            }

            $paydata = $paymentMethod->convertAutoData();
            $arbController = app(\App\Http\Controllers\Payment\ArbController::class);

            // Decrypt trandata
            $decrypted = $arbController->decryption(
                $request->trandata,
                $paydata['resource_key']
            );

            if (!$decrypted) {
                Log::error('Failed to decrypt ARB trandata', [
                    'paymentid' => $request->input('paymentid'),
                ]);
                return [];
            }

            $raw = urldecode($decrypted);
            $dataArr = json_decode($raw, true);

            if (empty($dataArr) || !is_array($dataArr)) {
                Log::error('Invalid ARB callback data format', [
                    'decrypted' => $decrypted,
                    'paymentid' => $request->input('paymentid'),
                ]);
                return [];
            }

            $paymentData = $dataArr[0]; // ARB wraps in array

            // Map ARB format to expected format
            $result = $paymentData['result'] ?? null;
            $paymentId = $request->input('paymentid') ?? $paymentData['transId'] ?? null;

            if (!$paymentId) {
                Log::error('ARB payload missing payment ID', [
                    'payment_data' => $paymentData,
                    'request' => $request->all(),
                ]);
                return [];
            }

            return [
                'PaymentId' => $paymentId,
                'result' => $result,
                'udf3' => $paymentData['udf3'] ?? null,
                'udf4' => $paymentData['udf4'] ?? null,
                'amt' => $paymentData['amt'] ?? null,
                'transId' => $paymentData['transId'] ?? null,
                'RecurringId' => $paymentData['RecurringId'] ?? null,
                // Include original ARB data for reference
                '_arb_data' => $paymentData,
                '_gateway' => 'arb',
            ];

        } catch (\Exception $e) {
            Log::error('ARB payload extraction error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'paymentid' => $request->input('paymentid'),
            ]);

            return [];
        }
    }

    /**
     * Extract MyFatoorah payment payload
     *
     * @param Request $request
     * @return array
     */
    protected function extractMyFatoorahPayload(Request $request): array
    {
        // MyFatoorah sends data in different formats
        // Try array format first (common for webhooks)
        $payload = $request->all();

        // If it's wrapped in array, extract first element
        if (isset($payload[0]) && is_array($payload[0])) {
            $payload[0]['_gateway'] = 'myfatoorah';
            return $payload[0];
        }

        // If it's direct array, use it
        if (is_array($payload) && !empty($payload)) {
            $payload['_gateway'] = 'myfatoorah';
            return $payload;
        }

        // Fallback: try JSON body
        $json = json_decode($request->getContent(), true);
        if (is_array($json)) {
            $result = $json[0] ?? $json;
            $result['_gateway'] = 'myfatoorah';
            return $result;
        }

        return [];
    }

    /**
     * Verify payment with payment gateway API
     * Supports both MyFatoorah and ARB
     *
     * @param string $paymentId
     * @param ApiInstallation $installation
     * @return bool
     */
    protected function verifyPayment(string $paymentId, ApiInstallation $installation): bool
    {
        if ($this->gatewayType === 'arb') {
            return $this->verifyArbPayment($paymentId, $installation);
        }

        return $this->verifyMyFatoorahPayment($paymentId, $installation);
    }

    /**
     * Verify ARB payment
     * For ARB, we trust the decrypted trandata since it's cryptographically signed
     * Additional verification can be done by checking the payment status with ARB API if needed
     *
     * @param string $paymentId
     * @param ApiInstallation $installation
     * @return bool
     */
    protected function verifyArbPayment(string $paymentId, ApiInstallation $installation): bool
    {
        // ARB trandata is already decrypted and verified via signature
        // We just need to verify the payment ID matches and amount is correct
        // The amount verification happens in processPaymentResult
        
        // Verify payment ID matches installation
        if ($installation->invoice_id !== $paymentId) {
            Log::warning('ARB payment ID mismatch', [
                'expected' => $installation->invoice_id,
                'received' => $paymentId,
            ]);
            return false;
        }

        return true;
    }

    /**
     * Verify MyFatoorah payment with API
     *
     * @param string $paymentId
     * @param ApiInstallation $installation
     * @return bool
     */
    protected function verifyMyFatoorahPayment(string $paymentId, ApiInstallation $installation): bool
    {
        try {
            $paymentMethod = \App\Models\PaymentGateway::where('keyword', 'myfatoorah')->first();

            if (!$paymentMethod) {
                Log::error('MyFatoorah payment gateway not configured');
                return false;
            }

            $paydata = $paymentMethod->convertAutoData();
            Config::set('myfatorah.token', $paydata['token']);

            $myfatoorah = \Basel\MyFatoorah\MyFatoorah::getInstance(
                $paydata['sandbox_status'] == 1
            );

            $result = $myfatoorah->getPaymentStatus('paymentId', $paymentId);

            if (!$result || !($result['IsSuccess'] ?? false)) {
                Log::warning('MyFatoorah payment verification failed', [
                    'payment_id' => $paymentId,
                    'result' => $result,
                ]);

                return false;
            }

            $invoiceStatus = $result['Data']['InvoiceStatus'] ?? null;
            $invoiceValue = $result['Data']['InvoiceValue'] ?? 0;

            // Verify amount matches
            if (abs($invoiceValue - $installation->app->price) > 0.01) {
                Log::warning('Payment amount mismatch', [
                    'expected' => $installation->app->price,
                    'received' => $invoiceValue,
                ]);

                return false;
            }

            return $invoiceStatus === 'Paid';

        } catch (\Exception $e) {
            Log::error('MyFatoorah payment verification exception', [
                'payment_id' => $paymentId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Process payment result and update installation
     *
     * @param ApiInstallation $installation
     * @param array $payload
     * @return \Illuminate\Http\JsonResponse
     */
    protected function processPaymentResult(ApiInstallation $installation, array $payload)
    {
        $result = strtoupper($payload['result'] ?? '');
        $gateway = $payload['_gateway'] ?? $this->gatewayType ?? 'unknown';

        // ARB uses 'CAPTURED', MyFatoorah might use 'CAPTURED' or 'Paid'
        // Check for successful payment status
        $isSuccess = in_array($result, ['CAPTURED', 'PAID', 'SUCCESS']);

        if ($isSuccess) {
            // Verify amount for ARB payments
            if ($gateway === 'arb' && isset($payload['amt'])) {
                $expectedAmount = $installation->app->price;
                $paidAmount = (float) $payload['amt'];

                if (abs($paidAmount - $expectedAmount) > 0.01) {
                    Log::warning('ARB payment amount mismatch', [
                        'expected' => $expectedAmount,
                        'received' => $paidAmount,
                        'installation_id' => $installation->id,
                    ]);

                    return response()->json([
                        'status' => 'error',
                        'message' => 'Payment amount mismatch',
                    ], 422);
                }
            }

            // Payment successful - mark as installed
            try {
                $this->stateMachine->transition(
                    $installation,
                    InstallStatus::Installed,
                    [
                        'recurring_id' => $payload['RecurringId'] ?? null,
                        'payment_subscription_id' => $payload['RecurringId'] ?? null,
                    ]
                );

                Log::info('Installation activated via webhook', [
                    'gateway' => $gateway,
                    'installation_id' => $installation->id,
                    'user_id' => $installation->user_id,
                    'app_id' => $installation->app_id,
                    'payment_id' => $payload['PaymentId'],
                    'result' => $result,
                ]);

                return response()->json([
                    'status' => 'ok',
                    'message' => 'Installation activated',
                ]);

            } catch (\Exception $e) {
                Log::error('Failed to activate installation', [
                    'gateway' => $gateway,
                    'installation_id' => $installation->id,
                    'error' => $e->getMessage(),
                ]);

                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to activate installation',
                ], 500);
            }
        } else {
            // Payment failed or cancelled
            Log::info('Payment failed/cancelled via webhook', [
                'gateway' => $gateway,
                'installation_id' => $installation->id,
                'result' => $result,
                'payment_id' => $payload['PaymentId'],
            ]);

            return response()->json([
                'status' => 'ok',
                'message' => 'Payment result logged',
            ]);
        }
    }
}
