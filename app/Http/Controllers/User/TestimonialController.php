<?php

namespace App\Http\Controllers\User;

use Purifier;
use Validator;
use App\Models\UserStep;
use Illuminate\Http\Request;
use App\Models\User\Language;
use App\Models\User\BasicSetting;
use App\Models\User\HomePageText;
use App\Http\Controllers\Controller;
use App\Models\User\UserTestimonial;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class TestimonialController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return
     */
    public function index(Request $request)
    {
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
            Session::put('currentLangCode', $lang->codel);
        }
        $data['testimonials'] = UserTestimonial::where([
            ['lang_id', '=', $lang->id],
            ['user_id', '=', Auth::id()],
        ])
            ->orderBy('id', 'DESC')
            ->get();

        return view('user.testimonial.index', $data);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return
     */
    public function store(Request $request)
    {
        $img = $request->file('image');
        $allowedExts = array('jpg', 'png', 'jpeg');
        $messages = [
            'name.required' => 'The title field is required',
            'user_language_id.required' => 'The Language field is required',
            'content.required' => 'The content field is required',
            'serial_number.required' => 'The serial number field is required',
        ];
        $userBs = BasicSetting::where('user_id', Auth::id())->select('theme')->first();
        $rules = [
            'name' => 'required|max:255',
            'user_language_id' => 'required',
            'content' => 'required',
            'serial_number' => 'required|integer',
            'gender' => 'nullable',

        ];
        if ($userBs->theme != 'home_nine') {
            $rules += [
                'image' => 'mimes:jpg,jpeg,png'
            ];
        } else {
            $rules += [
                'image' => 'nullable'
            ];
        }
        $validator = Validator::make($request->all(), $rules, $messages);
        if ($validator->fails()) {
            $errmsgs = $validator->getMessageBag()->add('error', 'true');
            return response()->json($validator->errors());
        }
        $input = $request->all();
        $input['user_id'] = Auth::id();
        $input['gender'] = $request->gender?? null;

        if ($request->hasFile('image')) {
            $filename = time() . '.' . $img->getClientOriginalExtension();
            $directory = public_path('assets/front/img/user/testimonials/');
            if (!file_exists($directory)) mkdir($directory, 0775, true);
            $request->file('image')->move($directory, $filename);
            $input['image'] = $filename;
        }
        $input['content'] = Purifier::clean($request->content);
        $input['lang_id'] = $request->user_language_id;
        $blog = new UserTestimonial();
        $blog->create($input);

        Session::flash('success', 'Testimonial added successfully!');
        return "success";
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return
     */
    public function edit($id)
    {
        $data['testimonial'] = UserTestimonial::where('user_id', Auth::user()->id)->where('id', $id)->firstOrFail();
        return view('user.testimonial.edit', $data);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        $img = $request->file('image');
        $allowedExts = array('jpg', 'png', 'jpeg');

        $messages = [
            'name.required' => 'The title field is required',
            'content.required' => 'The content field is required',
            'serial_number.required' => 'The serial number field is required',

        ];

        $rules = [
            'name' => 'required|max:255',
            'content' => 'required',
            'serial_number' => 'required|integer',
            'serial_number' => 'nullable',
            'gender' => 'nullable',

        ];
        $userBs = BasicSetting::where('user_id', Auth::id())->select('theme')->first();
        $service = UserTestimonial::where('user_id', Auth::user()->id)->where('id', $request->id)->firstOrFail();
        if ($userBs->theme != 'home_nine' && empty($service->image)) {
            $rules += [
                'image' => 'nullable|mimes:jpg,jpeg,png'
            ];
        }


        $validator = Validator::make($request->all(), $rules, $messages);
        if ($validator->fails()) {
            $errmsgs = $validator->getMessageBag()->add('error', 'true');
            return response()->json($validator->errors());
        }
        $input = $request->all();
        $input['user_id'] = Auth::id();
        $input['gender'] = $request->gender ?? $service->gender;

        if ($request->hasFile('image')) {
            $filename = time() . '.' . $img->getClientOriginalExtension();
            $directory = public_path('assets/front/img/user/testimonials/');
            $request->file('image')->move($directory, $filename);
            if (file_exists($directory . $service->image)) {
                @unlink($directory . $service->image);
            }
            $input['image'] = $filename;
        }
        $input['content'] = Purifier::clean($request->content);
        $service->update($input);
        Session::flash('success', 'Testimonial updated successfully!');

        UserStep::updateOrCreate(
            ['user_id' => Auth::guard('web')->user()->id],
            ['user_testimonial' => true]
        );
        return "success";
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function delete(Request $request)
    {
        $service = UserTestimonial::where('user_id', Auth::user()->id)->where('id', $request->id)->firstOrFail();
        if (file_exists(public_path('assets/front/img/user/testimonials/' . $service->image))) {
            @unlink(public_path('assets/front/img/user/testimonials/' . $service->image));
        }
        $service->delete();
        Session::flash('success', 'Testimonial deleted successfully!');
        return back();
    }

    public function bulkDelete(Request $request)
    {
        $ids = $request->ids;
        foreach ($ids as $id) {
            $service = UserTestimonial::where('user_id', Auth::user()->id)->where('id', $id)->firstOrFail();
            if (file_exists(public_path('assets/front/img/user/testimonials/' . $service->image))) {
                @unlink(public_path('assets/front/img/user/testimonials/' . $service->image));
            }
            $service->delete();
        }
        Session::flash('success', 'Testimonial deleted successfully!');
        return "success";
    }
}
