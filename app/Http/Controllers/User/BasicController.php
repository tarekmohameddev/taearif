<?php

namespace App\Http\Controllers\User;

use Response;
use App\Models\Timezone;
use App\Models\User\SEO;
use App\Models\UserStep;
use App\Models\User\Member;
use App\Models\User\Social;
use Illuminate\Http\Request;
use App\Models\User\Language;
use App\Http\Helpers\Uploader;
use App\Models\User\Portfolio;
use App\Models\User\FooterText;
use App\Models\User\HomeSection;
use App\Models\User\UserService;
use App\Models\User\WorkProcess;
use App\Models\User\BasicSetting;
use App\Models\User\HomePageText;
use Illuminate\Support\Facades\DB;
use Mews\Purifier\Facades\Purifier;
use App\Http\Controllers\Controller;
use App\Models\User\UserTestimonial;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use App\Http\Helpers\UserPermissionHelper;
use Illuminate\Support\Facades\Log;

class BasicController extends Controller
{

    public function themeVersion()
    {
        $data = BasicSetting::where('user_id', Auth::guard('web')->user()->id)->first();
        return view('user.settings.themes', ['data' => $data]);
    }

    public function updateThemeVersion(Request $request)
    {
        $rule = [
            'theme' => 'required'
        ];

        $validator = Validator::make($request->all(), $rule);

        if ($validator->fails()) {
            return Response::json([
                'errors' => $validator->getMessageBag()->toArray()
            ], 400);
        }
        $data = BasicSetting::where('user_id', Auth::guard('web')->user()->id)->first();
        $data->theme = $request->theme;
        $data->save();
        $request->session()->flash('success', 'Theme updated successfully!');

        return 'success';
    }

    public function favicon(Request $request)
    {
        $data['basic_setting'] = BasicSetting::where('user_id', Auth::guard('web')->user()->id)->first();

        return view('user.settings.favicon', $data);
    }
    public function generalSettings(Request $request)
    {
        $language = Language::where('user_id', Auth::guard('web')->user()->id)->where('code', $request->language)->firstOrFail();

        $data['timezones'] = Timezone::all();
        $data['data'] = BasicSetting::where('user_id', Auth::guard('web')->user()->id)
            ->first();
        $data['basic_setting'] = BasicSetting::where('user_id', Auth::guard('web')->user()->id)->first();


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

        //footer text
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
        // $information['data'] = FooterText::where('language_id', $lang->id)->where('user_id', Auth::id())->first();
        $data['footertext'] = FooterText::where('language_id', $lang->id)->where('user_id', Auth::id())->first();

        // socials
        $data['socials'] = Social::where('user_id', Auth::id())
            ->orderBy('id', 'DESC')
            ->get();


        return view('user.settings.general-settings', $data);
    }

    //
	public function updateAllSettings(Request $request, $language)
    {
        // dd($request->all());
        $user = Auth::guard('web')->user();

        $lang = Language::where('code', $language)
        ->where('user_id', $user->id)
        ->firstOrFail();


        $package = UserPermissionHelper::currentPackagePermission($user->id);
        if (!empty($user)) {
            $permissions = UserPermissionHelper::packagePermission($user->id);
            $permissions = json_decode($permissions, true);
        }

        // Validate Basic Settings Input
        $rules = [
            'website_title' => 'sometimes|required',
            'timezone'      => 'sometimes|required',
        ];

        // Validate the main logo file upload.
        if ($request->hasFile('website-logo')) {
            $rules['website-logo'] = [
                function ($attribute, $value, $fail) use ($request) {
                    $allowedExts = ['jpg', 'png', 'jpeg'];
                    $ext = $request->file('website-logo')->getClientOriginalExtension();
                    if (!in_array($ext, $allowedExts)) {
                        return $fail("Only png, jpg, jpeg images are allowed for logo.");
                    }
                }
            ];
        }


        if (!empty($permissions) && in_array('Ecommerce', $permissions)) {
            $rules['base_currency_symbol']        = 'sometimes|required';
            $rules['base_currency_symbol_position'] = 'sometimes|required';
            $rules['base_currency_text']            = 'sometimes|required';
            $rules['base_currency_text_position']   = 'sometimes|required';
            $rules['base_currency_rate']            = 'sometimes|required|numeric|min:0.00000001';
        }

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $validator->getMessageBag()->add('error', 'true');
            return response()->json($validator->errors());
        }

        // Update General (Basic) Settings section
        $bss = BasicSetting::firstOrNew(['user_id' => $user->id]);

        if ($request->has('website_title')) {
            $bss->website_title = $request->website_title;
        }
        if ($request->has('timezone')) {
            $bss->timezone = $request->timezone;
        }
        if ($request->has('email_verification_status')) {
            $bss->email_verification_status = $request->email_verification_status;
        }
        if ($request->has('base_color')) {
            $bss->base_color = $request->base_color;
        }
        if ($request->has('secondary_color')) {
            $bss->secondary_color = $request->secondary_color;
        }

        if (!empty($permissions) && in_array('Ecommerce', $permissions)) {
            if ($request->has('base_currency_symbol')) {
                $bss->base_currency_symbol = $request->base_currency_symbol;
            }
            if ($request->has('base_currency_symbol_position')) {
                $bss->base_currency_symbol_position = $request->base_currency_symbol_position;
            }
            if ($request->has('base_currency_text')) {
                $bss->base_currency_text = $request->base_currency_text;
            }
            if ($request->has('base_currency_text_position')) {
                $bss->base_currency_text_position = $request->base_currency_text_position;
            }
            if ($request->has('base_currency_rate')) {
                $bss->base_currency_rate = $request->base_currency_rate;
            }
        }

        // main "logo" upload.
        if ($request->hasFile('website-logo')) {
            try {
                // Delete old images if they exist.
                $imageFields = ['logo', 'preloader', 'favicon'];
                foreach ($imageFields as $field) {
                    if ($bss->$field) {
                        @unlink(public_path('assets/front/img/user/' . $bss->$field));
                    }
                }

                $file = $request->file('website-logo');
                $filename = uniqid() . '.' . $file->getClientOriginalExtension();
                $file->move(public_path('assets/front/img/user/'), $filename);

                // Set the same filename for all the related fields.
                foreach ($imageFields as $field) {
                    $bss->$field = $filename;
                }

                // Copy logo file to footer folder.
                $sourcePath = public_path('assets/front/img/user/' . $filename);
                $destinationPath = public_path('assets/front/img/user/footer/' . $filename);
                if (!file_exists(public_path('assets/front/img/user/footer/'))) {
                    mkdir(public_path('assets/front/img/user/footer/'), 0775, true);
                }
                copy($sourcePath, $destinationPath);


                $tempFooterText = FooterText::where('language_id', $lang->id)
                                             ->where('user_id', $user->id)
                                             ->first();
                if (!$tempFooterText) {
                    $tempFooterText = new FooterText;
                }
                $tempFooterText->logo = $filename;
                $tempFooterText->bg_image = $filename;
                $tempFooterText->language_id = $lang->id;
                $tempFooterText->user_id = $user->id;
                $tempFooterText->save();
            } catch (\Exception $e) {
                Session::flash('error', 'File upload failed. Please try again.');
                return back();
            }
        }
        $bss->save();

