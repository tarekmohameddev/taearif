<?php

namespace App\Http\Controllers\Api\isthara;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Api\Isthara;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;

class IstharaController extends Controller
{
    //
    public function store(Request $request)
    {
        \Log::info("message");
        // Validation Rules
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'phone' => 'required|regex:/^05[0-9]{8}$/',
            'recaptcha_token' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        // Verify reCAPTCHA
        $recaptchaResponse = Http::asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
            'secret' => config('services.recaptcha.secret'),
            'response' => $request->recaptcha_token
        ]);

        if (!$recaptchaResponse->json('success')) {
            return response()->json([
                'status' => 'error',
                'message' => 'فشل التحقق من reCAPTCHA'
            ], 422);
        }

        // Create Record
        $booking = Isthara::create([
            'name' => $request->name,
            'phone' => $request->phone
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'تم حجز الاستشارة بنجاح',
            'data' => $booking
        ], 201);
    }

    // public function index()
    // {
    //     $bookings = Isthara::all();
    //     return response()->json([
    //         'status' => 'success',
    //         'data' => $bookings
    //     ], 200);
    // }

    // public function show($id)
    // {
    //     $booking = Isthara::find($id);
    //     if (!$booking) {
    //         return response()->json([
    //             'status' => 'error',
    //             'message' => 'حجز غير موجود'
    //         ], 404);
    //     }
    //     return response()->json([
    //         'status' => 'success',
    //         'data' => $booking
    //     ], 200);
    // }

    // public function destroy($id)
    // {
    //     $booking = Isthara::find($id);
    //     if (!$booking) {
    //         return response()->json([
    //             'status' => 'error',
    //             'message' => 'حجز غير موجود'
    //         ], 404);
    //     }
    //     $booking->delete();
    //     return response()->json([
    //         'status' => 'success',
    //         'message' => 'تم حذف الحجز بنجاح'
    //     ], 200);
    // }
}
