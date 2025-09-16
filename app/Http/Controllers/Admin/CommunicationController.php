<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BasicSetting;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Session;

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
}
