<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Api\Isthara;

class AdminIstharaController extends Controller
{
    //
    public function index(Request $request)
    {
        $data['bookings'] = Isthara::orderBy('id', 'DESC')->paginate(10);
        return view('admin.isthara.index', $data);
    }

    public function show($id)
    {
        $data['booking'] = Isthara::findOrFail($id);
        if (!$data['booking']->is_read) {
            $data['booking']->update(['is_read' => true]);
        }
        return view('admin.isthara.show', $data);
    }

    // mark as read
    public function markAsRead($id)
    {
        $booking = Isthara::findOrFail($id);
        $booking->update(['is_read' => 1]);
        return redirect()->back()->with('success', 'تم وضع علامة مقروءة');
    }
}
