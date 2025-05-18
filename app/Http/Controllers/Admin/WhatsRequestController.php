<?php

namespace App\Http\Controllers\Admin;

use App\Models\WhatsRequest;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class WhatsRequestController extends Controller
{
    public function index(Request $request)
    {
        $requests = WhatsRequest::when($request->username, function ($q) use ($request) {
                $q->where('username', 'like', '%' . $request->username . '%');
            })
            ->when($request->phone_number, function ($q) use ($request) {
                $q->where('phone_number', 'like', '%' . $request->phone_number . '%');
            })
            ->orderByDesc('id')
            ->paginate(10);

        return view('admin.whats_requestes.index', compact('requests'));
    }

    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:pending,approved,rejected',
        ]);

        $row = WhatsRequest::findOrFail($id);
        $row->status = $request->status;
        $row->save();

        return response()->json(['message' => 'Status updated successfully.']);
    }

    public function destroy($id)
    {
        $row = WhatsRequest::findOrFail($id);
        $row->delete();

        return redirect()->back()->with('success', 'Request deleted.');
    }
}

