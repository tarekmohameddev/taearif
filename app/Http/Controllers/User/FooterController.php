<?php

namespace App\Http\Controllers\User;

use App\Models\UserStep;
use Illuminate\Http\Request;
use App\Models\User\Language;
use App\Http\Helpers\Uploader;
use App\Models\User\FooterText;
use App\Models\User\HomePageText;
use App\Http\Controllers\Controller;
use App\Models\User\FooterQuickLink;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Validator;

class FooterController extends Controller
{
    public function footerText(Request $request)
    {
        // first, get the language info from db
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



        // then, get the footer text info of that language from db
        $information['data'] = FooterText::where('language_id', $lang->id)->where('user_id', Auth::id())->first();
        return view('user.footer.text', $information);
    }

    public function updateFooterInfo(Request $request, $language)
    {
        $lang = Language::where('code', $language)->where('user_id', Auth::id())->firstOrFail();
        $data = FooterText::where('language_id', $lang->id)->where('user_id', Auth::id())->first();
        if (is_null($data)) {
            $data = new FooterText;
        }
        $rules = [
            'about_company' => 'nullable',
            'copyright_text' => 'nullable',
        ];
        $message = [
            'about_company.required' => 'The about company field is required',
            'copyright_text.required' => 'The copy right text field is required',
            'logo.required' => 'The logo field is required'
        ];
        if (is_null($data)) {
            $rules['logo'] = 'required|mimes:jpeg,jpg,png|max:1000';
        } elseif (is_null($data->logo) && !$request->hasFile('logo')) {
            $rules['logo'] = 'required|mimes:jpeg,jpg,png|max:1000';
        }
        $validator = Validator::make($request->all(), $rules, $message);
        if ($validator->fails()) {
            $validator->getMessageBag()->add('error', 'true');
            return response()->json($validator->errors());
        }
        $request['image_name'] = $data->logo;
        if ($request->hasFile('logo')) {
            $request['image_name'] = Uploader::update_picture('assets/front/img/user/footer/', $request->file('logo'), $data->logo);
        }
        if ($request->hasFile('bg_image')) {
            $request['bg_img_name'] = Uploader::update_picture('assets/front/img/user/footer/', $request->file('bg_image'), $data->bg_image);
        }
        $data->language_id =  $lang->id;
        $data->copyright_text =  clean($request->copyright_text);
        $data->logo =  $request->image_name;
        if ($request->color) {

            $data->footer_color =  $request->color;
        }
        $data->bg_image =  $request->bg_img_name;
        $data->user_id = Auth::id();
        $data->about_company = clean($request->about_company);
        $data->newsletter_text = clean($request->newsletter_text);
        $data->save();

        UserStep::updateOrCreate(
            ['user_id' => Auth::guard('web')->user()->id],
            ['footer' => true]
        );

        $request->session()->flash('success', 'Footer text info updated successfully!');
        return 'success';
    }


    public function quickLinks(Request $request)
    {
        // first, get the language info from db
        $language = Language::where('code', $request->language)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        // then, get the footer quick link info of that language from db
        $information['links'] = FooterQuickLink::where('language_id', $language->id)
            ->where('user_id', Auth::id())
            ->orderBy('id', 'desc')
            ->get();

        $information['userLanguages'] = Language::where('user_id', Auth::id())->get();

        return view('user.footer.quick_links', $information);
    }

    public function storeQuickLink(Request $request)
    {
        $rules = [
            'title' => 'required',
            'url' => 'required',
            'serial_number' => 'required',
            'user_language_id' => 'required',
        ];
        $message = [
            'title.required' => 'The title field is required',
            'url.required' => 'The url field is required',
            'serial_number.required' => 'The serial number field is required',
            'user_language_id.required' => 'The language field is required',
        ];

        $validator = Validator::make($request->all(), $rules, $message);

        if ($validator->fails()) {
            return Response::json([
                'errors' => $validator->getMessageBag()->toArray()
            ], 400);
        }

        FooterQuickLink::create($request->except('language_id', 'user_id') + [
            'language_id' => $request->user_language_id,
            'user_id' => Auth::id(),
        ]);

        $request->session()->flash('success', 'New quick link added successfully!');

        UserStep::updateOrCreate(
            ['user_id' => Auth::guard('web')->user()->id],
            ['contacts_social_info' => true]
        );

        return 'success';
    }

