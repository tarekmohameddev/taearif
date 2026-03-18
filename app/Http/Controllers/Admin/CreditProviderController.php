<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Api\marketing\CreditCommunicationProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class CreditProviderController extends Controller
{
    /**
     * Show provider configuration page
     */
    public function index()
    {
        $providers = CreditCommunicationProvider::all()->keyBy('provider_type');
        
        // Ensure all provider types exist
        $providerTypes = ['whatsapp_meta', 'whatsapp_evolution', 'sms'];
        foreach ($providerTypes as $type) {
            if (!isset($providers[$type])) {
                $providers[$type] = CreditCommunicationProvider::create([
                    'provider_type' => $type,
                    'is_enabled' => false,
                    'status' => 'unconfigured',
                ]);
            }
        }
        
        return view('admin.credit_management.providers.index', compact('providers'));
    }

    /**
     * Update provider configuration
     */
    public function update(Request $request, $providerType)
    {
        $rules = [
            'is_enabled' => 'boolean',
            'name' => 'nullable|string|max:255',
        ];
        
        // Provider-specific validation
        if ($providerType === 'whatsapp_meta') {
            $rules = array_merge($rules, [
                'phone_number_id' => 'required_if:is_enabled,true|nullable|string',
                'business_account_id' => 'required_if:is_enabled,true|nullable|string',
                'api_url' => 'required_if:is_enabled,true|nullable|url',
                'access_token' => 'nullable|string', // Not required if just toggling
                'webhook_verify_token' => 'nullable|string',
            ]);
        } elseif ($providerType === 'whatsapp_evolution') {
            $rules = array_merge($rules, [
                'instance_name' => 'required_if:is_enabled,true|nullable|string',
                    'api_url' => 'required_if:is_enabled,true|nullable|url',
                'evolution_api_key' => 'nullable|string',
            ]);
        } elseif ($providerType === 'sms') {
            $rules = array_merge($rules, [
                'sms_provider' => 'required_if:is_enabled,true|nullable|string',
                'account_sid' => 'required_if:is_enabled,true|nullable|string',
                'api_url' => 'required_if:is_enabled,true|nullable|url',
                'api_key' => 'nullable|string',
                'from_number' => 'nullable|string',
            ]);
        }
        
        $validated = $request->validate($rules);
        
        $provider = CreditCommunicationProvider::firstOrCreate(
            ['provider_type' => $providerType]
        );
        
        // Only update fields that are present in request
        foreach ($validated as $key => $value) {
            if ($value !== null) {
                $provider->$key = $value;
            }
        }
        
        // Update status
        if (isset($validated['is_enabled'])) {
            $provider->status = $validated['is_enabled'] ? 'configured' : 'unconfigured';
        }
        
        $provider->save();
        
        return response()->json([
            'success' => true,
            'message' => 'Provider configuration updated successfully',
            'provider' => $provider,
        ]);
    }

    /**
     * Test provider connection
     */
    public function test($providerType)
    {
        $provider = CreditCommunicationProvider::where('provider_type', $providerType)->firstOrFail();
        
        if (!$provider->is_enabled) {
            return response()->json([
                'success' => false,
                'message' => 'Provider is not enabled',
            ], 400);
        }
        
        try {
            // Test connection based on provider type
            $result = $this->testProviderConnection($provider);
            
            $provider->update([
                'status' => 'active',
                'last_tested_at' => now(),
                'error_message' => null,
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Provider connection successful',
                'result' => $result,
            ]);
            
        } catch (\Exception $e) {
            $provider->update([
                'status' => 'error',
                'error_message' => $e->getMessage(),
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Provider connection failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Test provider connection
     */
    private function testProviderConnection(CreditCommunicationProvider $provider)
    {
        switch ($provider->provider_type) {
            case 'whatsapp_meta':
                // Test Meta Cloud API
                $response = Http::withToken($provider->access_token)
                    ->get("{$provider->api_url}/{$provider->phone_number_id}");
                
                if (!$response->successful()) {
                    throw new \Exception('Meta Cloud API connection failed: ' . $response->body());
                }
                
                return [
                    'status' => 'connected',
                    'phone_number' => $response->json('display_phone_number'),
                    'verified' => $response->json('verified_name'),
                ];
                
            case 'whatsapp_evolution':
                // Test Evolution API
                $response = Http::withHeaders(['apikey' => $provider->evolution_api_key])
                    ->get("{$provider->api_url}/instance/connectionState/{$provider->instance_name}");
                
                if (!$response->successful()) {
                    throw new \Exception('Evolution API connection failed: ' . $response->body());
                }
                
                return [
                    'status' => 'connected',
                    'state' => $response->json('state'),
                    'instance' => $provider->instance_name,
                ];
                
            case 'sms':
                // Test SMS provider - simplified test
                // In production, you'd make an actual API call
                if (!$provider->api_url || !$provider->api_key) {
                    throw new \Exception('SMS API URL and API Key are required');
                }
                
                return [
                    'status' => 'configured',
                    'provider' => $provider->sms_provider,
                    'message' => 'SMS provider configured (test sending not implemented)',
                ];
                
            default:
                throw new \Exception('Unknown provider type');
        }
    }

    /**
     * Toggle provider enable/disable
     */
    public function toggle($providerType)
    {
        $provider = CreditCommunicationProvider::where('provider_type', $providerType)->firstOrFail();
        
        $provider->is_enabled = !$provider->is_enabled;
        $provider->status = $provider->is_enabled ? 'configured' : 'unconfigured';
        $provider->save();
        
        return response()->json([
            'success' => true,
            'is_enabled' => $provider->is_enabled,
            'message' => $provider->is_enabled ? 'Provider enabled' : 'Provider disabled',
        ]);
    }
}
