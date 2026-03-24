<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BasicSetting;
use App\Services\WhatsAppService;
use App\Services\EmailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;

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
            'meta_template_name' => 'nullable|string|max:100',
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
        Log::info('WhatsApp test request received', [
            'phone' => $request->test_phone,
            'all_data' => $request->all()
        ]);

        $request->validate([
            'test_phone' => 'required|string|max:20',
        ]);

        try {
            $whatsappService = new WhatsAppService();
            $testCode = rand(100000, 999999);

            Log::info('Attempting to send WhatsApp test message', [
                'phone' => $request->test_phone,
                'code' => $testCode
            ]);

            $whatsappService->sendPasswordResetCode($request->test_phone, $testCode, 'Test User');

            Log::info('WhatsApp test message sent successfully');
            Session::flash('success', "Test message sent successfully to {$request->test_phone}. Code: {$testCode}");
        } catch (\Exception $e) {
            Log::error('WhatsApp test failed', [
                'error' => $e->getMessage(),
                'phone' => $request->test_phone
            ]);
            Session::flash('error', 'Test failed: ' . $e->getMessage());
        }

        return redirect()->back();
    }

    public function checkConfiguration()
    {
        Log::info('Configuration check request received');

        try {
            $whatsappService = new WhatsAppService();
            $result = $whatsappService->testConfiguration();

            Log::info('Configuration check result', $result);

            if ($result['status'] === 'success') {
                Session::flash('success', $result['message']);
            } else {
                Session::flash('error', $result['message']);
            }
        } catch (\Exception $e) {
            Log::error('Configuration check failed', [
                'error' => $e->getMessage()
            ]);
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
            'selected_api' => 'nullable|string|in:meta,evolution',
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

        // Store the selected API for this message type
        if ($request->selected_api) {
            $abs->welcome_message_api = $request->selected_api;
        }

        $abs->save();

        $apiName = $request->selected_api === 'meta' ? 'Meta Cloud API' : 'Evolution API';
        Session::flash('success', "Welcome message settings updated successfully for {$apiName}!");
        return redirect()->back();
    }

    public function testWelcomeMessage(Request $request)
    {
        $request->validate([
            'test_phone' => 'required|string|max:20',
        ]);

        try {
            $whatsappService = new WhatsAppService();
            $testMessage = " مرحباً بك  في منصة تعاريف ! هذا اختبار لرسالة الترحيب.";

            $whatsappService->sendWelcomeMessage($request->test_phone, $testMessage, 'مستخدم تجريبي');

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
            'subscription_expiration_text' => 'nullable|string|max:1000',
            'subscription_expiration_days_before' => 'nullable|integer|min:1|max:30',
            'subscription_expiration_template' => 'nullable|string|max:100',
            'subscription_expiration_send_time' => 'nullable|date_format:H:i:s',
            'selected_api' => 'nullable|string|in:meta,evolution',
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
        $abs->subscription_expiration_text = $request->subscription_expiration_text ?? 'تنبيه: باقة الاشتراك الخاصة بك ستنتهي قريباً.';
        $abs->subscription_expiration_days_before = $request->subscription_expiration_days_before ?? 3;
        $abs->subscription_expiration_template = $request->subscription_expiration_template;
        $abs->subscription_expiration_send_time = $request->subscription_expiration_send_time ?? '09:00';

        // Store the selected API for this message type
        if ($request->selected_api) {
            $abs->subscription_expiration_api = $request->selected_api;
        }

        $abs->save();

        $apiName = $request->selected_api === 'meta' ? 'Meta Cloud API' : 'Evolution API';
        Session::flash('success', "Subscription expiration message settings updated successfully for {$apiName}!");
        return redirect()->back();
    }

    public function testSubscriptionExpiration(Request $request)
    {
        $request->validate([
            'test_phone' => 'required|string|max:20',
        ]);

        try {
            $whatsappService = new WhatsAppService();
            $testMessage = "{name}، تنبيه: اشتراكك في {package_name} سينتهي في {expiry_date}. يرجى تجديد اشتراكك لتجنب انقطاع الخدمة.";

            $whatsappService->sendSubscriptionExpirationMessage($request->test_phone, $testMessage, 'مستخدم تجريبي', 'الباقة الذهبية', '2024-12-31');

            Session::flash('success', "Test subscription expiration message sent successfully to {$request->test_phone}");
        } catch (\Exception $e) {
            Session::flash('error', 'Test failed: ' . $e->getMessage());
        }

        return redirect()->back();
    }

    public function updateSubscriptionExpired(Request $request)
    {

        $rules = [
            'subscription_expired_enabled' => 'nullable|boolean',
            'subscription_expired_text' => 'nullable|string|max:1000',
            'subscription_expired_template' => 'nullable|string|max:100',
            'subscription_expired_send_time' => 'nullable|date_format:H:i:s',
            'selected_api' => 'nullable|string|in:meta,evolution',
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

        $abs->subscription_expired_enabled = $request->has('subscription_expired_enabled') ? 1 : 0;
        $abs->subscription_expired_text = $request->subscription_expired_text ?? 'مرحبا {name}انتهى اشتراكك وتم نقلك إلى الباقة المجانية.
يمكنك الترقية في أي وقت.';
        $abs->subscription_expired_template = $request->subscription_expired_template;
        $abs->subscription_expired_send_time = $request->subscription_expired_send_time ?? '09:00';

        // Store the selected API for this message type
        if ($request->selected_api) {
            $abs->subscription_expired_api = $request->selected_api;
        }

        $abs->save();

        $apiName = $request->selected_api === 'meta' ? 'Meta Cloud API' : 'Evolution API';
        Session::flash('success', "On expiration notification settings updated successfully for {$apiName}!");
        return redirect()->back();
    }

    public function testSubscriptionExpired(Request $request)
    {
        $request->validate([
            'test_phone' => 'required|string|max:20',
        ]);

        try {
            $whatsappService = new WhatsAppService();
            $testMessage = "{name}، انتهت صلاحية اشتراكك في {package_name} في {expiry_date}. يرجى تجديد اشتراكك لاستعادة الخدمة.";
            $whatsappService->sendSubscriptionExpiredMessage($request->test_phone, $testMessage, 'مستخدم تجريبي', 'الباقة الذهبية', '2024-12-31');
            Session::flash('success', "Test subscription expired message sent successfully to {$request->test_phone}");
        } catch (\Exception $e) {
            Session::flash('error', 'Test failed: ' . $e->getMessage());
        }

        return redirect()->back();
    }

    public function updatePasswordReset(Request $request)
    {
        $rules = [
            'password_reset_enabled' => 'nullable|boolean',
            'password_reset_text' => 'nullable|string|max:1000',
            'password_reset_template' => 'nullable|string|max:100',
            'selected_api' => 'nullable|string|in:meta,evolution',
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

        $abs->password_reset_enabled = $request->has('password_reset_enabled') ? 1 : 0;
        $abs->password_reset_text = $request->password_reset_text ?? 'رمز إعادة تعيين كلمة المرور: {code}

هذا الرمز صالح لمدة 15 دقيقة.

أو يمكنك الضغط على الرابط التالي:
{reset_url}?code={code}';
        $abs->password_reset_template = $request->password_reset_template;

        // Store the selected API for this message type
        if ($request->selected_api) {
            $abs->password_reset_api = $request->selected_api;
        }

        $abs->save();

        // Check for Meta Cloud template warning
        $warningMessage = '';
        if ($request->selected_api === 'meta' && $abs->whatsapp_service === 'meta_cloud') {
            try {
                $whatsappService = new \App\Services\WhatsAppService();
                if (!$whatsappService->checkMetaTemplateExists('password_reset')) {
                    $warningMessage = 'Warning: Meta Cloud API template "password_reset" not found. The system will use the default message format.';
                }
            } catch (\Exception $e) {
                $warningMessage = 'Warning: Could not check Meta Cloud API templates. Please verify your API configuration.';
            }
        }

        $apiName = $request->selected_api === 'meta' ? 'Meta Cloud API' : 'Evolution API';
        Session::flash('success', "Password reset message settings updated successfully for {$apiName}!");

        if ($warningMessage) {
            Session::flash('warning', $warningMessage);
        }

        return redirect()->back();
    }

    public function testPasswordReset(Request $request)
    {
        $request->validate([
            'test_phone' => 'required|string|max:20',
        ]);

        try {
            $whatsappService = new WhatsAppService();
            $testCode = rand(100000, 999999);
            $resetUrl = env('FRONTEND_URL', 'https://app.taearif.com') . '/reset';

            $sent = $whatsappService->sendPasswordResetCode($request->test_phone, $testCode, 'مستخدم تجريبي', 'ar', $resetUrl, 'password_reset');
            if ($sent) {
                Session::flash('success', "Test password reset message sent successfully to {$request->test_phone}. Code: {$testCode}");
            } else {
                Session::flash('error', 'Failed to send password reset message. If Meta template is configured, ensure it is approved and active.');
            }
        } catch (\Exception $e) {
            Session::flash('error', 'Test failed: ' . $e->getMessage());
        }

        return redirect()->back();
    }

    public function updateRegistrationOtp(Request $request)
    {
        $rules = [
            'registration_otp_template' => 'nullable|string|max:100',
            'otp_max_sends_per_hour' => 'nullable|integer|min:1|max:100',
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $abs = BasicSetting::first();
        if (!$abs) {
            $abs = new BasicSetting();
        }

        $abs->registration_otp_template = $request->registration_otp_template;
        $abs->otp_max_sends_per_hour = (int) ($request->otp_max_sends_per_hour ?? 5);
        $abs->save();

        Session::flash('success', 'Registration OTP template settings updated successfully!');

        // Handle AJAX requests
        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Registration OTP template settings updated successfully!'
            ]);
        }

        return redirect()->back();
    }

    public function testRegistrationOtp(Request $request)
    {
        $request->validate([
            'test_phone' => 'required|string|max:20',
        ]);

        try {
            $whatsappService = new WhatsAppService();
            $testCode = rand(100000, 999999);

            $sent = $whatsappService->sendRegistrationOtp($request->test_phone, $testCode);
            if ($sent) {
                Session::flash('success', "Test registration OTP sent successfully to {$request->test_phone}. Code: {$testCode}");
            } else {
                Session::flash('error', 'Failed to send registration OTP. If Meta template is configured, ensure it is approved and active.');
            }
        } catch (\Exception $e) {
            Session::flash('error', 'Test failed: ' . $e->getMessage());
        }

        return redirect()->back();
    }


    /**
     * Fetch WhatsApp templates from Facebook Meta API
     */
    public function fetchMetaTemplates(Request $request)
    {
        try {
            $abs = BasicSetting::first();

            if (!$abs || !$abs->meta_access_token || !$abs->meta_business_account_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Meta Cloud API configuration incomplete'
                ]);
            }

            $whatsappService = new WhatsAppService();
            $templates = $whatsappService->fetchMetaTemplates();

            return response()->json([
                'success' => true,
                'templates' => $templates
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching templates: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Save selected template for testing
     */
    public function saveSelectedTemplate(Request $request)
    {
        try {
            $request->validate([
                'template_name' => 'nullable|string|max:255'
            ]);

            $abs = BasicSetting::first();
            if (!$abs) {
                return response()->json([
                    'success' => false,
                    'message' => 'Basic settings not found'
                ]);
            }

            // Debug logging
            Log::info('Saving template selection', [
                'template_name' => $request->template_name,
                'current_value' => $abs->meta_test_template_name ?? 'null'
            ]);

            // Save the selected template name for testing
            $abs->update([
                'meta_test_template_name' => $request->template_name
            ]);

            // Verify the save
            $abs->refresh();
            Log::info('Template saved successfully', [
                'saved_value' => $abs->meta_test_template_name
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Template selection saved successfully',
                'template_name' => $request->template_name
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error saving template selection: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Test selected template by sending a test message
     */
    public function testSelectedTemplate(Request $request)
    {
        try {
            $request->validate([
                'test_phone' => 'required|string|max:20'
            ]);

            $abs = BasicSetting::first();

            // Debug logging
            Log::info('Test template request', [
                'test_phone' => $request->test_phone,
                'meta_test_template_name' => $abs->meta_test_template_name ?? 'null',
                'whatsapp_service' => $abs->whatsapp_service ?? 'null'
            ]);

            if (!$abs || !$abs->meta_test_template_name) {
                return response()->json([
                    'success' => false,
                    'message' => 'No template selected for testing. Please select a template first.'
                ]);
            }

            $whatsappService = new WhatsAppService();
            $result = $whatsappService->sendTestMessage(
                $request->test_phone,
                $abs->meta_test_template_name,
                $abs->meta_template_language ?? 'ar'
            );

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => 'Test message sent successfully using template: ' . $abs->meta_test_template_name
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to send test message: ' . $result['message']
                ]);
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error sending test message: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Update master toggle for all WhatsApp notifications
     */
    public function updateMasterWhatsAppToggle(Request $request)
    {
        $abs = BasicSetting::first();
        if (!$abs) {
            $abs = new BasicSetting();
        }

        $abs->whatsapp_notifications_enabled = $request->has('whatsapp_notifications_enabled') ? 1 : 0;
        $abs->save();

        $status = $abs->whatsapp_notifications_enabled ? 'تفعيل' : 'إيقاف';
        Session::flash('success', "تم {$status} جميع إشعارات واتس اب بنجاح!");
        return redirect()->back();
    }
}
