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

            $phone = ltrim($request->phone, '0'); // remove leading 0 if present
            $fullPhone = '966' . $phone;
        $message_txt = '';
        $result = $this->sendWhatsAppMessage($fullPhone,'شكراً على التسجيل في منصة تعاريف');

        return response()->json([
            'status' => 'success',
            'message' => 'تم حجز الاستشارة بنجاح',
            'data' => $booking
        ], 201);
    }

    public function sendWhatsAppMessage($phone, $message)
    {
        try {
            $url = 'https://whatsapp-evolution-api.3dxvu8.easypanel.host/message/sendText/abdullah';
            $apiKey = '3DE40EE7B984-4281-8080-4D9C02E84CF3';

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'apikey' => $apiKey,
            ])->post($url, [
                'number' => $phone,
                'text' => $message,
            ]);

            if ($response->successful()) {
                return true;
            } else {
                \Log::error('WhatsApp API error: ' . $response->body());
                return false;
            }
        } catch (\Exception $e) {
            \Log::error('Exception while sending WhatsApp message: ' . $e->getMessage());
            return false;
        }
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
