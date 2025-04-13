<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Http\Controllers\Payment\ArbController;

class PaymentController extends Controller
{
    public function checkout(Request $request)
    {
        try {
            // Get user from token instead of auth() helper
            $user = Auth::user();
            
            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthorized access'
                ], 401);
            }
    
            $data = $request->all();
            $data['status'] = "1";
            $data['receipt_name'] = null;
            $data['email'] = $user->email;
            
            $title = "You are extending your membership";
            $description = "Congratulation you are going to join our membership.Please make a payment for confirming your membership now!";
            
      
                $amount = $request->price;
                
                // Change success and cancel URLs to API endpoints
                $success_url = route('membership.arb.success');
                $cancel_url = route('membership.arb.cancel');
                
                $arbPayment = new ArbController();
                $result = $arbPayment->paymentProcess($request, $amount, $success_url, $cancel_url, $title);
                
                return response()->json([
                    'status' => 'success',
                    'payment_url' => $result['redirect_url'],
                    'payment_token' => $result['payment_token'] ?? null
                ], 200);

            
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
