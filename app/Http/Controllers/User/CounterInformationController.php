<?php

namespace App\Http\Controllers\User;

use App\Models\User\Brand;
use App\Models\User\Skill;
use Illuminate\Http\Request;
use App\Models\User\Language;
use App\Models\User\UserService;
use App\Models\User\HomePageText;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\User\CounterInformation;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;

class CounterInformationController extends Controller
{
    public function index(Request $request)
    {
        if ($request->has('language')) {
            $lang = Language::where([
                ['code', $request->language],
                ['user_id', Auth::id()]
            ])->first();
            Session::put('currentLangCode', $request->language);
        } else {
            $lang = Language::where([
                ['is_default', 1],
                ['user_id', Auth::id()]
            ])
                ->first();
            Session::put('currentLangCode', $lang->code);
        }

        // counter-informations
        $data['counterInformations'] = CounterInformation::where([
            ['language_id', '=', $lang->id],
            ['user_id', '=', Auth::id()],
        ])
        ->orderBy('id', 'DESC')
        ->get();

        // skills
        $data['skills'] = Skill::where([
            ['language_id', '=', $lang->id],
            ['user_id', '=', Auth::id()],
        ])
            ->orderBy('id', 'DESC')
            ->get();

        //services
        $language = Language::where('user_id', Auth::guard('web')->user()->id)->where('code', $request->language)->firstOrFail();

        $text = HomePageText::where('user_id', Auth::guard('web')->user()->id)->where('language_id', $language->id);
        if ($text->count() == 0) {
            $text = new HomePageText;
            $text->language_id = $language->id;
            $text->user_id = Auth::guard('web')->user()->id;
            $text->save();
        } else {
            $text = $text->first();
        }
        $data['home_setting'] = $text;
        $data['services'] = UserService::where([
            ['lang_id', '=', $lang->id],
            ['user_id', '=', Auth::id()],
        ])
            ->orderBy('id', 'DESC')
            ->get();

        //brands
        $data['brands'] = Brand::where('user_id', Auth::guard('web')->user()->id)
            ->orderBy('id', 'desc')
            ->get();


        return view('user.counter-information.index', $data);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return
     */
    public function store(Request $request)
    {
        $messages = [
            'user_language_id.required' => 'The language field is required',
        ];

        $rules = [
            'user_language_id' => 'required',
            'title' => 'required|max:255',
            'count' => 'required|integer',
            'serial_number' => 'required|integer'
        ];

        $validator = Validator::make($request->all(), $rules, $messages);
        if ($validator->fails()) {
            $validator->getMessageBag()->add('error', 'true');
            return response()->json($validator->errors());
        }
        $input = $request->all();
        $input['language_id'] = $request->user_language_id;
        $input['user_id'] = Auth::id();

        $counterInformation = new CounterInformation;
        $counterInformation->create($input);
        Session::flash('success', 'Counter Information added successfully!');
        return "success";
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return
     */
    public function edit($id)
    {
        $data['counterInformation'] = CounterInformation::where('user_id', Auth::user()->id)->where('id', $id)->firstOrFail();
        return view('user.counter-information.edit', $data);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     *
     */
    public function update(Request $request)
    {
        $rules = [
            'title' => 'required|max:255',
            'count' => 'required|integer',
            'serial_number' => 'required|integer'
        ];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $validator->getMessageBag()->add('error', 'true');
            return response()->json($validator->errors());
        }
        $input = $request->all();
        $counterInformation = CounterInformation::where('user_id', Auth::user()->id)->where('id', $request->counter_information_id)->firstOrFail();
        $slug = make_slug($request->title);
        $input['slug'] = $slug;
        $input['user_id'] = Auth::id();
        $input['icon'] = $request->icon === null ? $counterInformation->icon : $request->icon;
        $counterInformation->update($input);
        Session::flash('success', 'Counter Information updated successfully!');
        return "success";
    }

    public function delete(Request $request)
    {
        CounterInformation::where('user_id', Auth::user()->id)->where('id', $request->counter_information_id)->firstOrFail()->delete();
        Session::flash('success', 'Counter Information deleted successfully!');
        return back();
    }
    public function bulkDelete(Request $request)
    {
        $ids = $request->ids;
        foreach ($ids as $id) {
            CounterInformation::where('user_id', Auth::user()->id)->where('id', $id)->firstOrFail()->delete();
        }
        Session::flash('success', 'Counter Information bulk-deleted successfully!');
        return "success";
    }
}
