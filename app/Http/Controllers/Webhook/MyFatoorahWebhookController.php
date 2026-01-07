<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use App\Models\Api\ApiInstallation;
use App\Enums\InstallStatus;
use App\Services\InstallationStateMachine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;

/**
 * MyFatoorah Webhook Controller
 *
 * Handles payment webhooks from MyFatoorah for app installations
 * Includes security measures: rate limiting, validation, and payment verification
 */
class MyFatoorahWebhookController extends Controller
{
    protected InstallationStateMachine $stateMachine;

    public function __construct(InstallationStateMachine $stateMachine)
    {
        $this->stateMachine = $stateMachine;
    }

    /**
     * Handle webhook callback from MyFatoorah
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function handle(Request $request)
    {
        // Rate limiting: max 10 requests per minute per IP
        $key = 'webhook:myfatoorah:' . $request->ip();
        if (RateLimiter::tooManyAttempts($key, 10)) {
            Log::warning('Webhook rate limit exceeded', [
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

            // Validate payload structure
            $validator = Validator::make($payload, [
                'PaymentId' => 'required|string',
                'result' => 'required|string|in:CAPTURED,FAILED,CANCELLED',
                'udf3' => 'required|string',
                'udf4' => 'nullable|integer', // app_id
            ]);

            if ($validator->fails()) {
                Log::warning('Invalid webhook payload', [
                    'payload' => $payload,
                    'errors' => $validator->errors()->toArray(),
                    'ip' => $request->ip(),
                ]);

                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid payload',
                ], 422);
            }

            // Only process APP context webhooks
            if ($payload['udf3'] !== 'APP') {
                Log::debug('Webhook ignored - not APP context', [
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
                    'payment_id' => $payload['PaymentId'],
                    'ip' => $request->ip(),
                ]);

                return response()->json([
                    'status' => 'error',
                    'message' => 'Installation not found',
                ], 404);
            }

            // Verify payment with MyFatoorah API (idempotency check)
            if (!$this->verifyPayment($payload['PaymentId'], $installation)) {
                Log::error('Payment verification failed', [
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
     *
     * @param Request $request
     * @return array
     */
    protected function extractPayload(Request $request): array
    {
        // MyFatoorah sends data in different formats
        // Try array format first (common for webhooks)
        $payload = $request->all();

        // If it's wrapped in array, extract first element
        if (isset($payload[0]) && is_array($payload[0])) {
            return $payload[0];
        }

        // If it's direct array, use it
        if (is_array($payload) && !empty($payload)) {
            return $payload;
        }

        // Fallback: try JSON body
        $json = json_decode($request->getContent(), true);
        if (is_array($json)) {
            return $json[0] ?? $json;
        }

        return [];
    }

    /**
     * Verify payment with MyFatoorah API
     *
     * @param string $paymentId
     * @param ApiInstallation $installation
     * @return bool
     */
    protected function verifyPayment(string $paymentId, ApiInstallation $installation): bool
    {
        try {
            $paymentMethod = \App\Models\PaymentGateway::where('keyword', 'myfatoorah')->first();

            if (!$paymentMethod) {
                Log::error('MyFatoorah payment gateway not configured');
                return false;
            }

            $paydata = $paymentMethod->convertAutoData();
            \Config::set('myfatorah.token', $paydata['token']);

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
            Log::error('Payment verification exception', [
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
        $result = $payload['result'] ?? '';

        if ($result === 'CAPTURED') {
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
                    'installation_id' => $installation->id,
                    'user_id' => $installation->user_id,
                    'app_id' => $installation->app_id,
                    'payment_id' => $payload['PaymentId'],
                ]);

                return response()->json([
                    'status' => 'ok',
                    'message' => 'Installation activated',
                ]);

            } catch (\Exception $e) {
                Log::error('Failed to activate installation', [
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
