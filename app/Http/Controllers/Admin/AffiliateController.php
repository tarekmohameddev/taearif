<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Api\ApiAffiliateUser;
class AffiliateController extends Controller
{
    // index method to display the list of affiliates
    public function index(Request $request)
    {
        // $data['affiliates'] = ApiAffiliateUser::orderBy('id', 'DESC')->paginate(10);
        // return view('admin.affiliate.index', $data);
        $query = ApiAffiliateUser::query();

        // Check if a search term is provided
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('id', 'LIKE', "%{$search}%")
                  ->orWhere('fullname', 'LIKE', "%{$search}%")
                  ->orWhere('bank_name', 'LIKE', "%{$search}%")
                  ->orWhere('bank_account_number', 'LIKE', "%{$search}%")
                  ->orWhere('iban', 'LIKE', "%{$search}%")
                  ->orWhere('request_status', 'LIKE', "%{$search}%");
            });
        }

        $data['affiliates'] = $query->orderBy('id', 'DESC')->paginate(10);
        return view('admin.affiliate.index', $data);
    }

    // updateStatus method to update the affiliate status
    public function updateStatus(Request $request, $id)
    {
        $affiliate = ApiAffiliateUser::findOrFail($id);
        $affiliate->request_status = $request->request_status;
        $affiliate->save();
        return redirect()->back()->with('success', 'Affiliate status updated successfully');
    }
}
