<?php

namespace App\Http\Controllers\Api\apps\whatsapp;

use App\Http\Controllers\Controller;
use App\Models\Api\ApiCustomerInquiry;
use Illuminate\Http\Request;
use App\Models\WhatsappUser;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class WhatsappController extends Controller
{
    public function store(Request $request)
    {

        $validated = $request->validate([
            'phoneNumber'      => ['required', 'regex:/^[0-9]{9}$/'], // 9 digits for KSA
            'linkingMethod'    => ['required', 'in:support,automatic'],
            'apiMethod'        => ['required', 'in:official,unofficial'],
            'customerName'     => ['nullable', 'string'],
            'supportMessage'   => ['nullable', 'string'],
        ]);

        $fullPhoneNumber = '+966' . $validated['phoneNumber'];
        $requestId = 'req_' . Str::random(8);
        $status = 'active';
        $request_status = 'pending';

        $whatsappUser = WhatsappUser::create([
            'user_id'    => auth()->id(),
            'number'     => $fullPhoneNumber,
            'name'       => $validated['customerName'] ?? null,
            'note'       => json_encode([
                'linkingMethod' => $validated['linkingMethod'],
                'apiMethod'     => $validated['apiMethod'],
                'requestId'     => $requestId,
                'supportMessage'=> $validated['supportMessage'] ?? null,
            ], JSON_UNESCAPED_UNICODE),
            'status'     => $status,
            'request_status'     => $status,
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'requestId' => $requestId,
                'status' => $status,
                'phoneNumber' => $fullPhoneNumber,
                'linkingMethod' => $validated['linkingMethod'],
                'apiMethod' => $validated['apiMethod'],
                'estimatedTime' => $validated['linkingMethod'] === 'support' ? '24-48 hours' : null,
                'verificationRequired' => $validated['linkingMethod'] === 'automatic',
            ],
            'message' => $validated['linkingMethod'] === 'support'
                ? 'تم إرسال طلب الدعم بنجاح'
                : 'تم بدء عملية الربط التلقائي بنجاح'
        ]);
    }

    /**
     * Get WhatsApp dashboard with stats and linked numbers.
     *
     * Returns:
     * - dashboard: total received messages, total linked numbers
     * - numbers: list of linked WhatsApp numbers with details and message counts
     */
    public function index(Request $request)
    {
        $userId = auth()->id();

        // Get all linked WhatsApp numbers for this user
        $whatsappUsers = WhatsappUser::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->get();

        // Get total count of linked numbers
        $totalLinkedNumbers = $whatsappUsers->count();

        // If no numbers linked, return empty dashboard
        if ($totalLinkedNumbers === 0) {
            return response()->json([
                'success' => true,
                'status' => 'not_linked',
                'message' => 'لم يتم ربط أي رقم بعد',
                'dashboard' => [
                    'total_messages_received' => 0,
                    'total_linked_numbers' => 0,
                ],
                'numbers' => [],
            ]);
        }

        // Collect all phone numbers (with variants) for message counting
        $allPhoneNumbers = [];
        foreach ($whatsappUsers as $wu) {
            if ($wu->number) {
                $allPhoneNumbers = array_merge($allPhoneNumbers, $this->buildPhoneVariants($wu->number));
            }
        }

        // Get total received messages for all linked numbers
        $totalMessagesReceived = ApiCustomerInquiry::where('user_id', $userId)
            ->where(function ($query) use ($allPhoneNumbers) {
                $query->where('source_channel', 'whatsapp')
                    ->orWhereIn('phone_number', $allPhoneNumbers);
            })
            ->count();

        // Build numbers list with individual message counts
        $numbers = $whatsappUsers->map(function ($wu) use ($userId) {
            $phoneVariants = $this->buildPhoneVariants($wu->number);

            // Count messages for this specific number
            $messagesReceived = ApiCustomerInquiry::where('user_id', $userId)
                ->whereIn('phone_number', $phoneVariants)
                ->count();

            // Parse note metadata
            $noteData = json_decode($wu->note, true) ?? [];

            return [
                'id' => $wu->id,
                'display_name' => $wu->name,
                'number' => $wu->number,
                'status' => $wu->status,
                'request_status' => $wu->request_status,
                'messages_received' => $messagesReceived,
                'business_id' => $wu->business_id,
                'waba_id' => $wu->waba_id,
                'phone_id' => $wu->phone_id,
                'linking_method' => $noteData['linkingMethod'] ?? null,
                'api_method' => $noteData['apiMethod'] ?? null,
                'token_expires_at' => $wu->token_expires_at,
                'created_at' => $wu->created_at?->toIso8601String(),
                'updated_at' => $wu->updated_at?->toIso8601String(),
            ];
        });

        // Determine overall status
        $hasActiveNumber = $whatsappUsers->where('status', 'active')->isNotEmpty();
        $hasPendingRequest = $whatsappUsers->where('request_status', 'pending')->isNotEmpty();

        $overallStatus = 'linked';
        if (!$hasActiveNumber) {
            $overallStatus = $hasPendingRequest ? 'pending' : 'inactive';
        }

        return response()->json([
            'success' => true,
            'status' => $overallStatus,
            'message' => $this->getStatusMessage($overallStatus),
            'dashboard' => [
                'total_messages_received' => $totalMessagesReceived,
                'total_linked_numbers' => $totalLinkedNumbers,
            ],
            'numbers' => $numbers,
        ]);
    }

    /**
     * Build phone number variants for matching (with/without +, country code variations).
     */
    private function buildPhoneVariants(?string $phone): array
    {
        if (!$phone) {
            return [];
        }

        $normalized = ltrim($phone, '+');
        return array_unique([
            $phone,
            $normalized,
            '+' . $normalized,
        ]);
    }

    /**
     * Get localized status message.
     */
    private function getStatusMessage(string $status): string
    {
        return match ($status) {
            'linked' => 'تم ربط الأرقام بنجاح',
            'pending' => 'طلب الربط قيد الانتظار',
            'inactive' => 'لا توجد أرقام نشطة',
            default => 'حالة غير معروفة',
        };
    }
}
