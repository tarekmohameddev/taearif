<?php

namespace App\Http\Controllers\User;

use Illuminate\Http\Request;
use App\Models\User\Language;
use App\Http\Controllers\Controller;
use App\Models\User\UserOfferBanner;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;

class OfferBannerController extends Controller
{
    public function index(Request $request)
    {
        $lang = Language::where('code', $request->language)->where('user_id', Auth::guard('web')->user()->id)->first();
        $lang_id = $lang->id;
        $data['offers'] = UserOfferBanner::where('language_id', $lang_id)->where('user_id', Auth::guard('web')->user()->id)->orderBy('id', 'DESC')->get();
        $data['lang_id'] = $lang_id;
        return view('user.offerbanner.index', $data);
    }
    public function edit($id)
    {
        $data['offer'] = UserOfferBanner::findOrFail($id);
        return view('user.offerbanner.edit', $data);
    }
    public function store(Request $request)
    {
        $rules = [
            'user_language_id' => 'required',
            'image' => 'required',
            'position' => 'required',
            'text_1' => 'required|max:100',
            'text_2' => 'required|max:100',
            'text_3' => 'required|max:100',
            'url' => 'required',
        ];
        $messages = [
            'user_language_id.required' => 'The language field is required',
            'position.required' => 'Please select banner possition'
        ];
        $validator = Validator::make($request->all(), $rules, $messages);
        if ($validator->fails()) {
            $errmsgs = $validator->getMessageBag()->add('error', 'true');
            return response()->json($validator->errors());
        }
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $name = time() . $file->getClientOriginalName();
            $file->move(public_path('assets/front/img/user/offers/'), $name);
        }
        $offer = new UserOfferBanner;
        $offer->user_id = Auth::guard('web')->user()->id;
        $offer->image = $name;
        $offer->language_id = $request->user_language_id;
        $offer->position = $request->position;
        $offer->text_1 = $request->text_1;
        $offer->text_2 = $request->text_2;
        $offer->text_3 = $request->text_3;
        $offer->url = $request->url;
        $offer->save();
        Session::flash('success', 'Offer Banner added successfully!');
        return "success";
    }
    public function update(Request $request)
    {
        $rules = [
            'text_1' => 'required|max:100',
            'text_2' => 'required|max:100',
            'text_3' => 'required|max:100',
            'url' => 'required',
        ];
        $messages = [];
        $validator = Validator::make($request->all(), $rules, $messages);
        if ($validator->fails()) {
            $errmsgs = $validator->getMessageBag()->add('error', 'true');
            return response()->json($validator->errors());
        }
        $offer = UserOfferBanner::findOrFail($request->offer_id);
        if ($request->hasFile('image')) {
            @unlink(public_path('assets/front/img/user/offers/' . $offer->image));
            $file = $request->file('image');
            $name = time() . $file->getClientOriginalName();
            $file->move(public_path('assets/front/img/user/offers/'), $name);
        }
        if ($request->image) {
            $offer->image = $name;
        }
        $offer->position = $request->position;
        $offer->text_1 = $request->text_1;
        $offer->text_2 = $request->text_2;
        $offer->text_3 = $request->text_3;
        $offer->url = $request->url;
        $offer->save();
        Session::flash('success', 'Offer Banner updated successfully!');
        return back();
    }
    public function delete(Request $request)
    {
        $offer = UserOfferBanner::findOrFail($request->offer_id);
        @unlink(public_path('assets/front/img/user/offers/' . $offer->image));
        $offer->delete();
        Session::flash('success', 'Offer Banner  deleted successfully!');
        return back();
    }
}
