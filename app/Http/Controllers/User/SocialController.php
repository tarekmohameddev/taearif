<?php

namespace App\Http\Controllers\User;

use App\Models\UserStep;
use App\Models\User\Social;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class SocialController extends Controller
{
    public function index()
    {
        $data['socials'] = Social::where('user_id', Auth::id())
            ->orderBy('id', 'DESC')
            ->get();
        return view('user.settings.social.index', $data);
    }

    public function store(Request $request)
    {
        $request->validate([
            'icon' => 'required',
            'url' => 'required',
            'serial_number' => 'required|integer',
        ]);

        $social = new Social;
        $social->icon = $request->icon;
        $social->url = $request->url;
        $social->serial_number = $request->serial_number;
        $social->user_id = Auth::id();
        $social->save();

        UserStep::updateOrCreate(
            ['user_id' => Auth::guard('web')->user()->id],
            ['user_social' => true]
        );

        Session::flash('success', 'New link added successfully!');
        return back();
    }

    public function edit($id)
    {
        $data['social'] = Social::where('user_id', Auth::id())->where('id', $id)->firstOrFail();
        return view('user.settings.social.edit', $data);
    }

    public function update(Request $request)
    {
        $request->validate([
            'icon' => 'required',
            'url' => 'required',
            'serial_number' => 'required|integer',
        ]);

        $social = Social::where('user_id', Auth::id())->where('id', $request->socialid)->firstOrFail();
        $social->icon = $request->icon;
        $social->url = $request->url;
        $social->serial_number = $request->serial_number;
        $social->user_id = Auth::id();
        $social->save();

        UserStep::updateOrCreate(
            ['user_id' => Auth::guard('web')->user()->id],
            ['user_social' => true]
        );

        Session::flash('success', 'Social link updated successfully!');
        return back();
    }

    public function delete(Request $request)
    {

        $social = Social::where('user_id', Auth::id())
                        ->where('id', $request->socialid)
                        ->firstOrFail();
        $social->delete();
        //  return a JSON response If the request is AJAX
        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Social link deleted successfully.'
            ]);
        }
        // otherwise, flash a message and return back.
        Session::flash('success', 'Social link deleted successfully!');
        return back();
    }

}