        // Update user steps.
        $steps = UserStep::firstOrCreate(
            ['user_id' => $user->id],
            [
                'logo_uploaded'      => false,
                'favicon_uploaded'   => false,
                'website_named'      => false,
                'homepage_updated'   => false,
            ]
        );
        $steps->update([
            'website_named'    => $request->has('website_title') ? true : $steps->website_named,
            'logo_uploaded'    => $request->hasFile('website-logo') ? true : $steps->logo_uploaded,
            'favicon_uploaded' => $request->hasFile('favicon') ? true : $steps->favicon_uploaded,
            'homepage_updated' => $request->hasFile('preloader') ? true : $steps->homepage_updated,
        ]);


        // for the about section.
        $aboutImageFilename = null;
        if ($request->hasFile('about_image')) {
            try {
                $file = $request->file('about_image');
                $aboutImageFilename = uniqid() . '.' . $file->getClientOriginalExtension();
                $file->move(public_path('assets/front/img/user/home_settings/'), $aboutImageFilename);
            } catch (\Exception $e) {
                \Log::error("About image upload failed: " . $e->getMessage());
            }
        }

        $aboutVideoImageFilename = null;
        if ($request->hasFile('about_video_image')) {
            try {
                $file = $request->file('about_video_image');
                $aboutVideoImageFilename = uniqid() . '.' . $file->getClientOriginalExtension();
                $file->move(public_path('assets/front/img/user/home_settings/'), $aboutVideoImageFilename);
            } catch (\Exception $e) {
            }
        }

        // Define the allowed fields for updating the HomePageText.
        $allowedFields = [
            'about_title',
            'about_content',
            'about_button_text',
            'about_button_url',
            'about_snd_button_text',
            'about_video_url',
            'about_snd_button_url'
        ];

        // Extract only the allowed fields from the request.
        $updateData = $request->only($allowedFields);

        // Add file upload fields if they exist.
        if ($aboutImageFilename) {
            $updateData['about_image'] = $aboutImageFilename;
        }

        if ($aboutVideoImageFilename) {
            $updateData['about_video_image'] = $aboutVideoImageFilename;
        }

        // Set additional fields for language and user.
        $updateData['language_id'] = $request->input('language_id');
        $updateData['user_id']     = $user->id;

        // Retrieve the HomePageText record by ID, or create a new instance if not found.
        $homePageText = HomePageText::find($request->input('id')) ?? new HomePageText;

        // Update the HomePageText record with the new data.
        $homePageText->update($updateData);

        // Log the allowed fields for debugging purposes.
        Log::info('Allowed HomePageText fields: ' . json_encode($allowedFields));



        // file uploads for footer fields.
        $footerLogoFilename = null;
        if ($request->hasFile('footer_logo')) {
            try {
                $file = $request->file('footer_logo');
                $footerLogoFilename = uniqid() . '.' . $file->getClientOriginalExtension();
                $file->move(public_path('assets/front/img/user/footer/'), $footerLogoFilename);
            } catch (\Exception $e) {
                \Log::error("Footer logo upload failed: " . $e->getMessage());
            }
        }

        $footerBgImageFilename = null;
        if ($request->hasFile('footer_bg_image')) {
            try {
                $file = $request->file('footer_bg_image');
                $footerBgImageFilename = uniqid() . '.' . $file->getClientOriginalExtension();
                $file->move(public_path('assets/front/img/user/footer/'), $footerBgImageFilename);
            } catch (\Exception $e) {
                \Log::error("Footer background image upload failed: " . $e->getMessage());
            }
        }

        $footerText = FooterText::where('language_id', $lang->id)
        ->where('user_id', $user->id)
        ->first();
        if (!$footerText) {
        $footerText = new FooterText;
        }

        $footerFields = [
        'footer_color',
        'about_company',
        'newsletter_text',
        'copyright_text'
        ];

        $footerUpdateData = $request->only($footerFields);

        // Add file upload data if available.
        // if ($footerLogoFilename) {
        // $footerUpdateData['logo'] = $footerLogoFilename;
        // }

        if (empty($footerUpdateData['logo']) && $request->has('existing_footer_logo')) {
            $footerUpdateData['logo'] = $request->input('existing_footer_logo');
        }

        //

        if ($footerBgImageFilename) {
        $footerUpdateData['bg_image'] = $footerBgImageFilename;
        }
        // Set the language and user IDs.
        $footerUpdateData['language_id'] = $lang->id;
        $footerUpdateData['user_id'] = $user->id;

        if ($footerText->exists) {
        $footerText->update($footerUpdateData);
        } else {
        $footerText->fill($footerUpdateData);
        $footerText->save();
        }

        // Social Links section:
        if ($request->has('social_links')) {
            if ($request->has('socialid')) {
                $socialId = $request->input('socialid');
                $socialLinkData = $request->input('social_links.0');

                $social = Social::where('user_id', Auth::id())
                                ->where('id', $socialId)
                                ->first();
                if ($social) {
                    $social->update([
                        'icon'          => $socialLinkData['icon'],
                        'url'           => $socialLinkData['url'],
                        'serial_number' => $socialLinkData['serial_number'],
                    ]);
                }
            } else {

                foreach ($request->input('social_links') as $socialData) {

                    if (!isset($socialData['serial_number']) || $socialData['serial_number'] === null) {
                        continue;
                    }

                    if (isset($socialData['id'])) {
                        $social = Social::where('user_id', Auth::id())
                                        ->where('id', $socialData['id'])
                                        ->first();
                        if ($social) {
                            $social->update([
                                'icon'          => $socialData['icon'],
                                'url'           => $socialData['url'],
                                'serial_number' => $socialData['serial_number'],
                            ]);
                        }
                    } else {
                        Social::create([
                            'user_id'       => Auth::id(),
                            'icon'          => $socialData['icon'],
                            'url'           => $socialData['url'],
                            'serial_number' => $socialData['serial_number'],
                        ]);
                    }
                }
            }
        }

