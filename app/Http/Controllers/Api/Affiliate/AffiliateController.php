<?php

namespace App\Http\Controllers\Api\Affiliate;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Api\ApiAffiliateUser;


class AffiliateController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'fullname' => 'required|string|max:255',
            'bank_name' => 'required|string|max:255',
            'bank_account_number' => 'required|string|max:30',
            'iban' => 'required|string|max:34',
        ]);

        $user = $request->user();

        // Check if the user is already registered as an affiliate
        if ($user->affiliateUser) {
            return response()->json([
                'status' => 'info',
                'message' => 'You have already submitted an affiliate registration.',
                'data' => [
                    'request_status' => $user->affiliateUser->request_status
                ]
            ], 200);
        }

        // Create a new affiliate user record
        $affiliate = ApiAffiliateUser::create([
            'user_id' => $user->id,
            'fullname' => $request->fullname,
            'bank_name' => $request->bank_name,
            'bank_account_number' => $request->bank_account_number,
            'iban' => $request->iban,
            'request_status' => 'pending'
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Affiliate registration submitted successfully.',
            'data' => $affiliate
        ], 201);
    }


    public function index()
    {
        $affiliates = ApiAffiliateUser::all();

        return response()->json([
            'status' => 'success',
            'data' => $affiliates
        ]);
    }
}
