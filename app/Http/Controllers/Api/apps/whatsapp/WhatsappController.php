<?php

namespace App\Http\Controllers\Api\apps\whatsapp;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ResolvesTenant;
use App\Models\Api\ApiCustomerInquiry;
use Illuminate\Http\Request;
use App\Models\WhatsappUser;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class WhatsappController extends Controller
{
    use ResolvesTenant;

    public function store(Request $request)
    {
        $tenantId = $this->tenantId();

        $validated = $request->validate([
            'phoneNumber'      => ['required', 'regex:/^[0-9]{9}$/'], // KSA
            'linkingMethod'    => ['required', 'in:support,automatic'],
            'apiMethod'        => ['required', 'in:official,unofficial'],
            'customerName'     => ['nullable', 'string'],
            'supportMessage'   => ['nullable', 'string'],
            'employeeId'       => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where(function ($query) use ($tenantId) {
                    $query->where('tenant_id', $tenantId)
                          ->where('account_type', 'employee')
                          ->where('active', true);
                }),
            ],
        ]);

        $fullPhoneNumber = '+966' . $validated['phoneNumber'];
        $requestId = 'req_' . Str::random(8);

        $whatsappUser = WhatsappUser::create([
            'user_id'       => $tenantId,
            'employee_id'   => $validated['employeeId'] ?? null,
            'number'        => $fullPhoneNumber,
            'name'          => $validated['customerName'] ?? null,
            'note'          => json_encode([
                'linkingMethod' => $validated['linkingMethod'],
                'apiMethod'     => $validated['apiMethod'],
                'requestId'     => $requestId,
                'supportMessage'=> $validated['supportMessage'] ?? null,
            ], JSON_UNESCAPED_UNICODE),
            'status'        => 'active',
            'request_status'=> 'pending',
        ]);

        $responseData = [
            'requestId' => $requestId,
            'status'    => 'active',
            'phoneNumber' => $fullPhoneNumber,
            'linkingMethod' => $validated['linkingMethod'],
            'apiMethod' => $validated['apiMethod'],
            'estimatedTime' => $validated['linkingMethod'] === 'support' ? '24-48 hours' : null,
            'verificationRequired' => $validated['linkingMethod'] === 'automatic',
        ];

        if ($whatsappUser->employee_id) {
            $whatsappUser->load('employee:id,first_name,last_name,email');

            if ($whatsappUser->employee) {
                $responseData['employee'] = [
                    'id'    => $whatsappUser->employee->id,
                    'name'  => trim(($whatsappUser->employee->first_name ?? '') . ' ' . ($whatsappUser->employee->last_name ?? '')),
                    'email' => $whatsappUser->employee->email,
                ];
            }
        }

        return response()->json([
            'success' => true,
            'data' => $responseData,
            'message' =>
                $validated['linkingMethod'] === 'support'
                ? 'تم إرسال طلب الدعم بنجاح'
                : 'تم بدء عملية الربط التلقائي بنجاح'
        ]);
    }

    // =====================================================================
    // MAIN INDEX — Dashboard Style
    // =====================================================================

    public function index(Request $request)
    {
        $userId = auth()->id();

        $whatsappUsers = WhatsappUser::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->get();

        $totalLinkedNumbers = $whatsappUsers->count();

        if ($totalLinkedNumbers === 0) {
            return response()->json([
                'success' => true,
                'status' => 'not_linked',
                'dashboard' => [
                    'total_messages_received' => 0,
                    'total_linked_numbers' => 0,
                ],
                'numbers' => [],
                'message' => 'لم يتم ربط أي رقم بعد'
            ]);
        }

        $allPhoneNumbers = [];
        foreach ($whatsappUsers as $wu) {
            if ($wu->number) {
                $allPhoneNumbers = array_merge($allPhoneNumbers, $this->buildPhoneVariants($wu->number));
            }
        }

        $totalMessagesReceived = ApiCustomerInquiry::where('user_id', $userId)
            ->where(function ($query) use ($allPhoneNumbers) {
                $query->where('source_channel', 'whatsapp')
                      ->orWhereIn('phone_number', $allPhoneNumbers);
            })
            ->count();

        $numbers = $whatsappUsers->map(function ($wu) use ($userId) {
            $phoneVariants = $this->buildPhoneVariants($wu->number);

            $messagesReceived = ApiCustomerInquiry::where('user_id', $userId)
                ->whereIn('phone_number', $phoneVariants)
                ->count();

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

        $hasActive = $whatsappUsers->where('status', 'active')->isNotEmpty();
        $hasPending = $whatsappUsers->where('request_status', 'pending')->isNotEmpty();

        $overallStatus = $hasActive ? 'linked' : ($hasPending ? 'pending' : 'inactive');

        return response()->json([
            'success' => true,
            'status' => $overallStatus,
            'dashboard' => [
                'total_messages_received' => $totalMessagesReceived,
                'total_linked_numbers' => $totalLinkedNumbers,
            ],
            'numbers' => $numbers,
            'message' => $this->getStatusMessage($overallStatus)
        ]);
    }

    public function updateEmployee(Request $request, $id)
    {
        $tenantId = $this->tenantId();

        $whatsappUser = WhatsappUser::where('id', $id)
            ->where('user_id', $tenantId)
            ->firstOrFail();

        $validated = $request->validate([
            'employeeId' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where(function ($query) use ($tenantId) {
                    $query->where('tenant_id', $tenantId)
                          ->where('account_type', 'employee')
                          ->where('active', true);
                }),
            ],
        ]);

        $whatsappUser->employee_id = $validated['employeeId'] ?? null;
        $whatsappUser->save();

        $responseData = [
            'id' => $whatsappUser->id,
            'phoneNumber' => $whatsappUser->number,
        ];

        if ($whatsappUser->employee_id) {
            $whatsappUser->load('employee:id,first_name,last_name,email');
            if ($whatsappUser->employee) {
                $responseData['employee'] = [
                    'id' => $whatsappUser->employee->id,
                    'name' => trim(($whatsappUser->employee->first_name ?? '') . ' ' . ($whatsappUser->employee->last_name ?? '')),
                    'email' => $whatsappUser->employee->email,
                ];
            }
        } else {
            $responseData = [
                'id' => $whatsappUser->id,
                'phoneNumber' => $whatsappUser->number,
                'employee' => null,
            ];
        }
    }

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
