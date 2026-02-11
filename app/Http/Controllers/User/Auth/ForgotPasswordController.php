<?php

namespace App\Http\Controllers\User\Auth;

use App\Http\Controllers\Controller;
use App\Models\Language;
use App\Models\Seo;
use App\Models\User;
use App\Models\BasicExtended;
use App\Services\EmailService;
use App\Services\WhatsAppService;
use App\Jobs\SendPasswordResetCodeJob;
use Illuminate\Foundation\Auth\SendsPasswordResetEmails;
use Illuminate\Support\Facades\Password;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
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

    /**
     * Send a reset link to the given user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    public function sendResetLinkEmail(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'reset_method' => 'required|in:email,whatsapp',
            'phone' => 'required_if:reset_method,whatsapp|nullable|string'
        ]);

        // Find user by email
        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return back()->withErrors(['email' => __('We can\'t find a user with that email address.')]);
        }

        // Generate reset code
        $resetCode = Str::random(6);
        
        // Store reset code in session
        Session::put('password_reset_code', $resetCode);
        Session::put('password_reset_email', $request->email);
        Session::put('password_reset_method', $request->reset_method);
        Session::put('password_reset_expires', now()->addMinutes(15));

        try {
            if ($request->reset_method === 'email') {
                // Send email
                $emailService = new EmailService();
                $settings = BasicExtended::first();
                $templateName = $settings->email_password_reset_template ?? null;
                
                $success = $emailService->sendPasswordResetCode(
                    $request->email,
                    $user->name ?? $user->email,
                    $resetCode,
                    'ar', // user language
                    $templateName,
                    null, // resetUrl
                    $user->id
                );

                if ($success) {
                    Session::flash('success', __('Password reset code has been sent to your email address.'));
                } else {
                    return back()->withErrors(['email' => __('Failed to send reset code. Please try again.')]);
                }

            } else {
                // Send WhatsApp
                $whatsappService = new WhatsAppService();
                $phone = $request->phone;
                
                // Remove any non-numeric characters except +
                $phone = preg_replace('/[^0-9+]/', '', $phone);
                
                // Get frontend URL for reset link
                $frontendUrl = config('app.frontend_url');
                $resetUrl = $frontendUrl . '/reset';
                
                $settings = BasicExtended::first();
                $templateName = $settings->meta_template_name ?? null;
                
                $success = $whatsappService->sendPasswordResetCode(
                    $phone,
                    $resetCode,
                    $user->name ?? $user->email,
                    'ar', // user language
                    $resetUrl,
                    $templateName,
                    $user->id
                );

                if ($success) {
                    Session::flash('success', __('Password reset code has been sent to your WhatsApp number.'));
                } else {
                    return back()->withErrors(['phone' => __('Failed to send reset code. Please try again.')]);
                }
            }

            // Redirect to reset form
            return redirect()->route('user.reset.password.form', [
                'token' => 'code',
                'email' => $request->email
            ]);

        } catch (\Exception $e) {
            \Log::error('Password reset failed: ' . $e->getMessage());
            return back()->withErrors(['email' => __('An error occurred. Please try again.')]);
        }
    }
}
