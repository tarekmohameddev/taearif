<?php

namespace App\Services;

use App\Models\BasicSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    protected $settings;

    public function __construct()
    {
        $this->settings = BasicSetting::first();
    }

    /**
     * Send WhatsApp message for password reset
     */
    public function sendPasswordResetCode($phoneNumber, $code, $userName = null)
    {
        if (!$this->settings || !$this->settings->whatsapp_service) {
            throw new \Exception('WhatsApp service not configured');
        }

        $service = $this->settings->whatsapp_service;

        switch ($service) {
            case 'meta_cloud':
                return $this->sendViaMetaCloud($phoneNumber, $code, $userName);
            case 'evolution_api':
                return $this->sendViaEvolutionApi($phoneNumber, $code, $userName);
            default:
                throw new \Exception('Unknown WhatsApp service: ' . $service);
        }
    }

    /**
     * Send message via Meta Cloud API
     */
    protected function sendViaMetaCloud($phoneNumber, $code, $userName = null)
    {
        try {
            $accessToken = $this->settings->meta_access_token;
            $phoneNumberId = $this->settings->meta_phone_number_id;
            $templateName = $this->settings->meta_template_name;
            $templateLanguage = $this->settings->meta_template_language;

            if (!$accessToken || !$phoneNumberId || !$templateName) {
                throw new \Exception('Meta Cloud API configuration incomplete');
            }

            // Format phone number (remove + and ensure it starts with country code)
            $formattedPhone = $this->formatPhoneNumber($phoneNumber);

            // Prepare template parameters
            $templateParams = [
                [
                    "type" => "text",
                    "text" => $code
                ]
            ];

            // Add user name if provided
            if ($userName) {
                array_unshift($templateParams, [
                    "type" => "text", 
                    "text" => $userName
                ]);
            }

            $payload = [
                "messaging_product" => "whatsapp",
                "to" => $formattedPhone,
                "type" => "template",
                "template" => [
                    "name" => $templateName,
                    "language" => [
                        "code" => $templateLanguage
                    ],
                    "components" => [
                        [
                            "type" => "body",
                            "parameters" => $templateParams
                        ]
                    ]
                ]
            ];

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
                'Content-Type' => 'application/json',
            ])->post("https://graph.facebook.com/v18.0/{$phoneNumberId}/messages", $payload);

            if ($response->successful()) {
                Log::info('WhatsApp message sent via Meta Cloud API', [
                    'phone' => $formattedPhone,
                    'template' => $templateName,
                    'response' => $response->json()
                ]);
                return true;
            } else {
                Log::error('Meta Cloud API error', [
                    'phone' => $formattedPhone,
                    'response' => $response->json(),
                    'status' => $response->status()
                ]);
                throw new \Exception('Failed to send WhatsApp message via Meta Cloud API');
            }

        } catch (\Exception $e) {
            Log::error('Meta Cloud API exception', [
                'error' => $e->getMessage(),
                'phone' => $phoneNumber
            ]);
            throw $e;
        }
    }

    /**
     * Send message via Evolution API
     */
    protected function sendViaEvolutionApi($phoneNumber, $code, $userName = null)
    {
        try {
            $apiUrl = $this->settings->evolution_api_url;
            $apiKey = $this->settings->evolution_api_key;
            $instanceName = $this->settings->evolution_instance_name;
            $fromPhone = $this->settings->evolution_phone_number;

            if (!$apiUrl || !$apiKey || !$instanceName || !$fromPhone) {
                throw new \Exception('Evolution API configuration incomplete');
            }

            // Format phone number
            $formattedPhone = $this->formatPhoneNumber($phoneNumber);

            // Prepare message content
            $message = "رمز إعادة تعيين كلمة المرور: {$code}";
            if ($userName) {
                $message = "مرحباً {$userName},\n\n" . $message;
            }

            $payload = [
                "number" => $formattedPhone,
                "text" => $message,
                "options" => [
                    "delay" => 1200,
                    "presence" => "composing"
                ]
            ];

            $response = Http::withHeaders([
                'apikey' => $apiKey,
                'Content-Type' => 'application/json',
            ])->post("{$apiUrl}/message/sendText/{$instanceName}", $payload);

            if ($response->successful()) {
                Log::info('WhatsApp message sent via Evolution API', [
                    'phone' => $formattedPhone,
                    'instance' => $instanceName,
                    'response' => $response->json()
                ]);
                return true;
            } else {
                Log::error('Evolution API error', [
                    'phone' => $formattedPhone,
                    'response' => $response->json(),
                    'status' => $response->status()
                ]);
                throw new \Exception('Failed to send WhatsApp message via Evolution API');
            }

        } catch (\Exception $e) {
            Log::error('Evolution API exception', [
                'error' => $e->getMessage(),
                'phone' => $phoneNumber
            ]);
            throw $e;
        }
    }

    /**
     * Format phone number for WhatsApp
     */
    protected function formatPhoneNumber($phoneNumber)
    {
        // Remove all non-numeric characters except +
        $phone = preg_replace('/[^0-9+]/', '', $phoneNumber);
        
        // Remove + if present
        $phone = ltrim($phone, '+');
        
        // Ensure it doesn't start with 0
        if (strpos($phone, '0') === 0) {
            $phone = substr($phone, 1);
        }
        
        return $phone;
    }

    /**
     * Test WhatsApp service configuration
     */
    public function testConfiguration()
    {
        if (!$this->settings || !$this->settings->whatsapp_service) {
            return [
                'status' => 'error',
                'message' => 'WhatsApp service not configured'
            ];
        }

        $service = $this->settings->whatsapp_service;

        switch ($service) {
            case 'meta_cloud':
                return $this->testMetaCloudConfiguration();
            case 'evolution_api':
                return $this->testEvolutionApiConfiguration();
            default:
                return [
                    'status' => 'error',
                    'message' => 'Unknown WhatsApp service: ' . $service
                ];
        }
    }

    /**
     * Test Meta Cloud API configuration
     */
    protected function testMetaCloudConfiguration()
    {
        $requiredFields = [
            'meta_access_token' => 'Access Token',
            'meta_phone_number_id' => 'Phone Number ID',
            'meta_template_name' => 'Template Name',
            'meta_template_language' => 'Template Language'
        ];

        foreach ($requiredFields as $field => $label) {
            if (empty($this->settings->$field)) {
                return [
                    'status' => 'error',
                    'message' => "Missing required field: {$label}"
                ];
            }
        }

        return [
            'status' => 'success',
            'message' => 'Meta Cloud API configuration is complete'
        ];
    }

    /**
     * Test Evolution API configuration
     */
    protected function testEvolutionApiConfiguration()
    {
        $requiredFields = [
            'evolution_api_url' => 'API URL',
            'evolution_api_key' => 'API Key',
            'evolution_instance_name' => 'Instance Name',
            'evolution_phone_number' => 'Phone Number'
        ];

        foreach ($requiredFields as $field => $label) {
            if (empty($this->settings->$field)) {
                return [
                    'status' => 'error',
                    'message' => "Missing required field: {$label}"
                ];
            }
        }

        return [
            'status' => 'success',
            'message' => 'Evolution API configuration is complete'
        ];
    }
}
