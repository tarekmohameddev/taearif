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
            $testMessage = "تنبيه: باقة الاشتراك الخاصة بك ستنتهي قريباً. هذا اختبار لرسالة انتهاء الباقة.";
            
            $whatsappService->sendSubscriptionExpirationMessage($request->test_phone, $testMessage, 'Test User', 'Test Package', '2024-12-31');
            
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
            $testMessage = "مرحبا {name} انتهى اشتراكك وتم نقلك إلى الباقة المجانية. يمكنك الترقية في أي وقت.";
            $whatsappService->sendSubscriptionExpiredMessage($request->test_phone, $testMessage, 'Test User', 'Test Package', '2024-12-31');          
            Session::flash('success', "Test on expiration message sent successfully to {$request->test_phone}");
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
}
