<?php

namespace App\Http\Controllers\Api\apps\whatsapp;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ResolvesTenant;
use Illuminate\Http\Request;
use App\Models\WhatsappUser;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use App\Http\Requests\Api\Apps\Whatsapp\WhatsappStoreRequest;
use App\Http\Requests\Api\Apps\Whatsapp\WhatsappUpdateEmployeeRequest;

class WhatsappController extends Controller
{
    use ResolvesTenant;

    // Status constants
    const STATUS_ACTIVE = 'active';
    const STATUS_PENDING = 'pending';
    const STATUS_REJECTED = 'rejected';
    const STATUS_NOT_LINKED = 'not_linked';
    const STATUS_LINKED = 'linked';

    public function store(WhatsappStoreRequest $request)
    {
        $tenantId = $this->tenantId();
        $validated = $request->validated();

        $user = auth()->user()->tenantOwner();
        $isNotLinked = $validated['notLinked'] ?? false;

        if (!$isNotLinked && $user->whatsapp_usage >= $user->whatsapp_quota) {
            return response()->json([
                'success' => false,
                'message' => 'لقد وصلت للحد الأقصى لعدد الأرقام المسموح بها. يرجى شراء إضافة لزيادة الحد.'
            ], 422);
        }

        $fullPhoneNumber = str_starts_with($validated['phoneNumber'], '966') 
            ? '+' . $validated['phoneNumber'] 
            : '+966' . $validated['phoneNumber'];
        
        // Check for duplicate phone number
        $existing = WhatsappUser::where('user_id', $tenantId)
            ->where('number', $fullPhoneNumber)
            ->exists();

        if ($existing) {
            throw ValidationException::withMessages([
                'phoneNumber' => ['هذا الرقم مربوط بالفعل']
            ]);
        }

        $requestId = 'req_' . Str::random(8);
        // All new WhatsApp numbers start as not_linked with pending status
        $status = self::STATUS_NOT_LINKED;
        $request_status = self::STATUS_PENDING;

        try {
            $whatsappUser = WhatsappUser::create([
                'user_id'    => $tenantId,
                'employee_id' => $validated['employeeId'] ?? null,
                'number'     => $fullPhoneNumber,
                'name'       => $validated['customerName'] ?? null,
                'note'       => json_encode([
                    'linkingMethod' => $validated['linkingMethod'],
                    'apiMethod'     => $validated['apiMethod'],
                    'requestId'     => $requestId,
                    'supportMessage'=> $validated['supportMessage'] ?? null,
                ], JSON_UNESCAPED_UNICODE),
                'status'     => $status,
                'request_status'     => $request_status,
            ]);

            $responseData = [
                'requestId' => $requestId,
                'status' => $status,
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
                        'id' => $whatsappUser->employee->id,
                        'name' => trim(($whatsappUser->employee->first_name ?? '') . ' ' . ($whatsappUser->employee->last_name ?? '')),
                        'email' => $whatsappUser->employee->email,
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'data' => $responseData,
                'message' => $validated['linkingMethod'] === 'support'
                    ? 'تم إرسال طلب الدعم بنجاح'
                    : 'تم بدء عملية الربط التلقائي بنجاح'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء ربط الرقم. يرجى المحاولة مرة أخرى.'
            ], 500);
        }
    }

    public function index(Request $request)
    {
        $tenantId = $this->tenantId();

        $whatsappUsers = WhatsappUser::where('user_id', $tenantId)
            ->with('employee:id,first_name,last_name,email')
            ->orderBy('created_at', 'desc')
            ->get();

        if ($whatsappUsers->isEmpty()) {
            return response()->json([
                'success' => true,
                'data' => [
                    'status' => self::STATUS_NOT_LINKED,
                    'numbers' => [],
                    'total' => 0,
                ],
                'message' => 'لم يتم ربط أي رقم بعد'
            ]);
        }

        $numbers = $whatsappUsers->map(function ($whatsappUser) {
            $noteData = json_decode($whatsappUser->note, true) ?? [];

            $numberData = [
                'id' => $whatsappUser->id,
                'phoneNumber' => $whatsappUser->number,
                'name' => $whatsappUser->name,
                'status' => $whatsappUser->status,
                'request_status' => $whatsappUser->request_status,
                'linkingMethod' => $noteData['linkingMethod'] ?? null,
                'apiMethod' => $noteData['apiMethod'] ?? null,
                'requestId' => $noteData['requestId'] ?? null,
                'created_at' => $whatsappUser->created_at?->toIso8601String(),
                'updated_at' => $whatsappUser->updated_at?->toIso8601String(),
            ];

            if ($whatsappUser->employee_id && $whatsappUser->employee) {
                $numberData['employee'] = [
                    'id' => $whatsappUser->employee->id,
                    'name' => trim(($whatsappUser->employee->first_name ?? '') . ' ' . ($whatsappUser->employee->last_name ?? '')),
                    'email' => $whatsappUser->employee->email,
                ];
            }

            return $numberData;
        });

        // Determine overall status
        $hasActive = $whatsappUsers->where('status', self::STATUS_ACTIVE)->isNotEmpty();
        $hasPending = $whatsappUsers->where('request_status', self::STATUS_PENDING)->isNotEmpty();
        $overallStatus = $hasActive ? self::STATUS_LINKED : ($hasPending ? self::STATUS_PENDING : self::STATUS_REJECTED);

        return response()->json([
            'success' => true,
            'data' => [
                'status' => $overallStatus,
                'numbers' => $numbers,
                'total' => $whatsappUsers->count(),
                'active_count' => $whatsappUsers->where('status', self::STATUS_ACTIVE)->count(),
                'pending_count' => $whatsappUsers->where('request_status', self::STATUS_PENDING)->count(),
                'quota' => $request->user()->tenantOwner()->whatsapp_quota,
                'usage' => $request->user()->tenantOwner()->whatsapp_usage,
            ],
            'message' => $this->getStatusMessage($overallStatus)
        ]);
    }

    public function updateEmployee(WhatsappUpdateEmployeeRequest $request, $id)
    {
        $tenantId = $this->tenantId();

        $whatsappUser = WhatsappUser::where('id', $id)
            ->where('user_id', $tenantId)
            ->firstOrFail();

        $validated = $request->validated();
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
            $responseData['employee'] = null;
        }

        return response()->json([
            'success' => true,
            'data' => $responseData,
            'message' => $validated['employeeId'] 
                ? 'تم تعيين الموظف بنجاح' 
                : 'تم إلغاء تعيين الموظف بنجاح'
        ]);
    }

    public function destroy($id)
    {
        $tenantId = $this->tenantId();
        $whatsappUser = WhatsappUser::where('user_id', $tenantId)->findOrFail($id);

        $whatsappUser->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم حذف الرقم بنجاح'
        ]);
    }

    public function unlink($id)
    {
        $tenantId = $this->tenantId();
        $whatsappUser = WhatsappUser::where('user_id', $tenantId)->findOrFail($id);

        $whatsappUser->status = self::STATUS_NOT_LINKED;
        $whatsappUser->save();

        return response()->json([
            'success' => true,
            'message' => 'تم فك ربط الرقم بنجاح'
        ]);
    }

    public function link($id)
    {
        $tenantId = $this->tenantId();
        $whatsappUser = WhatsappUser::where('user_id', $tenantId)->findOrFail($id);

        if ($whatsappUser->status === self::STATUS_ACTIVE) {
            return response()->json([
                'success' => false,
                'message' => 'الرقم مربوط بالفعل'
            ], 422);
        }

        $whatsappUser->status = self::STATUS_ACTIVE;
        $whatsappUser->save();

        return response()->json([
            'success' => true,
            'message' => 'تم ربط الرقم بنجاح',
            'data' => [
                'id' => $whatsappUser->id,
                'phoneNumber' => $whatsappUser->number,
                'status' => $whatsappUser->status,
            ]
        ]);
    }

    private function getStatusMessage(string $status): string
    {
        return match ($status) {
            self::STATUS_LINKED => 'تم ربط الأرقام بنجاح',
            self::STATUS_PENDING => 'طلب الربط قيد الانتظار',
            self::STATUS_REJECTED => 'تم رفض طلب الربط',
            self::STATUS_NOT_LINKED => 'لم يتم ربط أي رقم بعد',
            default => 'حالة غير معروفة',
        };
    }
}
