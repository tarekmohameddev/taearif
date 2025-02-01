<?php

namespace App\Http\Controllers\User\RealestateManagement\ManageProperty;

use App\Http\Controllers\Controller;
use App\Models\User\Language;
use App\Models\User\RealestateManagement\PropertyContact;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class PropertyMessageController extends Controller
{
    public function propertyMessages(Request $request)
    {
        $userId = Auth::guard('web')->user()->id;
        if ($request->has('language')) {
            $lang = Language::where([
                ['code', $request->language],
                ['user_id', $userId]
            ])->first();
            Session::put('currentLangCode', $request->language);
        } else {
            $lang = Language::where([
                ['is_default', 1],
                ['user_id', $userId]
            ])
                ->first();
            Session::put('currentLangCode', $lang->code);
        }


        $messages = PropertyContact::with(['propertyContent' => function ($q) use ($lang) {
            $q->where('language_id', $lang->id);
        }])->where('user_id', $userId)->latest()->get();
        return view('user.realestate_management.property-management.message', compact('messages'));
    }
    public function destroy(Request $request)
    {
        $userId = Auth::guard('web')->user()->id;
        $message = PropertyContact::where('user_id', $userId)->find($request->message_id);
        if ($message) {

            $message->delete();
        } else {
            Session::flash('warning', 'Something went wrong!');
            return redirect()->back();
        }
        Session::flash('success', 'Message deleted successfully');
        return redirect()->back();
    }
}