    public function updateQuickLink(Request $request)
    {
        $rules = [
            'title' => 'required',
            'url' => 'required',
            'serial_number' => 'required'
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            $errmsgs = $validator->getMessageBag()->add('error', 'true');
            return response()->json($validator->errors());
        }

        FooterQuickLink::where('user_id', Auth::user()->id)->where('id', $request->link_id)->firstOrFail()->update($request->all());

        $request->session()->flash('success', 'Quick link updated successfully!');

        UserStep::updateOrCreate(
            ['user_id' => Auth::guard('web')->user()->id],
            ['contacts_social_info' => true]
        );
        return 'success';
    }

    public function deleteQuickLink(Request $request)
    {
        FooterQuickLink::where('user_id', Auth::user()->id)->where('id', $request->link_id)->firstOrFail()->delete();

        $request->session()->flash('success', 'Quick link deleted successfully!');

        return redirect()->back();
    }

    public function FooterInfo_QuickLink(Request $request){

        // first, get the language info from db
        $language = Language::where('code', $request->language)->where('user_id', Auth::id())->firstOrFail();

        // then, get the footer quick link info of that language from db
        $information['links'] = FooterQuickLink::where('language_id', $language->id)->where('user_id', Auth::id())->orderBy('id', 'desc')->get();


        $information['userLanguages'] = Language::where('user_id', Auth::id())->get();

    }

    public function updateFooterInfo_QuickLink(Request $request, $language)
    {

        $lang = Language::where('code', $language)->where('user_id', Auth::id())->firstOrFail();
        $data = FooterText::firstOrNew(['language_id' => $lang->id, 'user_id' => Auth::id()]);
        $homePageText = HomePageText::where('user_id', Auth::id())->firstOrNew();

        $rules = [
            'about_company' => 'nullable',
            'copyright_text' => 'nullable',
            'useful_footer_links' => 'nullable|string|max:100',
            'contact_us_footer_links' => 'nullable|string|max:100',
        ];

        if (!$data->logo) {
            $rules['footer_logo'] = 'required|mimes:jpeg,jpg,png|max:1000';
        }

        $messages = [
            'footer_logo.required' => 'The footer logo field is required'
        ];

        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return response()->json([
                'error' => true,
                'messages' => $validator->errors()
            ]);
        }

        // Map 'footer_logo' to 'logo' before storing in the database
        if ($request->hasFile('footer_logo')) {
            $data->logo = Uploader::update_picture('assets/front/img/user/footer/', $request->file('footer_logo'), $data->logo);
        }

        if ($request->hasFile('bg_image')) {
            $data->bg_image = Uploader::update_picture('assets/front/img/user/footer/', $request->file('bg_image'), $data->bg_image);
        }

        $data->copyright_text = clean($request->copyright_text);
        $data->about_company = clean($request->about_company);
        $data->newsletter_text = clean($request->newsletter_text);
        if ($request->color) {
            $data->footer_color = $request->color;
        }
        $data->save();
        // Update HomePageText
        $homePageText->user_id = Auth::id();
        $homePageText->useful_footer_links = clean($request->useful_footer_links);
        $homePageText->contact_us_footer_links = clean($request->contact_us_footer_links);
        $homePageText->save();

        UserStep::updateOrCreate(['user_id' => Auth::id()], ['footer' => true]);

        $request->session()->flash('success', 'Footer text info updated successfully!');
        return 'success';
    }


}
