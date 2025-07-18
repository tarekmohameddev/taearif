<?php

namespace App\Http\Controllers\User\Auth;

use App\Http\Controllers\Controller;
use App\Models\Language;
use App\Models\Seo;
use Illuminate\Foundation\Auth\SendsPasswordResetEmails;
use Illuminate\Support\Facades\Password;
use Config;
use App\Models\BasicSetting as BS;

class ForgotPasswordController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Password Reset Controller
    |--------------------------------------------------------------------------
    |
    | This controller is responsible for handling password reset emails and
    | includes a trait which assists in sending these notifications from
    | your application to your users. Feel free to explore this trait.
    |
    */

    use SendsPasswordResetEmails;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest');
    }

    /**
     * Display the form to request a password reset link.
     *
     * @return
     */
    public function showLinkRequestForm()
    {
        $bs = BS::first();
        
        Config::set('captcha.sitekey', $bs->google_recaptcha_site_key);
        Config::set('captcha.secret', $bs->google_recaptcha_secret_key);

        if (session()->has('lang')) {
            $currentLang = Language::where('code', session()->get('lang'))->first();
        } else {
            $currentLang = Language::where('is_default', 1)->first();
        }
        $bs = $currentLang->basic_setting;

        $data['seo'] = Seo::where('language_id', $currentLang->id)->first();
        $data['bs'] = $bs;
        return view('front.auth.passwords.email', $data);
    }

    /**
     * Get the broker to be used during password reset.
     *
     * @return \Illuminate\Contracts\Auth\PasswordBroker
     */
    public function broker()
    {
        return Password::broker('users');
    }
}
