<?php

namespace App\Services\Payment;

use App\Models\PaymentGateway;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ArbPaymentVerificationService
{
    /**
     * Verify payment status with ARB API
     *
     * @param string $paymentId ARB PaymentID
     * @return array{verified: bool, status: string, amount: float|null, details: array}
     */
    public function verifyPayment(string $paymentId): array
    {
        try {
            $paymentMethod = PaymentGateway::where('keyword', 'arb')->first();

            if (!$paymentMethod) {
                Log::error('ARB payment gateway not configured');
                return [
                    'verified' => false,
                    'status' => 'error',
                    'amount' => null,
                    'details' => ['error' => 'Payment gateway not configured'],
                ];
            }

            $paydata = $paymentMethod->convertAutoData();

            // ARB status inquiry endpoint
            $configName = 'bank_hosted_endpoint';
            $baseUrl = $paydata["mode"] == 'live'
                ? $paydata["live_$configName"]
                : $paydata["test_$configName"];

            // Extract base URL without the payment path
            $baseUrl = str_replace('/pg/paymentpage', '', $baseUrl);

            // ARB status inquiry API call
            // Note: ARB may not have a direct status inquiry endpoint
            // This is a placeholder - actual implementation depends on ARB API documentation
            $data = [
                'id' => $paydata['tranportal_id'],
                'password' => $paydata['tranportal_password'],
                'action' => '10', // Status inquiry action (verify with ARB docs)
                'trackId' => $paymentId,
            ];

            $data = $this->createRequestBody($this->wrapData($data));

            $response = Http::withBody($data, 'application/json')
                ->withOptions(['verify' => false])
                ->timeout(10)
                ->post($baseUrl . '/pg/paymentpage');

            $responseData = $response->json('0');

            if (isset($responseData['status']) && $responseData['status'] == '1') {
                // Payment verified
                return [
                    'verified' => true,
                    'status' => 'CAPTURED',
                    'amount' => $responseData['amt'] ?? null,
                    'details' => $responseData,
                ];
            }

            return [
                'verified' => false,
                'status' => $responseData['status'] ?? 'unknown',
                'amount' => null,
                'details' => $responseData ?? [],
            ];

        } catch (\Exception $e) {
            Log::error('ARB payment verification failed', [
                'payment_id' => $paymentId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'verified' => false,
                'status' => 'error',
                'amount' => null,
                'details' => ['error' => $e->getMessage()],
            ];
        }
    }

    /**
     * Wrap data for ARB API
     */
    protected function wrapData(array $data): string
    {
        return json_encode($data);
    }

    /**
     * Create request body for ARB API
     */
    protected function createRequestBody(string $data): string
    {
        // ARB expects data in specific format
        // This matches the format used in ArbController
        return $data;
    }
}
