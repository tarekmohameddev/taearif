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
        $information['data'] = FooterText::where('language_id', $lang->id)->where('user_id', Auth::id())->first();

        // socials
        $data['socials'] = Social::where('user_id', Auth::id())
            ->orderBy('id', 'DESC')
            ->get();



        return view('user.settings.general-settings', $data , $information);
    }

    public function updateAllSettings(Request $request, $language)
    {
        // $lang = Language::where('code', $language)->where('user_id', Auth::id())->firstOrFail();
        $user = Auth::guard('web')->user();
        $package = UserPermissionHelper::currentPackagePermission($user->id);
        if (!empty($user)) {
            $permissions = UserPermissionHelper::packagePermission($user->id);
            $permissions = json_decode($permissions, true);
        }

        // Use "sometimes" so the fields are validated only when provided.
        $rules = [
            'website_title' => 'sometimes|required',
            'timezone'      => 'sometimes|required',
        ];

        // Add validation rules for images if they are being uploaded
        if ($request->hasFile('logo')) {
            $rules['logo'] = [
                function ($attribute, $value, $fail) use ($request) {
                    $allowedExts = ['jpg', 'png', 'jpeg'];
                    $ext = $request->file('logo')->getClientOriginalExtension();
                    if (!in_array($ext, $allowedExts)) {
                        return $fail("Only png, jpg, jpeg images are allowed for logo.");
                    }
                }
            ];
        }

        // if ($request->hasFile('preloader')) {
        //     $rules['preloader'] = [
        //         function ($attribute, $value, $fail) use ($request) {
        //             $allowedExts = ['jpg', 'png', 'jpeg', 'gif'];
        //             $ext = $request->file('preloader')->getClientOriginalExtension();
        //             if (!in_array($ext, $allowedExts)) {
        //                 return $fail("Only png, jpg, jpeg, gif images are allowed for preloader.");
        //             }
        //         }
        //     ];
        // }

        // if ($request->hasFile('breadcrumb')) {
        //     $rules['breadcrumb'] = [
        //         function ($attribute, $value, $fail) use ($request) {
        //             $allowedExts = ['jpg', 'png', 'jpeg'];
        //             $ext = $request->file('breadcrumb')->getClientOriginalExtension();
        //             if (!in_array($ext, $allowedExts)) {
        //                 return $fail("Only png, jpg, jpeg images are allowed for breadcrumb.");
        //             }
        //         }
        //     ];
        // }

        // if ($request->hasFile('favicon')) {
        //     $rules['favicon'] = [
        //         function ($attribute, $value, $fail) use ($request) {
        //             $allowedExts = ['jpg', 'png', 'jpeg', 'ico'];
        //             $ext = $request->file('favicon')->getClientOriginalExtension();
        //             if (!in_array($ext, $allowedExts)) {
        //                 return $fail("Only png, jpg, jpeg, ico images are allowed for favicon.");
        //             }
        //         }
        //     ];
        // }

        // Add ecommerce-specific rules if applicable.
        if (!empty($permissions) && in_array('Ecommerce', $permissions)) {
            $rules['base_currency_symbol']          = 'sometimes|required';
            $rules['base_currency_symbol_position']   = 'sometimes|required';
            $rules['base_currency_text']              = 'sometimes|required';
            $rules['base_currency_text_position']     = 'sometimes|required';
            $rules['base_currency_rate']              = 'sometimes|required|numeric|min:0.00000001';
        }

        // Validate request.
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            $validator->getMessageBag()->add('error', 'true');
            return response()->json($validator->errors());
        }

        // Get or create BasicSetting record.
        $bss = BasicSetting::firstOrNew(['user_id' => $user->id]);

        // Update basic information if these fields are provided.
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

        // Handle currency settings if applicable and provided.
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

        // Handle file uploads.
        if ($request->hasFile('logo')) {
            try {
                // Delete old files if they exist.
                $imageFields = ['logo', 'preloader', 'breadcrumb', 'favicon'];
                foreach ($imageFields as $field) {
                    if ($bss->$field) {
                        @unlink(public_path('assets/front/img/user/' . $bss->$field));
                    }
                }

                // Upload new logo file to the first directory: assets/front/img/user/
                $file = $request->file('logo');
                $filename = uniqid() . '.' . $file->getClientOriginalExtension();
                $file->move(public_path('assets/front/img/user/'), $filename);

                // Assign the same filename to all fields in BasicSetting.
                foreach ($imageFields as $field) {
                    $bss->$field = $filename;
                }

                // Copy the uploaded file to the second directory: assets/front/img/user/footer/
                $sourcePath = public_path('assets/front/img/user/' . $filename);
                $destinationPath = public_path('assets/front/img/user/footer/' . $filename);
                if (!file_exists(public_path('assets/front/img/user/footer/'))) {
                    mkdir(public_path('assets/front/img/user/footer/'), 0775, true); // Create directory if it doesn't exist
                }
                copy($sourcePath, $destinationPath);

                // Update FooterText with the same logo and bg_image.
                // $lang = Language::where('user_id', $user->id)->first(); // Get the default language for the user.
                $lang = Language::where('code', $language)->where('user_id', Auth::id())->firstOrFail();
                if ($lang) {
                    $footerText = FooterText::where('language_id', $lang->id)->where('user_id', $user->id)->first();
                    if (is_null($footerText)) {
                        $footerText = new FooterText;
                    }

                    // Delete old footer logo and bg_image if they exist.
                    if ($footerText->logo) {
                        @unlink(public_path('assets/front/img/user/footer/' . $footerText->logo));
                    }
                    if ($footerText->bg_image) {
                        @unlink(public_path('assets/front/img/user/footer/' . $footerText->bg_image));
                    }

                    // Set the new logo and bg_image in FooterText.
                    $footerText->logo = $filename;
                    $footerText->bg_image = $filename; // Set the same logo as bg_image.
                    $footerText->language_id = $lang->id;
                    $footerText->user_id = $user->id;
                    $footerText->save();
                }
            } catch (\Exception $e) {
                \Log::error("File upload failed: " . $e->getMessage());
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
            'logo_uploaded'    => $request->hasFile('logo') ? true : $steps->logo_uploaded,
            'favicon_uploaded' => $request->hasFile('favicon') ? true : $steps->favicon_uploaded,
            'homepage_updated' => $request->hasFile('preloader') ? true : $steps->homepage_updated,
        ]);

        Session::flash('success', 'Settings updated successfully.');
        return back();
    }

    //
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
        $img = $request->file('breadcrumb');
        $allowedExts = array('jpg', 'png', 'jpeg');

        $rules = [
            'breadcrumb' => [
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
            return response()->json(['errors' => $validator->errors(), 'id' => 'favicon']);
        }

        if ($request->hasFile('breadcrumb')) {
            $filename = uniqid() . '.' . $img->getClientOriginalExtension();
            $img->move(public_path('assets/front/img/user/'), $filename);
            $bss = BasicSetting::where('user_id', Auth::guard('web')->user()->id)->first();
            if (!is_null($bss)) {
                if ($bss->favicon) {
                    @unlink(public_path('assets/front/img/user/' . $bss->breadcrumb));
                }
                $bss->breadcrumb = $filename;
                $bss->user_id = Auth::guard('web')->user()->id;
                $bss->save();
            } else {
                $bs = new BasicSetting();
                $bs->breadcrumb = $filename;
                $bs->user_id = Auth::guard('web')->user()->id;
                $bs->save();
            }
        }
        Session::flash('success', 'Breadcrumb update successfully.');

        UserStep::updateOrCreate(
            ['user_id' => Auth::guard('web')->user()->id],
            ['sub_pages_upper_image' => true]
        );

        return back();
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
            if ($type == 'about_image' || $type == 'about_video_url' || $type == 'about_video_image') {
                continue;
            }
            $homeText->$type = Purifier::clean($request[$type]);
        }
        if ($request->hasFile('about_image')) {
            $aboutImage = uniqid() . '.' . $request->file('about_image')->getClientOriginalExtension();
            $request->file('about_image')->move(public_path('assets/front/img/user/home_settings/'), $aboutImage);
            @unlink(public_path('assets/front/img/user/home_settings/' . $homeText->about_image));
            $homeText->about_image = $aboutImage;
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

    public function updateWhyChooseUsSection(Request $request, $language)
    {
        $rules = [
            'why_choose_us_section_title' => 'nullable|max:255',
            'why_choose_us_section_subtitle' => 'nullable|max:255',
            'why_choose_us_section_text' => 'nullable',
            'why_choose_us_section_button_text' => 'nullable|max:255',
            'why_choose_us_section_button_url' => 'nullable|max:255'
        ];
        $messages = [
            'why_choose_us_section_title.max' => 'The title field can contain maximum 255 characters',
            'why_choose_us_section_subtitle.max' => 'The subtitle field can contain maximum 255 characters',
            'why_choose_us_section_button_text.max' => 'The button name field can contain maximum 255 characters',
            'why_choose_us_section_button_url.max' => 'The button url field can contain maximum 255 characters',
            'why_choose_us_section_image.max' => 'The image field is required',
            'why_choose_us_section_video_image.max' => 'The video section image field is required',
            'why_choose_us_section_video_url.max' => 'The video section url field can contain maximum 255 characters'
        ];

        $lang = Language::where('code', $language)->where('user_id', Auth::guard('web')->user()->id)->first();
        $userBs = BasicSetting::where('user_id', Auth::guard('web')->user()->id)->first();
        $data = HomePageText::where('language_id', $lang->id)->where('user_id', Auth::guard('web')->user()->id)->first();
        $request['video_image_name'] = $data->why_choose_us_section_video_image;
        $request['image_name'] = $data->why_choose_us_section_image;

        if (empty($data->why_choose_us_section_image) && !$request->hasFile('why_choose_us_section_image')) {
            $rules['why_choose_us_section_image'] = 'nullable|mimes:jpeg,jpg,png';
        }
        if (empty($data->why_choose_us_section_video_image) && !$request->hasFile('why_choose_us_section_video_image') && $userBs->theme === 'home_three') {
            $rules['why_choose_us_section_video_image'] = 'nullable|mimes:jpeg,jpg,png';
        }

        if ($request->hasFile('why_choose_us_section_image')) {
            $request['image_name'] = Uploader::update_picture('assets/front/img/user/home_settings/', $request->file('why_choose_us_section_image'), $data->why_choose_us_section_image);
        }
        if ($userBs->theme === 'home_three') {
            $rules['why_choose_us_section_video_url'] = 'nullable';
            if ($request->hasFile('why_choose_us_section_video_image')) {
                $request['video_image_name'] = Uploader::update_picture('assets/front/img/user/home_settings/', $request->file('why_choose_us_section_video_image'), $data->why_choose_us_section_video_image);
            }
        }
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), $rules, $messages);
        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator);
        }
        $data->update($request->except(['why_choose_us_section_image', 'why_choose_us_section_text', 'why_choose_us_section_video_image', 'why_choose_us_section_video_url']) + [
            'why_choose_us_section_image' => $request->image_name,
            'why_choose_us_section_video_image' => $request->video_image_name,
            'why_choose_us_section_text' => clean($request->why_choose_us_section_text),
            'why_choose_us_section_video_url' => (strpos($request->why_choose_us_section_video_url, "&") != false) ? substr($request->why_choose_us_section_video_url, 0, strpos($request->why_choose_us_section_video_url, "&")) : $request->why_choose_us_section_video_url,
        ]);
        $request->session()->flash('success', 'Why choose us section updated successfully!');
        $steps = UserStep::firstOrCreate(
            ['user_id' => Auth::guard('web')->user()->id],
            ['logo_uploaded' => false, 'favicon_uploaded' => false, 'website_named' => false, 'homepage_updated' => false] // Default values if record doesn't exist
        );
        $steps->update(['homepage_updated' => true]);
        return redirect()->back();
    }
    public function whyChooseUsItemStore(Request $request)
    {
        $request->validate([
            'language_id' => 'required',
            'icon' => 'required',
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
            'icon' => 'required',
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
        $request->session()->flash('success', 'Work process section updated successfully!');
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
