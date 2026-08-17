<?php

namespace App\Services\Payment;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PaymentIframeResult
{
    private const STATUSES = ['success', 'failed', 'cancelled', 'pending'];

    private const MESSAGES = [
        'success' => 'تمت العملية بنجاح',
        'failed' => 'فشلت عملية الدفع',
        'cancelled' => 'تم إلغاء عملية الدفع',
        'pending' => 'جاري تأكيد الدفع',
    ];

    public function respond(Request $request, array $payload): Response
    {
        $payload = $this->normalizePayload($payload);

        if ($request->expectsJson()) {
            $response = response()->json($payload);
        } else {
            $response = response()->view('payments.iframe-result', [
                'payload' => $payload,
                'targetOrigin' => config('payment_iframe.post_message_target_origin', '*'),
            ]);
        }

        $response->headers->remove('X-Frame-Options');
        $response->headers->set('Content-Security-Policy', self::contentSecurityPolicy());

        return $response;
    }

    public static function frameAncestors(): array
    {
        $ancestors = array_values(array_filter(
            array_map('trim', (array) config('payment_iframe.frame_ancestors', []))
        ));

        if (app()->environment('local') && !in_array('http://localhost:3000', $ancestors, true)) {
            $ancestors[] = 'http://localhost:3000';
        }

        return $ancestors;
    }

    public static function contentSecurityPolicy(): string
    {
        $ancestors = self::frameAncestors();

        return 'frame-ancestors ' . ($ancestors ? implode(' ', $ancestors) : "'none'");
    }

    private function normalizePayload(array $payload): array
    {
        $status = in_array($payload['status'] ?? null, self::STATUSES, true)
            ? $payload['status']
            : 'failed';
        $referenceNumber = $payload['reference_number'] ?? $payload['transaction_id'] ?? null;

        $result = [
            'source' => 'taearif-payment',
            'status' => $status,
            'message' => $payload['message'] ?? self::MESSAGES[$status],
            'transaction_id' => $referenceNumber,
            'reference_number' => $referenceNumber,
            'gateway' => $payload['gateway'] ?? null,
        ];

        foreach (['credits_added', 'new_balance', 'package_id'] as $key) {
            if (array_key_exists($key, $payload)) {
                $result[$key] = $payload[$key];
            }
        }

        return $result;
    }
}
