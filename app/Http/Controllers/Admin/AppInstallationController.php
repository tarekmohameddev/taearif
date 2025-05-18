<?php

namespace App\Http\Controllers\Admin;

use App\Models\AppRequest;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class AppInstallationController extends Controller
{
    public function index(Request $request)
    {
        $requests = AppRequest::with(['user', 'app'])
            ->when($request->phone_number, function ($q) use ($request) {
                $q->where('phone_number', 'like', '%' . $request->phone_number . '%');
            })
            ->when($request->app_name, function ($q) use ($request) {
                $q->whereHas('app', function ($subQ) use ($request) {
                    $subQ->where('name', 'like', '%' . $request->app_name . '%');
                });
            })
            ->orderByDesc('id')
            ->paginate(10);

        return view('admin.app_requests.index', compact('requests'));
    }


    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:pending,approved,rejected',
        ]);

        $row = AppRequest::findOrFail($id);
        $row->status = $request->status;
        $row->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Request status updated successfully.',
        ]);
    }

    public function destroy($id)
    {
        $row = AppRequest::findOrFail($id);
        $row->delete();

        return redirect()->back()->with('success', 'Request deleted.');
    }

    public function storeFromApi(Request $request)
    {
        $request->validate([
            'username' => 'required|string|max:255',
            'phone_number' => 'required|string|max:20',
        ]);

        $existing = AppRequest::where('username', $request->username)
            ->where('phone_number', $request->phone_number)
            ->first();

        if ($existing && $existing->status === 'pending') {
            return response()->json([
                'status' => 'error',
                'message' => 'Request already pending.',
            ], 409);
        }

        $requestRecord = AppRequest::create([
            'username' => $request->username,
            'phone_number' => $request->phone_number,
            'status' => 'pending', // Default status for admin review
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Request submitted and pending admin approval.',
            'data' => $requestRecord,
        ]);
    }

}

