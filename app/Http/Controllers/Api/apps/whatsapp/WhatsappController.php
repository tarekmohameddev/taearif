<?php

namespace App\Http\Controllers\Api\apps\whatsapp;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\WhatsappUser;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;

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


public function index(Request $request)
{
    $userId = auth()->id();

    $user = WhatsappUser::where('user_id', $userId)->latest()->first();

    // Case 1: Not linked
    if (!$user) {
        return response()->json([
            'success' => true,
            'status' => 'not_linked',
            'message' => 'لم يتم ربط الرقم بعد'
        ]);
    }

    // Parse optional note metadata
    $noteData = json_decode($user->note, true) ?? [];

    // Case 2: Pending request
    if (($noteData['requestId'] ?? null) && $user->request_status === 'pending') {
        return response()->json([
            'success' => true,
            'status' => 'pending',
            'requestId' => $noteData['requestId'],
            'phoneNumber' => $user->number,
            'linkingMethod' => $noteData['linkingMethod'] ?? null,
            'apiMethod' => $noteData['apiMethod'] ?? null,
            'message' => 'طلب الربط قيد الانتظار'
        ]);
    }

    // Case 3: Rejected
    if ($user->request_status === 'rejected') {
        return response()->json([
            'success' => true,
            'status' => 'rejected',
            'phoneNumber' => $user->number,
            'message' => 'تم رفض طلب الربط'
        ]);
    }

    // Case 4: Linked
    return response()->json([
        'success' => true,
        'status' => 'linked',
        'phoneNumber' => $user->number,
        'linkingMethod' => $noteData['linkingMethod'] ?? null,
        'apiMethod' => $noteData['apiMethod'] ?? null,
        'message' => 'تم ربط الرقم بنجاح'
    ]);
}

}
