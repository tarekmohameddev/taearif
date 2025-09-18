<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BasicSetting;
use App\Services\WhatsAppService;
use App\Services\EmailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Session;

class CommunicationController extends Controller
{
    public function __construct()
    {
        $abs = BasicSetting::first();
        if ($abs) {
            config(['app.timezone' => $abs->timezone]);
        }
    }

    public function whatsapp()
    {
        $data['abs'] = BasicSetting::first();
        return view('admin.communication.whatsapp', $data);
    }

    public function metaCloud()
    {
        $data['abs'] = BasicSetting::first();
        return view('admin.communication.meta-cloud', $data);
    }

    public function evolutionApi()
    {
        $data['abs'] = BasicSetting::first();
        return view('admin.communication.evolution-api', $data);
    }

    public function updateServiceSelection(Request $request)
    {
        $rules = [
            'whatsapp_service' => 'required|in:meta_cloud,evolution_api',
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $abs = BasicSetting::first();
        if (!$abs) {
            $abs = new BasicSetting();
        }

        $abs->whatsapp_service = $request->whatsapp_service;
        $abs->save();

        Session::flash('success', 'WhatsApp service updated successfully!');
        return redirect()->back();
    }

    public function updateMetaCloud(Request $request)
    {
        $rules = [
            'meta_access_token' => 'required|string|max:500',
            'meta_phone_number_id' => 'required|string|max:100',
            'meta_business_account_id' => 'required|string|max:100',
            'meta_template_name' => 'required|string|max:100',
            'meta_template_language' => 'required|string|max:10',
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $abs = BasicSetting::first();
        if (!$abs) {
            $abs = new BasicSetting();
        }

        $abs->meta_access_token = $request->meta_access_token;
        $abs->meta_phone_number_id = $request->meta_phone_number_id;
        $abs->meta_business_account_id = $request->meta_business_account_id;
        $abs->meta_template_name = $request->meta_template_name;
        $abs->meta_template_language = $request->meta_template_language;
        $abs->save();

        Session::flash('success', 'Meta Cloud API settings updated successfully!');
        return redirect()->back();
    }

    public function updateEvolutionApi(Request $request)
    {
        $rules = [
            'evolution_api_url' => 'required|url|max:255',
            'evolution_api_key' => 'required|string|max:500',
            'evolution_instance_name' => 'required|string|max:100',
            'evolution_phone_number' => 'required|string|max:20',
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $abs = BasicSetting::first();
        if (!$abs) {
            $abs = new BasicSetting();
        }

        $abs->evolution_api_url = $request->evolution_api_url;
        $abs->evolution_api_key = $request->evolution_api_key;
        $abs->evolution_instance_name = $request->evolution_instance_name;
        $abs->evolution_phone_number = $request->evolution_phone_number;
        $abs->save();

        Session::flash('success', 'Evolution API settings updated successfully!');
        return redirect()->back();
    }

    public function updateWhatsapp(Request $request)
    {
        $rules = [
            'whatsapp_number' => 'nullable|string|max:255',
            'whatsapp_message' => 'nullable|string|max:1000',
            'whatsapp_status' => 'nullable|boolean',
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $abs = BasicSetting::first();
        if (!$abs) {
            $abs = new BasicSetting();
        }

        $abs->whatsapp_number = $request->whatsapp_number;
        $abs->whatsapp_message = $request->whatsapp_message;
        $abs->whatsapp_status = $request->has('whatsapp_status') ? 1 : 0;
        $abs->save();

        Session::flash('success', 'WhatsApp settings updated successfully!');
        return redirect()->back();
    }

    public function testWhatsAppService(Request $request)
    {
        $request->validate([
            'test_phone' => 'required|string|max:20',
        ]);

        try {
            $whatsappService = new WhatsAppService();
            $testCode = rand(100000, 999999);
            
            $whatsappService->sendPasswordResetCode($request->test_phone, $testCode, 'Test User');
            
            Session::flash('success', "Test message sent successfully to {$request->test_phone}. Code: {$testCode}");
        } catch (\Exception $e) {
            Session::flash('error', 'Test failed: ' . $e->getMessage());
        }

        return redirect()->back();
    }

    public function checkConfiguration()
    {
        try {
            $whatsappService = new WhatsAppService();
            $result = $whatsappService->testConfiguration();
            
            if ($result['status'] === 'success') {
                Session::flash('success', $result['message']);
            } else {
                Session::flash('error', $result['message']);
            }
        } catch (\Exception $e) {
            Session::flash('error', 'Configuration check failed: ' . $e->getMessage());
        }

        return redirect()->back();
    }

    public function updateWelcomeMessage(Request $request)
    {
        $rules = [
            'welcome_message_enabled' => 'nullable|boolean',
            'welcome_message_text' => 'required|string|max:1000',
            'welcome_message_delay' => 'nullable|integer|min:0|max:300',
            'welcome_message_template' => 'nullable|string|max:100',
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $abs = BasicSetting::first();
        if (!$abs) {
            $abs = new BasicSetting();
        }

        $abs->welcome_message_enabled = $request->has('welcome_message_enabled') ? 1 : 0;
        $abs->welcome_message_text = $request->welcome_message_text;
        $abs->welcome_message_delay = $request->welcome_message_delay ?? 5;
        $abs->welcome_message_template = $request->welcome_message_template;
        $abs->save();

        Session::flash('success', 'Welcome message settings updated successfully!');
        return redirect()->back();
    }

    public function testWelcomeMessage(Request $request)
    {
        $request->validate([
            'test_phone' => 'required|string|max:20',
        ]);

        try {
            $whatsappService = new WhatsAppService();
            $testMessage = "مرحباً بك في منصتنا! هذا اختبار لرسالة الترحيب.";
            
            $whatsappService->sendWelcomeMessage($request->test_phone, $testMessage, 'Test User');
            
            Session::flash('success', "Test welcome message sent successfully to {$request->test_phone}");
        } catch (\Exception $e) {
            Session::flash('error', 'Test failed: ' . $e->getMessage());
        }

        return redirect()->back();
    }

    public function updateSubscriptionExpiration(Request $request)
    {
        $rules = [
            'subscription_expiration_enabled' => 'nullable|boolean',
            'subscription_expiration_text' => 'required|string|max:1000',
            'subscription_expiration_days_before' => 'nullable|integer|min:1|max:30',
            'subscription_expiration_template' => 'nullable|string|max:100',
            'subscription_expiration_send_time' => 'nullable|date_format:H:i',
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $abs = BasicSetting::first();
        if (!$abs) {
            $abs = new BasicSetting();
        }

        $abs->subscription_expiration_enabled = $request->has('subscription_expiration_enabled') ? 1 : 0;
        $abs->subscription_expiration_text = $request->subscription_expiration_text;
        $abs->subscription_expiration_days_before = $request->subscription_expiration_days_before ?? 3;
        $abs->subscription_expiration_template = $request->subscription_expiration_template;
        $abs->subscription_expiration_send_time = $request->subscription_expiration_send_time ?? '09:00';
        $abs->save();

        Session::flash('success', 'Subscription expiration message settings updated successfully!');
        return redirect()->back();
    }

    public function testSubscriptionExpiration(Request $request)
    {
        $request->validate([
            'test_phone' => 'required|string|max:20',
        ]);

        try {
            $whatsappService = new WhatsAppService();
            $testMessage = "تنبيه: باقة الاشتراك الخاصة بك ستنتهي قريباً. هذا اختبار لرسالة انتهاء الباقة.";
            
            $whatsappService->sendSubscriptionExpirationMessage($request->test_phone, $testMessage, 'Test User', 'Test Package', '2024-12-31');
            
            Session::flash('success', "Test subscription expiration message sent successfully to {$request->test_phone}");
        } catch (\Exception $e) {
            Session::flash('error', 'Test failed: ' . $e->getMessage());
        }

        return redirect()->back();
    }

    public function updateEmailTemplates(Request $request)
    {
        $rules = [
            'email_password_reset_template' => 'nullable|string|max:100',
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $abs = BasicSetting::first();
        if (!$abs) {
            $abs = new BasicSetting();
        }

        $abs->email_password_reset_template = $request->email_password_reset_template;
        $abs->save();

        Session::flash('success', 'Email template settings updated successfully!');
        return redirect()->back();
    }

    public function testEmailTemplates(Request $request)
    {
        $request->validate([
            'test_email' => 'required|email',
        ]);

        try {
            $emailService = new EmailService();
            $success = $emailService->testEmailConfiguration($request->test_email);
            
            if ($success) {
                return response()->json([
                    'success' => true,
                    'message' => 'Test email sent successfully to ' . $request->test_email
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to send test email. Please check SMTP configuration.'
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }
}
