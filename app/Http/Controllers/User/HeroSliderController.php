<?php

namespace App\Http\Controllers\User;

use App\Models\UserStep;
use App\Models\User\Menu;
use App\Models\User\Page;
use Illuminate\Http\Request;
use App\Models\User\Language;
use App\Http\Helpers\Uploader;
use App\Models\User\HeroSlider;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

use Illuminate\Support\Facades\Log;
class HeroSliderController extends Controller
{
    public function sliderVersion(Request $request)
    {
        // first, get the language info from db
        // $language = Language::where('code', $request->language)->where('user_id', Auth::guard('web')->user()->id)->first();
        $lang = Language::where('code', $request->language)->where('user_id', Auth::user()->id)->firstOrFail();
        $data['lang_id'] = $lang->id;
        $data['keywords'] = json_decode($lang->keywords, true);

        // get previous menus
        $menu = Menu::where('language_id', $lang->id)->where('user_id', Auth::user()->id)->first();
        $data['prevMenu'] = '';
        if (!empty($menu)) {
            $data['prevMenu'] = $menu->menus;
        }

        $data['apages'] = Page::where('language_id', $lang->id)->where('user_id', Auth::user()->id)->orderBy('id', 'DESC')->get();


        if ($request->has('language')) {
            $language = Language::where([
                ['code', $request->language],
                ['user_id', Auth::id()]
            ])->first();
            Session::put('currentLangCode', $request->language);
        } else {
            $language = Language::where([
                ['is_default', 1],
                ['user_id', Auth::id()]
            ])
                ->first();
            Session::put('currentLangCode', $language->code);
        }

        // then, get the slider version info of that language from db
        $information['sliders'] = HeroSlider::where('language_id', $language->id)
            ->orderBy('id', 'desc')
            ->where('user_id', Auth::guard('web')->user()->id)
            ->get();
        return view('user.home.hero_section.slider_version', $information,$data);
    }

    public function createSlider(Request $request)
    {
        // get the language info from db
        $language = Language::where('code', $request->language)->where('user_id', Auth::guard('web')->user()->id)->first();
        $information['language'] = $language;
        return view('user.home.hero_section.create_slider', $information);
    }

    public function storeSliderInfo(Request $request): \Illuminate\Http\RedirectResponse
    {

        log::info($request);
        $request->validate(
            [
                'title' => 'nullable|max:255',
                'subtitle' => 'nullable|max:255',
                'btn_name' => 'nullable|max:255',
                'btn_url' => 'nullable|max:255',
                'serial_number' => 'required',
                'slider_img' => 'required|mimes:jpeg,jpg,png,gif|max:30000',
                'user_language_id' => 'required',
            ],
            [
                'title.max' => 'The title field can contain maximum 255 characters.',
                'subtitle.max' => 'The subtitle field can contain maximum 255 characters.',
                'btn_name.max' => 'The button name field can contain maximum 255 characters.',
                'btn_url.max' => 'The button url field can contain maximum 255 characters.',
                'serial_number.required' => 'The serial number field is required.',
                'slider_img.required' => 'The image field is required',
                'user_language_id.required' => 'The language field is required',
            ]
        );
        if ($request->hasFile('slider_img')) {
            $request['image_name'] = Uploader::upload_picture('assets/front/img/hero_slider', $request->file('slider_img'));
        }
        HeroSlider::create($request->except('language_id', 'img', 'user_id') + [
            'language_id' => $request->user_language_id,
            'img' => $request->image_name,
            'user_id' => Auth::guard('web')->user()->id,
        ]);

        UserStep::updateOrCreate(
            ['user_id' => Auth::guard('web')->user()->id],
            ['sub_pages_upper_image' => true]
        );

        $request->session()->flash('success', 'New slider added successfully!');
        return redirect()->back();
    }

    public function editSlider(Request $request, $id)
    {
        // get the language info from db
        $language = Language::where('code', $request->language)->where('user_id', Auth::guard('web')->user()->id)->first();
        $information['language'] = $language;
        // get the slider info from db for update
        $information['slider'] = HeroSlider::findOrFail($id);
        return view('user.home.hero_section.edit_slider', $information);
    }

    public function updateSliderInfo(Request $request, $id): \Illuminate\Http\RedirectResponse
    {
        $request->validate([
            'title' => 'nullable|max:255',
            'subtitle' => 'nullable|max:255',
            'btn_name' => 'nullable|max:255',
            'btn_url' => 'nullable|max:255',
            'serial_number' => 'required',
        ], [
            'title.max' => 'The title field can contain maximum 255 characters.',
            'subtitle.max' => 'The subtitle field can contain maximum 255 characters.',
            'btn_name.max' => 'The button name field can contain maximum 255 characters.',
            'btn_url.max' => 'The button url field can contain maximum 255 characters.',
            'serial_number.required' => 'The serial number field is required.',
        ]);
        $slider = HeroSlider::where('user_id', Auth::user()->id)->where('id', $id)->firstOrFail();
        $request['image_name'] = $slider->img;
        if ($request->hasFile('slider_img')) {
            $request['image_name'] = Uploader::update_picture('assets/front/img/hero_slider', $request->file('slider_img'), $slider->img);
        }
        $slider->update($request->except('img') + [
            'img' => $request->image_name,
        ]);

        UserStep::updateOrCreate(
            ['user_id' => Auth::guard('web')->user()->id],
            ['sub_pages_upper_image' => true]
        );

        $request->session()->flash('success', 'Slider info updated successfully!');
        return redirect()->back();
    }

    public function deleteSlider(Request $request)
    {
        $slider = HeroSlider::findOrFail($request->slider_id);
        if (
            !is_null($slider->img) &&
            file_exists(public_path('assets/front/img/hero_slider/' . $slider->img))
        ) {
            unlink(public_path('assets/front/img/hero_slider/' . $slider->img));
        }
        $slider->delete();
        $request->session()->flash('success', 'Slider deleted successfully!');
        return redirect()->back();
    }
}
