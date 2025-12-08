<?php

namespace App\Http\Controllers\Api\apps\whatsapp;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\ResolvesTenant;
use Illuminate\Http\Request;
use App\Models\WhatsappUser;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class WhatsappController extends Controller
{
    use ResolvesTenant;

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
        $requestId = 'req_' . Str::random(8);
        $status = 'active';
        $request_status = 'pending';

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
    }

    public function index(Request $request)
    {
        $tenantId = $this->tenantId();

        $user = WhatsappUser::where('user_id', $tenantId)
            ->with('employee:id,first_name,last_name,email')
            ->latest()
            ->first();

        if (!$user) {
            return response()->json([
                'success' => true,
                'status' => 'not_linked',
                'message' => 'لم يتم ربط الرقم بعد'
            ]);
        }

        $noteData = json_decode($user->note, true) ?? [];

        $responseData = [
            'phoneNumber' => $user->number,
            'linkingMethod' => $noteData['linkingMethod'] ?? null,
            'apiMethod' => $noteData['apiMethod'] ?? null,
        ];

        if ($user->employee_id && $user->employee) {
            $responseData['employee'] = [
                'id' => $user->employee->id,
                'name' => trim(($user->employee->first_name ?? '') . ' ' . ($user->employee->last_name ?? '')),
                'email' => $user->employee->email,
            ];
        }

        if (($noteData['requestId'] ?? null) && $user->request_status === 'pending') {
            return response()->json([
                'success' => true,
                'status' => 'pending',
                'requestId' => $noteData['requestId'],
                ...$responseData,
                'message' => 'طلب الربط قيد الانتظار'
            ]);
        }

        if ($user->request_status === 'rejected') {
            return response()->json([
                'success' => true,
                'status' => 'rejected',
                ...$responseData,
                'message' => 'تم رفض طلب الربط'
            ]);
        }

        return response()->json([
            'success' => true,
            'status' => 'linked',
            ...$responseData,
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
