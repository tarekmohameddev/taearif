<?php

namespace App\Http\Controllers\User;

use App\Models\User;
use App\Models\User\Language;
use Illuminate\Http\Request;
use App\Services\LogoService;
use App\Models\User\FooterText;
use App\Models\User\HomeSection;
use App\Models\User\BasicSetting;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class OnboardingController extends Controller
{
    /**
     * Show Step 1 of Onboarding
     */
    public function index()
    {
        $user = Auth::user();

        // Redirect if already onboarded
        if ($user->onboarding_completed) {
            return redirect()->route('user-dashboard');
        }

        return view('user.onboarding');
    }

    /**
     * Store Step 1 Data and Redirect to Step 2
     */
    public function store(Request $request)
    {
        $request->validate([
            'website_title' => 'required|string|max:255',
            'industry_type' => 'required|string',
            'short_description' => 'nullable|string|max:255',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'favicon' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:512',
            'base_color' => 'required|string|max:7',
            'secondary_color' => 'required|string|max:7'
        ]);
        $user = Auth::user();
        $lang = Language::where([['user_id', Auth::id()], ['is_default', 1]])->first();

        $bss = BasicSetting::firstOrNew(['user_id' => $user->id]);
        if ($request->has('website_title')) {
            $bss->website_title = $request->website_title;
        }

        if ($request->has('base_color')) {
            $bss->base_color = $request->base_color;
        }
        if ($request->has('secondary_color')) {
            $bss->secondary_color = $request->secondary_color;
        }
        if ($request->has('industry_type')) {
            $bss->industry_type = $request->industry_type;
        }
        if ($request->has('short_description')) {
            $bss->short_description = $request->short_description;
        }

        // main "logo" upload.
        if ($request->hasFile('logo')) {
            try {
                // Delete old images if they exist.
                $imageFields = ['logo', 'preloader', 'breadcrumb', 'favicon'];
                foreach ($imageFields as $field) {
                    if ($bss->$field) {
                        @unlink(public_path('assets/front/img/user/' . $bss->$field));
                    }
                }

                $file = $request->file('logo');
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
                \Log::error("File upload failed: " . $e->getMessage());
                Session::flash('error', 'File upload failed. Please try again.');
                return back();
            }
        }

        $bss->save();


        // Use LogoService to update logo and favicon
        $uploadedFiles = LogoService::updateLogoAndFavicon($request, $user);

        // If the logo and favicon were updated successfully, assign them to the user
        if ($uploadedFiles) {
            $user->update([
                'logo' => $uploadedFiles['logo'],
                'favicon' => $uploadedFiles['favicon']
            ]);
        }

        // Update or Create User Details in BasicSetting
        BasicSetting::updateOrCreate(
            ['user_id' => $user->id],
            [
                'company_name' => $request->company_name,
                'industry_type' => $request->industry_type,
                'short_description' => $request->short_description,
                'primary_color' => $request->primary_color
            ]
        );

        // Redirect to Step 2
        return redirect()->route('onboarding.showStep2');
    }


    /**
     * Show Step 2 of Onboarding
     */
    public function showStep2()
    {
        $user = Auth::user();
        $data['sections'] = HomeSection::where('user_id', Auth::guard('web')->user()->id)->first();

        if ($user->onboarding_completed) {
            return redirect()->route('user-dashboard');
        }

        return view('user.onboarding_step2', compact('data'));
    }

    /**
     * Process Step 2 and Complete Onboarding
     */


    public function step2(Request $request)
    {
        $user_id = Auth::guard('web')->user()->id;

        // Retrieve existing section or create a new one
        $sections = HomeSection::where('user_id', $user_id)->first();
        if (is_null($sections)) {
            $sections = new HomeSection;
            $sections->user_id = $user_id;
        }

        // Define all possible checkbox fields
        $checkboxFields = [
            'intro_section',
            'portfolio_section',
            'featured_services_section',
            'why_choose_us_section',
            'counter_info_section',
            'video_section',
            'team_members_section',
            'skills_section',
            'testimonials_section',
            'blogs_section',
            'brand_section',
            'top_footer_section',
            'copyright_section'
        ];

        // Set all checkboxes to 0 by default
        foreach ($checkboxFields as $field) {
            $sections->$field = 0;
        }

        // Update the fields that are present in the request (checked boxes)
        foreach ($request->except('_token') as $key => $value) {
            if (in_array($key, $checkboxFields)) {
                $sections->$key = 1;
            }
        }

        // Save the changes
        $sections->save();

        // Flash success message and return
        Session::flash('success', 'Sections customized successfully!');
        return back();
    }


    /**
     * Skip Onboarding and Redirect to Dashboard
     */
    public function skip()
    {
        $user = Auth::user();
        $user->update(['onboarding_completed' => true]);

        return redirect()->route('onboarding.showStep2');
    }
}