        Session::flash('success', 'Settings updated successfully.');
        return back();
    }
    //

    public function updateInfo(Request $request)
    {
        $user = Auth::guard('web')->user();
        $package = UserPermissionHelper::currentPackagePermission($user->id);
        if (!empty($user)) {
            $permissions = UserPermissionHelper::packagePermission($user->id);
            $permissions = json_decode($permissions, true);
        }

        $rules = [];
        $rules['website_title'] = 'required';
        $rules['timezone'] = 'required';

        if (!empty($permissions) && in_array('Ecommerce', $permissions)) {
            $rules['base_currency_symbol'] = 'required';
            $rules['base_currency_symbol_position'] = 'required';
            $rules['base_currency_text'] = 'required';
            $rules['base_currency_text_position'] = 'required';
            $rules['base_currency_rate'] = 'required|numeric|min:0.00000001';
        }
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $validator->getMessageBag()->add('error', 'true');
            return response()->json($validator->errors());
        }

        BasicSetting::where('user_id', Auth::guard('web')->user()->id)->update([
            'website_title' => $request->website_title,
            'timezone' => $request->timezone,
            'email_verification_status' => $request->email_verification_status,
            'base_currency_symbol' => $request->base_currency_symbol,
            'base_currency_symbol_position' => $request->base_currency_symbol_position,
            'base_currency_text' => $request->base_currency_text,
            'base_currency_text_position' => $request->base_currency_text_position,
            'base_currency_rate' => $request->base_currency_rate,
        ]);

        $request->session()->flash('success', 'Information updated successfully!');
        $steps = UserStep::firstOrCreate(
            ['user_id' => Auth::guard('web')->user()->id],
            ['logo_uploaded' => false, 'favicon_uploaded' => false, 'website_named' => false, 'homepage_updated' => false] // Default values if record doesn't exist
        );
        $steps->update(['website_named' => true]);

        return 'success';
    }

    public function updatefav(Request $request)
    {
        $img = $request->file('favicon');
        $allowedExts = array('jpg', 'png', 'jpeg', 'ico');

        $rules = [
            'favicon' => [
                function ($attribute, $value, $fail) use ($img, $allowedExts) {
                    if (!empty($img)) {
                        $ext = $img->getClientOriginalExtension();
                        if (!in_array($ext, $allowedExts)) {
                            return $fail("Only png, jpg, jpeg, ico image is allowed");
                        }
                    }
                },
            ],
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $validator->getMessageBag()->add('error', 'true');
            return response()->json(['errors' => $validator->errors(), 'id' => 'favicon']);
        }

        if ($request->hasFile('favicon')) {
            $filename = uniqid() . '.' . $img->getClientOriginalExtension();
            $img->move(public_path('assets/front/img/user/'), $filename);
            $bss = BasicSetting::where('user_id', Auth::guard('web')->user()->id)->first();
            if (!is_null($bss)) {
                if ($bss->favicon) {
                    @unlink(public_path('assets/front/img/user/' . $bss->favicon));
                }
                $bss->favicon = $filename;
                $bss->user_id = Auth::guard('web')->user()->id;
                $bss->save();
            } else {
                $bs = new BasicSetting();
                $bs->favicon = $filename;
                $bs->user_id = Auth::guard('web')->user()->id;
                $bs->save();
            }
        }

        $steps = UserStep::firstOrCreate(
            ['user_id' => Auth::guard('web')->user()->id],
            ['logo_uploaded' => false, 'favicon_uploaded' => false, 'website_named' => false, 'homepage_updated' => false] // Default values if record doesn't exist
        );
        $steps->update(['favicon_uploaded' => true]);

        Session::flash('success', 'Favicon update successfully.');
        return "success";
    }

    public function logo(Request $request)
    {
        $data['basic_setting'] = BasicSetting::where('user_id', Auth::guard('web')->user()->id)->first();
        return view('user.settings.logo', $data);
    }

    public function updatelogo(Request $request)
    {
        $img = $request->file('file');
        $allowedExts = array('jpg', 'png', 'jpeg');

        $rules = [
            'file' => [
                function ($attribute, $value, $fail) use ($img, $allowedExts) {
                    if (!empty($img)) {
                        $ext = $img->getClientOriginalExtension();
                        if (!in_array($ext, $allowedExts)) {
                            return $fail("Only png, jpg, jpeg image is allowed");
                        }
                    }
                },
            ],
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $validator->getMessageBag()->add('error', 'true');
            return response()->json(['errors' => $validator->errors(), 'id' => 'logo']);
        }

        if ($request->hasFile('file')) {
            $bss = BasicSetting::where('user_id', Auth::guard('web')->user()->id)->first();
            $filename = uniqid() . '.' . $img->getClientOriginalExtension();
            $img->move(public_path('assets/front/img/user/'), $filename);
            $bss = BasicSetting::where('user_id', Auth::guard('web')->user()->id)->first();
            if (!is_null($bss)) {
                if ($bss->logo) {
                    @unlink(public_path('assets/front/img/user/' . $bss->logo));
                }
                $bss->logo = $filename;
                $bss->user_id = Auth::guard('web')->user()->id;
                $bss->save();
            } else {
                $bs = new BasicSetting();
                $bs->logo = $filename;
                $bs->user_id = Auth::guard('web')->user()->id;
                $bs->save();
            }
        }

        $steps = UserStep::firstOrCreate(
            ['user_id' => Auth::guard('web')->user()->id],
            ['logo_uploaded' => false, 'favicon_uploaded' => false, 'website_named' => false, 'homepage_updated' => false] // Default values if record doesn't exist
        );
        $steps->update(['logo_uploaded' => true]);

        Session::flash('success', 'Logo update successfully.');
        return back();
    }

    public function breadcrumb(Request $request)
    {
        $data['basic_setting'] = BasicSetting::where('user_id', Auth::guard('web')->user()->id)->first();
        return view('user.settings.breadcrumb', $data);
    }


    public function updateBreadcrumb(Request $request)
    {

        $userId = Auth::guard('web')->user()->id;

        $rules = [
            'breadcrumb' => 'mimes:jpg,jpeg,png',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors(),
                'id' => 'breadcrumb'
            ]);
        }

        if ($request->hasFile('breadcrumb')) {
            $img = $request->file('breadcrumb');
            $filename = uniqid() . '.' . $img->getClientOriginalExtension();
            $uploadPath = public_path('assets/front/img/user/');

            $img->move($uploadPath, $filename);

            $bss = BasicSetting::where('user_id', $userId)->first();

            if ($bss) {
                // Delete old file if exists
                if (!empty($bss->breadcrumb) && file_exists($uploadPath . $bss->breadcrumb)) {
                    unlink($uploadPath . $bss->breadcrumb);
                }
                $bss->breadcrumb = $filename;
                $bss->save();
            } else {
                BasicSetting::updateOrCreate([
                    'user_id' => $userId,
                    'breadcrumb' => $filename
                ]);
            }
        }

        // Update User Step
        UserStep::updateOrCreate(
            ['user_id' => $userId],
            ['sub_pages_upper_image' => true]
        );

        return redirect()->back()->with('success', 'Breadcrumb updated successfully.');
    }


    public function preloader(Request $request)
    {
        $data['basic_setting'] = BasicSetting::where('user_id', Auth::guard('web')->user()->id)->first();
        return view('user.settings.preloader', $data);
    }

    public function updatepreloader(Request $request)
    {
        $img = $request->file('file');
        $allowedExts = array('jpg', 'png', 'jpeg', 'gif');

        $rules = [
            'file' => [
                function ($attribute, $value, $fail) use ($img, $allowedExts) {
                    if (!empty($img)) {
                        $ext = $img->getClientOriginalExtension();
                        if (!in_array($ext, $allowedExts)) {
                            return $fail("Only png, jpg, jpeg, gif image is allowed");
                        }
                    }
                },
            ],
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $validator->getMessageBag()->add('error', 'true');
            return response()->json(['errors' => $validator->errors(), 'id' => 'preloader']);
        }

        if ($request->hasFile('file')) {
            $bss = BasicSetting::where('user_id', Auth::guard('web')->user()->id)->first();
            $filename = uniqid() . '.' . $img->getClientOriginalExtension();
            $img->move(public_path('assets/front/img/user/'), $filename);
            $bss = BasicSetting::where('user_id', Auth::guard('web')->user()->id)->first();
            if (!is_null($bss)) {
                @unlink(public_path('assets/front/img/user/' . $bss->preloader));
                $bss->preloader = $filename;
                $bss->user_id = Auth::guard('web')->user()->id;
                $bss->save();
            } else {
                $bs = new BasicSetting();
                $bs->preloader = $filename;
                $bs->user_id = Auth::guard('web')->user()->id;
                $bs->save();
            }
        }

        Session::flash('success', 'Preloader updated successfully.');
        $steps = UserStep::firstOrCreate(
            ['user_id' => Auth::guard('web')->user()->id],
            ['logo_uploaded' => false, 'favicon_uploaded' => false, 'website_named' => false, 'homepage_updated' => false] // Default values if record doesn't exist
        );
        $steps->update(['homepage_updated' => true]);
        return back();
    }

    public function homePageTextEdit(Request $request)
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

        $data['testimonials'] = UserTestimonial::where([
            ['lang_id', '=', $language->id],
            ['user_id', '=', Auth::id()],
        ])
            ->orderBy('id', 'DESC')
            ->get();

        $data['services'] = UserService::where([
            ['lang_id', '=', $language->id],
            ['user_id', '=', Auth::id()],
        ])
            ->orderBy('id', 'DESC')
            ->get();

            $data['portfolios'] = Portfolio::where([
                ['language_id', '=', $language->id],
                ['user_id', '=', Auth::id()],
            ])
                ->orderBy('id', 'DESC')
                ->get();

        return view('user.home.edit', $data);
    }

    public function homePageTextUpdate(Request $request)
    {
        $homeText = HomePageText::query()->where('language_id', $request->language_id)->where('user_id', Auth::guard('web')->user()->id)->firstOrFail();
        foreach ($request->types as $key => $type) {
            if ($type == 'faq_section_image' || $type == 'testimonial_image' || $type == 'newsletter_image' || $type == 'newsletter_snd_image' || $type == 'about_video_image' || $type == 'counter_section_image' || $type == 'contact_section_image') {
                continue;
            }
            $homeText->$type = Purifier::clean($request[$type]);
        }
        // dd($request->skills_image);

        if ($request->hasFile('skills_image')) {
            // dd('waid');
            $skillsImage = uniqid() . '.' . $request->file('skills_image')->getClientOriginalExtension();
            $request->file('skills_image')->move(public_path('assets/front/img/user/home_settings/'), $skillsImage);
            @unlink(public_path('assets/front/img/user/home_settings/' . $homeText->skills_image));
            $homeText->skills_image = $skillsImage;
        }
        if ($request->hasFile('newsletter_image')) {
            $newsletterImage = uniqid() . '.' . $request->file('newsletter_image')->getClientOriginalExtension();
            $request->file('newsletter_image')->move(public_path('assets/front/img/user/home_settings/'), $newsletterImage);
            @unlink(public_path('assets/front/img/user/home_settings/' . $homeText->newsletter_image));
            $homeText->newsletter_image = $newsletterImage;
        }
        if ($request->hasFile('newsletter_snd_image')) {
            $newsletterImage2 = uniqid() . '.' . $request->file('newsletter_snd_image')->getClientOriginalExtension();
            $request->file('newsletter_snd_image')->move(public_path('assets/front/img/user/home_settings/'), $newsletterImage2);
            @unlink(public_path('assets/front/img/user/home_settings/' . $homeText->newsletter_snd_image));
            $homeText->newsletter_snd_image = $newsletterImage2;
        }
        if ($request->hasFile('testimonial_image')) {
            $testimonialImage = uniqid() . '.' . $request->file('testimonial_image')->getClientOriginalExtension();
            $request->file('testimonial_image')->move(public_path('assets/front/img/user/home_settings/'), $testimonialImage);
            @unlink(public_path('assets/front/img/user/home_settings/' . $homeText->testimonial_image));
            $homeText->testimonial_image = $testimonialImage;
        }
        if ($request->hasFile('about_video_image')) {
            $aboutVideoImage = uniqid() . '.' . $request->file('about_video_image')->getClientOriginalExtension();
            $request->file('about_video_image')->move(public_path('assets/front/img/user/home_settings/'), $aboutVideoImage);
            @unlink(public_path('assets/front/img/user/home_settings/' . $homeText->about_video_image));
            $homeText->about_video_image = $aboutVideoImage;
        }
        if ($request->hasFile('faq_section_image')) {
            $faqSectionImage = uniqid() . '.' . $request->file('faq_section_image')->getClientOriginalExtension();
            $request->file('faq_section_image')->move(public_path('assets/front/img/user/home_settings/'), $faqSectionImage);
            @unlink(public_path('assets/front/img/user/home_settings/' . $homeText->faq_section_image));
            $homeText->faq_section_image = $faqSectionImage;
        }
        if ($request->hasFile('counter_section_image')) {
            $counterSectionImg = uniqid() . '.' . $request->file('counter_section_image')->getClientOriginalExtension();
            $request->file('counter_section_image')->move(public_path('assets/front/img/user/home_settings/'), $counterSectionImg);
            @unlink(public_path('assets/front/img/user/home_settings/' . $homeText->counter_section_image));
            $homeText->counter_section_image = $counterSectionImg;
        }
        if ($request->hasFile('contact_section_image')) {
            $contactSecImage = uniqid() . '.' . $request->file('contact_section_image')->getClientOriginalExtension();
            $request->file('contact_section_image')->move(public_path('assets/front/img/user/home_settings/'), $contactSecImage);
            @unlink(public_path('assets/front/img/user/home_settings/' . $homeText->contact_section_image));
            $homeText->contact_section_image = $contactSecImage;
        }
        $homeText->user_id = Auth::guard('web')->user()->id;
        $homeText->language_id = $request->language_id;
        $homeText->save();

        $steps = UserStep::firstOrCreate(
            ['user_id' => Auth::guard('web')->user()->id],
            ['logo_uploaded' => false, 'favicon_uploaded' => false, 'website_named' => false, 'homepage_updated' => false] // Default values if record doesn't exist
        );
        $steps->update(['homepage_updated' => true]);

        Session::flash('success', 'Home page text updated successfully.');
        return "success";
    }

    public function seo(Request $request)
    {
        // first, get the language info from db
        $language = Language::where('code', $request->language)->where('user_id', Auth::guard('web')->user()->id)->firstOrFail();
        $langId = $language->id;

        // then, get the seo info of that language from db
        $seo = SEO::where('language_id', $langId)->where('user_id', Auth::guard('web')->user()->id);

        if ($seo->count() == 0) {
            // if seo info of that language does not exist then create a new one
            SEO::create($request->except('language_id', 'user_id') + [
                'language_id' => $langId,
                'user_id' => Auth::guard('web')->user()->id
            ]);
        }

        $information['language'] = $language;

        // then, get the seo info of that language from db
        $information['data'] = $seo->first();

        // get all the languages from db
        $information['langs'] = Language::where('user_id', Auth::guard('web')->user()->id)->get();

        return view('user.settings.seo', $information);
    }

    public function updateSEO(Request $request)
    {
        // first, get the language info from db
        $language = Language::where('code', $request->language)->where('user_id', Auth::guard('web')->user()->id)->first();
        $langId = $language->id;

        // then, get the seo info of that language from db
        $seo = SEO::where('language_id', $langId)->where('user_id', Auth::guard('web')->user()->id)->first();

        // else update the existing seo info of that language
        $seo->update($request->all());

        $request->session()->flash('success', 'SEO Informations updated successfully!');

        return redirect()->back();
    }
    public function cookieAlert(Request $request)
    {
        $userId = Auth::guard('web')->user()->id;
        $lang = Language::query()
            ->where('code', $request->language)
            ->where('user_id', $userId)
            ->first();

        $data['lang_id'] = $lang->id;
        $data['abe'] = BasicSetting::where('user_id', $userId)->first();
        return view('user.settings.cookie', $data);
    }

    public function updatecookie(Request $request, $langid)
    {
        $userId = Auth::guard('web')->user()->id;
        $request->validate([
            'cookie_alert_status' => 'required',
            'cookie_alert_text' => 'required',
            'cookie_alert_button_text' => 'required|max:25',
        ]);

        $be = BasicSetting::query()
            ->where('user_id', $userId)
            ->first();
        $be->cookie_alert_status = $request->cookie_alert_status;
        $be->cookie_alert_text = Purifier::clean($request->cookie_alert_text, 'youtube');
        $be->cookie_alert_button_text = $request->cookie_alert_button_text;
        $be->save();

        Session::flash('success', 'Cookie alert updated successfully!');
        return back();
    }
    public function teamSection(Request $request)
    {
        // first, get the language info from db
        $language = Language::where('code', $request->language)->where('user_id', Auth::guard('web')->user()->id)->first();
        $information['language'] = $language;

        // then, get the testimonial section heading info of that language from db
        $information['data'] = HomePageText::where('language_id', $language->id)->where('user_id', Auth::guard('web')->user()->id)->first();

        // also, get the testimonials of that language from db
        $information['memberInfos'] = Member::where('language_id', $language->id)
            ->where('user_id', Auth::guard('web')->user()->id)
            ->orderby('id', 'desc')
            ->get();

        // get all the languages from db
        $information['langs'] = Language::where('code', $request->language)->where('user_id', Auth::guard('web')->user()->id)->get();

        return view('user.team_section.index', $information);
    }
    public function updateTeamSection(Request $request)
    {
        $lang = Language::where('code', $request->language)->where('user_id', Auth::guard('web')->user()->id)->first();
        $data = HomePageText::where('language_id', $lang->id)->where('user_id', Auth::guard('web')->user()->id)->first();
        $data->team_section_title = $request->team_section_title;
        $data->team_section_subtitle = $request->team_section_subtitle;
        $data->save();
        $request->session()->flash('success', 'Team section updated successfully!');

        $steps = UserStep::firstOrCreate(
            ['user_id' => Auth::guard('web')->user()->id],
            ['logo_uploaded' => false, 'favicon_uploaded' => false, 'website_named' => false, 'homepage_updated' => false] // Default values if record doesn't exist
        );
        $steps->update(['homepage_updated' => true]);

        return redirect()->back();
    }

    public function homePageAbout(Request $request)
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
        return view('user.about_section', $data);
    }

    public function homePageAboutUpdate(Request $request)
    {
        $rules = [
            'about_button_text' => 'nullable|max:50',
            'about_snd_button_text' => 'nullable|max:50',
            'about_button_url' => 'nullable|max:255',
            'about_snd_button_url' => 'nullable|max:255',
            'years_of_expricence' => 'nullable|integer|min:0',
        ];
        $messages = [
            'about_button_text.max' => 'Button text field can contain maximum 50 characters',
            'about_snd_button_text.max' => 'Secound Button text field can contain maximum 50 characters',
            'about_button_url.max' => 'Button URL field can contain maximum 255 characters',
            'about_snd_button_url.max' => 'Secound Button URL field can contain maximum 255 characters'
        ];
        $request->validate($rules, $messages);
        $homeText = HomePageText::query()->where('language_id', $request->language_id)->where('user_id', Auth::guard('web')->user()->id)->firstOrFail();

        foreach ($request->types as $key => $type) {
            if ($type == 'about_image' || $type == 'about_image_two' || $type == 'about_video_url' || $type == 'about_video_image') {
                continue;
            }

            if ($type == 'years_of_expricence') {
                $homeText->$type = ($request[$type] === '' || $request[$type] === null) ? null : (int) $request[$type];
            } else {
                $homeText->$type = Purifier::clean($request[$type]);
            }
        }


        if ($request->hasFile('about_image')) {
            $aboutImage = uniqid() . '.' . $request->file('about_image')->getClientOriginalExtension();
            $request->file('about_image')->move(public_path('assets/front/img/user/home_settings/'), $aboutImage);
            @unlink(public_path('assets/front/img/user/home_settings/' . $homeText->about_image));
            $homeText->about_image = $aboutImage;
        }
        if ($request->hasFile('about_image_two')) {
            $aboutImage = uniqid() . '.' . $request->file('about_image_two')->getClientOriginalExtension();
            $request->file('about_image_two')->move(public_path('assets/front/img/user/home_settings/'), $aboutImage);
            @unlink(public_path('assets/front/img/user/home_settings/' . $homeText->about_image_two));
            $homeText->about_image_two = $aboutImage;
        }
        if ($request->hasFile('about_video_image')) {
            $aboutVidImg = uniqid() . '.' . $request->file('about_video_image')->getClientOriginalExtension();
            $request->file('about_video_image')->move(public_path('assets/front/img/user/home_settings/'), $aboutVidImg);
            @unlink(public_path('assets/front/img/user/home_settings/' . $homeText->about_video_image));
            $homeText->about_video_image = $aboutVidImg;
        }
        if ($request->filled('about_video_url')) {
            $videoLink = $request->about_video_url;
            if (strpos($videoLink, "&") != false) {
                $videoLink = substr($videoLink, 0, strpos($videoLink, "&"));
            }
        } else {
            $videoLink = NULL;
        }
        $homeText->about_video_url = $videoLink;
        $homeText->user_id = Auth::guard('web')->user()->id;
        $homeText->language_id = $request->language_id;
        $homeText->save();
        Session::flash('success', 'About section updated successfully.');

        $steps = UserStep::firstOrCreate(
            ['user_id' => Auth::guard('web')->user()->id],
            ['logo_uploaded' => false, 'favicon_uploaded' => false, 'website_named' => false, 'homepage_updated' => false] // Default values if record doesn't exist
        );
        $steps->update(['homepage_updated' => true]);

        // UserStep::updateOrCreate(
        //     ['user_id' => Auth::guard('web')->user()->id],
        //     ['homepage_updated' => true]
        // );

        return "success";
    }

    public function homePageVideo(Request $request)
    {
        // first, get the language info from db
        $language = Language::where('user_id', Auth::guard('web')->user()->id)->where('is_default', 1)->first();
        // then, get the testimonial section heading info of that language from db
        $information['data'] = HomePageText::where('language_id', $language->id)->where('user_id', Auth::guard('web')->user()->id)->first();
        return view('user.video_section', $information);
    }

    public function homePageUpdateVideo(Request $request)
    {
        $lang = Language::where('user_id', Auth::guard('web')->user()->id)->where('is_default', 1)->first();
        $data = HomePageText::where('language_id', $lang->id)->where('user_id', Auth::guard('web')->user()->id)->first();
        if (empty($data)) {
            $data = HomePageText::create([
                'language_id' => $lang->id,
                'user_id' => Auth::guard('web')->user()->id
            ]);
        }
        if (empty($data->video_section_image) && !$request->hasFile('video_section_image')) {
            $rules = [
                'video_section_image' => 'required|mimes:jpeg,jpg,png|max:1000',
            ];
            $messages = [
                'video_section_image.required' => 'Video Section Background Image required',
                'video_section_image.mimes' => 'Image type must should - jpeg, jpg, png',
                'video_section_image.max' => 'Image size should maximum 1 MB'
            ];
            $request->validate($rules, $messages);
        }
        $request['image_name'] = $data->video_section_image;
        if ($request->hasFile('video_section_image')) {
            $request['image_name'] = Uploader::update_picture('assets/front/img/user/home_settings/', $request->file('video_section_image'), $data->video_section_image);
        }
        $videoLink = $request->video_section_url;
        if (strpos($videoLink, "&") != false) {
            $videoLink = substr($videoLink, 0, strpos($videoLink, "&"));
            $request['video_section_url'] = $videoLink;
        }
        $data->update($request->except(['video_section_image', 'video_section_text']) + [
            'video_section_image' => $request->image_name,
            'video_section_text' => clean($request->video_section_text),
        ]);
        $request->session()->flash('success', 'Video section updated successfully!');
        $steps = UserStep::firstOrCreate(
            ['user_id' => Auth::guard('web')->user()->id],
            ['logo_uploaded' => false, 'favicon_uploaded' => false, 'website_named' => false, 'homepage_updated' => false] // Default values if record doesn't exist
        );
        $steps->update(['homepage_updated' => true]);
        return redirect()->back();
    }
    public function whyChooseUsSection(Request $request)
    {
        // first, get the language info from db
        $language = Language::where('code', $request->language)->where('user_id', Auth::guard('web')->user()->id)->first();
        // then, get the blog section heading info of that language from db
        $information['data'] = HomePageText::where('language_id', $language->id)->first();

        $userBs = BasicSetting::where('user_id', Auth::guard('web')->user()->id)->select('theme')->first();
        if ($userBs->theme == 'home_nine') {
            $information['chooseUsItems'] = DB::table('user_choose_us_items')->where('user_id', Auth::guard('web')->user()->id)->where('language_id', $language->id)->orderBy('serial_number')->get();
        }

        return view("user.home.why_choose_us_section", $information);
    }

    //

    public function updateWhyChooseUsSection(Request $request, $language)
    {
        \Log::info('Request data:', $request->all());

        $rules = [
            'why_choose_us_section_title' => 'nullable|max:255',
            'why_choose_us_section_subtitle' => 'nullable|max:255',
            'why_choose_us_section_text' => 'nullable',
            'why_choose_us_section_button_text' => 'nullable|max:255',
            'why_choose_us_section_button_url' => 'nullable|max:255',
            'why_choose_us_section_image' => 'nullable|mimes:jpeg,jpg,png',
            'why_choose_us_section_image_two' => 'nullable|mimes:jpeg,jpg,png',
        ];

        $messages = [
            'why_choose_us_section_title.max' => 'The title field can contain maximum 255 characters',
            'why_choose_us_section_subtitle.max' => 'The subtitle field can contain maximum 255 characters',
            'why_choose_us_section_button_text.max' => 'The button name field can contain maximum 255 characters',
            'why_choose_us_section_button_url.max' => 'The button url field can contain maximum 255 characters',
        ];

        $lang = Language::where('code', $language)->where('user_id', Auth::guard('web')->user()->id)->firstOrFail();
        $userBs = BasicSetting::where('user_id', Auth::guard('web')->user()->id)->firstOrFail();
        $data = HomePageText::where('language_id', $lang->id)->where('user_id', Auth::guard('web')->user()->id)->firstOrFail();

        if ($userBs->theme === 'home_three') {
            $rules['why_choose_us_section_video_url'] = 'nullable|max:255';
            $rules['why_choose_us_section_video_image'] = 'nullable|mimes:jpeg,jpg,png';
        }

        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), $rules, $messages);
        if ($validator->fails()) {
            \Log::info('Validation failed:', $validator->errors()->toArray());
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $image_name = $data->why_choose_us_section_image;
        $image_name2 = $data->why_choose_us_section_image_two;
        $video_image_name = $data->why_choose_us_section_video_image;

        if ($request->hasFile('why_choose_us_section_image')) {
            $image_name = Uploader::update_picture('assets/front/img/user/home_settings/', $request->file('why_choose_us_section_image'), $data->why_choose_us_section_image);
            \Log::info('Image 1 updated:', ['name' => $image_name]);
        } else {
            \Log::info('No file uploaded for why_choose_us_section_image');
        }

        if ($request->hasFile('why_choose_us_section_image_two')) {
            $image_name2 = Uploader::update_picture('assets/front/img/user/home_settings/', $request->file('why_choose_us_section_image_two'), $data->why_choose_us_section_image_two);
            \Log::info('Image 2 updated:', ['name' => $image_name2]);
        } else {
            \Log::info('No file uploaded for why_choose_us_section_image_two');
        }

        if ($userBs->theme === 'home_three' && $request->hasFile('why_choose_us_section_video_image')) {
            $video_image_name = Uploader::update_picture('assets/front/img/user/home_settings/', $request->file('why_choose_us_section_video_image'), $data->why_choose_us_section_video_image);
            \Log::info('Video image updated:', ['name' => $video_image_name]);
        }

        $updateData = [
            'why_choose_us_section_title' => $request->why_choose_us_section_title,
            'why_choose_us_section_subtitle' => $request->why_choose_us_section_subtitle,
            'why_choose_us_section_text' => clean($request->why_choose_us_section_text),
            'why_choose_us_section_button_text' => $request->why_choose_us_section_button_text,
            'why_choose_us_section_button_url' => $request->why_choose_us_section_button_url,
            'why_choose_us_section_image' => $image_name,
            'why_choose_us_section_image_two' => $image_name2,
            'why_choose_us_section_video_image' => $video_image_name,
            'why_choose_us_section_video_url' => $request->why_choose_us_section_video_url
                ? (strpos($request->why_choose_us_section_video_url, "&") !== false
                    ? substr($request->why_choose_us_section_video_url, 0, strpos($request->why_choose_us_section_video_url, "&"))
                    : $request->why_choose_us_section_video_url)
                : null,
        ];

        \Log::info('Data to update:', $updateData);

        $data->update($updateData);

        $request->session()->flash('success', 'Why choose us section updated successfully!');

        UserStep::updateOrCreate(
            ['user_id' => Auth::guard('web')->user()->id],
            ['user_whychooseus' => true]
        );

        \Log::info('Updated data:', $data->toArray());

        return redirect()->back();
    }

    //
    public function whyChooseUsItemStore(Request $request)
    {
        Log::info($request->all());
        $request->validate([
            'language_id' => 'required',
            // 'icon' => 'required',
            'title' => 'required',
            'content' => 'required',
            'serial_number' => 'required'
        ], [
            'language_id.required' => 'The language field is required.'
        ]);

        try {
            DB::table('user_choose_us_items')->insert($request->except('_token') + [
                'user_id' => Auth::guard('web')->user()->id
            ]);
            session()->flash('success', 'Why choose us item store successfully!');
            // return response()->json(['status' => 'success'], 200);
            return 'success';
        } catch (\Exception $e) {
            session()->flash('warning', $e->getMessage());
            return 'success';
        }
    }

    public function whyChooseUsItemUpdate(Request $request)
    {
        $request->validate([
            // 'icon' => 'required',
            'title' => 'required',
            'content' => 'required',
            'serial_number' => 'required'
        ], [
            'language_id.required' => 'The language field is required.'
        ]);

        try {
            DB::table('user_choose_us_items')->where('id', $request->id)->update($request->except('_token', 'id') + [
                'user_id' => Auth::guard('web')->user()->id
            ]);
            session()->flash('success', 'Why choose us item update successfully!');

            UserStep::updateOrCreate(
                ['user_id' => Auth::guard('web')->user()->id],
                ['user_whychooseus' => true]
            );

            return 'success';
        } catch (\Exception $e) {
            session()->flash('warning', $e->getMessage());
            return redirect()->back();
        }
    }
    public function whyChooseUsItemDelete(Request $request)
    {
        DB::table('user_choose_us_items')->where('id', $request->id)->delete();
        session()->flash('success', 'Why choose us item Delete successfully!');
        return redirect()->back();
        // return 'success';
    }
    public function sections(Request $request)
    {
        $data['sections'] = HomeSection::where('user_id', Auth::guard('web')->user()->id)->first();

        return view('user.sections', $data);
    }

    public function updateSection(Request $request)
    {
        $fields = $request->except('_token');
        $sections = HomeSection::where('user_id', Auth::guard('web')->user()->id)->first();
        if (is_null($sections)) {
            $sections = new HomeSection;
            $sections->user_id = Auth::guard('web')->user()->id;
        }

        foreach ($fields as $key => $value) {
            if ($request->has("$key")) {
                $sections["$key"] = $value;
            }
        }
        $sections->save();

        Session::flash('success', 'Sections customized successfully!');
        return back();
    }

    public function workProcessSection(Request $request)
    {
        // first, get the language info from db
        $language = Language::where('code', $request->language)->where('user_id', Auth::guard('web')->user()->id)->first();
        // then, get the blog section heading info of that language from db
        $information['data'] = HomePageText::where('language_id', $language->id)->where('user_id', Auth::guard('web')->user()->id)->first();
        $information['workProcessInfos'] = WorkProcess::where('language_id', $language->id)->orderby('id', 'desc')->get();
        return view('user.home.work_process_section.index', $information);
    }

    public function updateWorkProcessSection(Request $request)
    {
        $request->validate([
            'work_process_section_title' => 'nullable|max:255',
            'work_process_section_subtitle' => 'nullable|max:255',
            'work_process_section_text' => 'nullable',
        ], [
            'work_process_section_title.required' => 'The title field cannot contain more than 255 characters',
            'work_process_section_subtitle.required' => 'The subtitle field cannot contain more than 255 characters',
            'work_process_section_video_img.required' => 'The video image field is required',
        ]);

        $lang = Language::where('code', $request->language)->where('user_id', Auth::guard('web')->user()->id)->first();
        $data = HomePageText::where('language_id', $lang->id)->where('user_id', Auth::guard('web')->user()->id)->first();
        if (empty($data)) {
            $data = new HomePageText;
        }
        $userBs = BasicSetting::where('user_id', Auth::guard('web')->user()->id)->first();

        if ($userBs->theme === 'home_two' || $userBs->theme === 'home_four' || $userBs->theme === 'home_five' || $userBs->theme === 'home_six') {
            if (empty($data->work_process_section_img) && !$request->hasFile('work_process_section_img')) {
                $request->validate(
                    ['work_process_section_img' => 'nullable|mimes:jpeg,jpg,png|max:1000']
                );
            }
            if (empty($data->work_process_section_video_img) && !$request->hasFile('work_process_section_video_img')) {
                $request->validate(
                    ['work_process_section_video_img' => 'nullable|mimes:jpeg,jpg,png|max:1000']
                );
            }
            if (!$request->hasFile('work_process_section_img')) {
                $request['image_name'] = $data->work_process_section_img;
            }
            if (!$request->hasFile('work_process_section_video_img')) {
                $request['video_image_name'] = $data->work_process_section_video_img ?? null;
            }
            if ($request->hasFile('work_process_section_img')) {
                $request['image_name'] = Uploader::update_picture('assets/front/img/work_process/', $request->file('work_process_section_img'), $data->work_process_section_img ?? '');
            }
            if ($request->hasFile('work_process_section_video_img')) {
                $request['video_image_name'] = Uploader::update_picture('assets/front/img/work_process/', $request->file('work_process_section_video_img'), $data->work_process_section_video_img);
            }
            $data->work_process_section_img = $request->image_name;
            $data->work_process_section_video_img = $request->video_image_name;
            $data->work_process_section_video_url = $request->work_process_section_video_url;
            $data->user_id = Auth::guard('web')->user()->id;
            $data->language_id = $lang->id;
            $data->save();
        }
        $videoLink = $request->work_process_section_video_url;
        if (!empty($videoLink) && (strpos($videoLink, "&") != false)) {
            $videoLink = substr($videoLink, 0, strpos($videoLink, "&"));
        }
        $data->update($request->except(['work_process_section_text', 'work_process_section_img', 'work_process_section_video_img', 'work_process_section_video_url']) + [
            'work_process_section_text' => clean($request->work_process_section_text),
            'work_process_section_video_url' => $videoLink
        ]);
        $request->session()->flash('success', 'Work section updated successfully!');
        return redirect()->back();
    }

    public function plugins()
    {
        $data = BasicSetting::where('user_id', Auth::guard('web')->user()->id)
            ->select('is_recaptcha', 'google_recaptcha_site_key', 'google_recaptcha_secret_key', 'whatsapp_status', 'whatsapp_number', 'whatsapp_header_title', 'whatsapp_popup_status', 'whatsapp_popup_message', 'analytics_status', 'measurement_id', 'disqus_status', 'disqus_short_name', 'pixel_status', 'pixel_id', 'tawkto_status', 'tawkto_direct_chat_link')
            ->first();
        return view('user.settings.plugins', compact('data'));
    }

    public function updateRecaptcha(Request $request)
    {

        $rules = [
            'is_recaptcha' => 'required',
            'google_recaptcha_site_key' => 'required',
            'google_recaptcha_secret_key' => 'required'
        ];

        $request->validate($rules);

        BasicSetting::where('user_id', Auth::guard('web')->user()->id)->update(
            [
                'is_recaptcha' => $request->is_recaptcha,
                'google_recaptcha_site_key' => $request->google_recaptcha_site_key,
                'google_recaptcha_secret_key' => $request->google_recaptcha_secret_key,
            ]
        );

        $request->session()->flash('success', 'Recaptcha info updated successfully!');

        return back();
    }


    public function updateAnalytics(Request $request)
    {
        $rules = [
            'analytics_status' => 'required',
            'measurement_id' => 'required'
        ];

        $request->validate($rules);

        BasicSetting::where('user_id', Auth::guard('web')->user()->id)->update(
            [
                'analytics_status' => $request->analytics_status,
                'measurement_id' => $request->measurement_id
            ]
        );

        $request->session()->flash('success', 'Analytics info updated successfully!');

        return back();
    }

    public function updateWhatsApp(Request $request)
    {
        $rules = [
            'whatsapp_status' => 'required',
            'whatsapp_number' => 'required',
            'whatsapp_header_title' => 'required',
            'whatsapp_popup_status' => 'required',
            'whatsapp_popup_message' => 'required'
        ];

        $request->validate($rules);

        BasicSetting::where('user_id', Auth::guard('web')->user()->id)->update(
            [
                'whatsapp_status' => $request->whatsapp_status,
                'whatsapp_number' => $request->whatsapp_number,
                'whatsapp_header_title' => $request->whatsapp_header_title,
                'whatsapp_popup_status' => $request->whatsapp_popup_status,
                'whatsapp_popup_message' => clean($request->whatsapp_popup_message)
            ]
        );

        $request->session()->flash('success', 'WhatsApp info updated successfully!');

        return back();
    }

    public function updateDisqus(Request $request)
    {
        $rules = [
            'disqus_status' => 'required',
            'disqus_short_name' => 'required'
        ];

        $request->validate($rules);

        BasicSetting::where('user_id', Auth::guard('web')->user()->id)->update(
            [
                'disqus_status' => $request->disqus_status,
                'disqus_short_name' => $request->disqus_short_name
            ]
        );

        $request->session()->flash('success', 'Disqus info updated successfully!');

        return back();
    }

    public function updatePixel(Request $request)
    {
        $rules = [
            'pixel_status' => 'required',
            'pixel_id' => 'required'
        ];

        $request->validate($rules);

        BasicSetting::where('user_id', Auth::guard('web')->user()->id)->update(
            [
                'pixel_status' => $request->pixel_status,
                'pixel_id' => $request->pixel_id
            ]
        );

        $request->session()->flash('success', 'Facebook Pixel info updated successfully!');

        return back();
    }

    public function updateTawkto(Request $request)
    {
        $rules = [
            'tawkto_status' => 'required',
            'tawkto_direct_chat_link' => 'required'
        ];

        $request->validate($rules);

        BasicSetting::where('user_id', Auth::guard('web')->user()->id)->update(
            [
                'tawkto_status' => $request->tawkto_status,
                'tawkto_direct_chat_link' => $request->tawkto_direct_chat_link
            ]
        );

        $request->session()->flash('success', 'Tawk.to info updated successfully!');

        return back();
    }





    public function cvUpload()
    {
        $data['basic_setting'] = BasicSetting::where('user_id', Auth::id())->first();
        return view('user.cv', $data);
    }
    public function updateCV(Request $request)
    {
        // dd($request->all());
        $request->validate([
            'cv'  => "required|file|mimes:pdf|max:10000"
        ]);

        $file = $request->file('cv');
        if ($request->hasFile('cv')) {
            $filename = uniqid() . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('assets/front/img/user/cv/'), $filename);
            $bss = BasicSetting::where('user_id', Auth::guard('web')->user()->id)->first();
            if (!is_null($bss)) {
                if ($bss->favicon) {
                    @unlink(public_path('assets/front/img/user/cv/' . $bss->cv));
                }
                $bss->cv_original = $file->getClientOriginalName();
                $bss->cv = $filename;
                $bss->save();
            } else {
                $bs = new BasicSetting();
                $bs->cv_original = $file->getClientOriginalName();
                $bs->cv = $filename;
                $bs->user_id = Auth::guard('web')->user()->id;
                $bs->save();
            }
        }
        Session::flash('success',  'Updated_successfully!');
        return redirect()->back();
    }

    public function deleteCV()
    {
        $bs = BasicSetting::where('user_id', Auth::id())->first();
        @unlink(public_path('assets/front/img/user/cv/' . $bs->cv));
        $bs->cv = NULL;
        $bs->cv_original = NULL;
        $bs->save();

        Session::flash('success',  'Deleted_successfully!');
        return back();
    }
}
