<?php

namespace App\Http\Controllers\Api\apps\whatsapp;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ResolvesTenant;
use Illuminate\Http\Request;
use App\Models\WhatsappUser;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class WhatsappController extends Controller
{
    use ResolvesTenant;

    // Status constants
    const STATUS_ACTIVE = 'active';
    const STATUS_PENDING = 'pending';
    const STATUS_REJECTED = 'rejected';
    const STATUS_NOT_LINKED = 'not_linked';
    const STATUS_LINKED = 'linked';

    public function store(Request $request)
    {
        $tenantId = $this->tenantId();

        $validated = $request->validate([
            'phoneNumber'      => ['required', 'regex:/^[0-9]{9}$/'], // 9 digits for KSA
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
        $status = self::STATUS_ACTIVE;
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

        $whatsappUser = WhatsappUser::where('user_id', $tenantId)
            ->with('employee:id,first_name,last_name,email')
            ->latest()
            ->first();

        if (!$whatsappUser) {
            return response()->json([
                'success' => true,
                'data' => [
                    'status' => self::STATUS_NOT_LINKED,
                ],
                'message' => 'لم يتم ربط الرقم بعد'
            ]);
        }

        $noteData = json_decode($whatsappUser->note, true) ?? [];

        $responseData = [
            'phoneNumber' => $whatsappUser->number,
            'linkingMethod' => $noteData['linkingMethod'] ?? null,
            'apiMethod' => $noteData['apiMethod'] ?? null,
        ];

        if ($whatsappUser->employee_id && $whatsappUser->employee) {
            $responseData['employee'] = [
                'id' => $whatsappUser->employee->id,
                'name' => trim(($whatsappUser->employee->first_name ?? '') . ' ' . ($whatsappUser->employee->last_name ?? '')),
                'email' => $whatsappUser->employee->email,
            ];
        }

        if (($noteData['requestId'] ?? null) && $whatsappUser->request_status === self::STATUS_PENDING) {
            return response()->json([
                'success' => true,
                'data' => array_merge($responseData, [
                    'status' => self::STATUS_PENDING,
                    'requestId' => $noteData['requestId'],
                ]),
                'message' => 'طلب الربط قيد الانتظار'
            ]);
        }

        if ($whatsappUser->request_status === self::STATUS_REJECTED) {
            return response()->json([
                'success' => true,
                'data' => array_merge($responseData, [
                    'status' => self::STATUS_REJECTED,
                ]),
                'message' => 'تم رفض طلب الربط'
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => array_merge($responseData, [
                'status' => self::STATUS_LINKED,
            ]),
            'message' => 'تم ربط الرقم بنجاح'
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
}
