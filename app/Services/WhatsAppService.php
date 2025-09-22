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
    public function sendPasswordResetCode($phoneNumber, $code, $userName = null, $userLanguage = 'ar', $resetUrl = null, $templateName = null)
    {
        // If WhatsApp service is not configured or disabled, use default message
        if (!$this->settings || !$this->settings->whatsapp_service || !$this->settings->password_reset_enabled) {
            // Prepare default message
            $message = "رمز إعادة تعيين كلمة المرور: {$code}\n\nهذا الرمز صالح لمدة 15 دقيقة.";
            
            if ($resetUrl) {
                $message .= "\n\nأو يمكنك الضغط على الرابط التالي:\n{$resetUrl}?code={$code}";
            }
            
            // Log that we're using default message due to service not being configured
            Log::info('WhatsApp service not configured, using default message format', [
                'phone' => $phoneNumber,
                'code' => $code,
                'service_configured' => $this->settings && $this->settings->whatsapp_service ? true : false,
                'password_reset_enabled' => $this->settings && $this->settings->password_reset_enabled ? true : false
            ]);
            
            // Return the default message (frontend can handle this as a fallback)
            return $message;
        }

        // Get template - first try with specific template name, then with user language, then fallback
        $template = null;
        $useMetaTemplate = false;
        
        // First, try to get the configured template from settings
        if ($this->settings->password_reset_template) {
            $template = \App\Models\WhatsAppTemplate::where('name', $this->settings->password_reset_template)
                ->where('type', 'password_reset')
                ->where('status', true)
                ->first();
        }
        
        // If no configured template found, try with specific template name parameter
        if (!$template && $templateName) {
            $template = \App\Models\WhatsAppTemplate::where('name', $templateName)
                ->where('type', 'password_reset')
                ->where('status', true)
                ->first();
        }
        
        // If no specific template found, try to get template by user language
        if (!$template) {
            $template = \App\Models\WhatsAppTemplate::where('type', 'password_reset')
                ->where('language', $userLanguage)
                ->where('status', true)
                ->orderBy('created_at', 'desc')
                ->first();
        }
        
        // If still no template found, try Arabic as fallback
        if (!$template && $userLanguage !== 'ar') {
            $template = \App\Models\WhatsAppTemplate::where('type', 'password_reset')
                ->where('language', 'ar')
                ->where('status', true)
                ->orderBy('created_at', 'desc')
                ->first();
        }
        
        // If still no template found and using Meta Cloud, check for password_reset template
        if (!$template && $this->settings->whatsapp_service === 'meta_cloud') {
            if ($this->checkMetaTemplateExists('password_reset')) {
                $useMetaTemplate = true;
            }
        }

        // Prepare message content
        if ($template) {
            $message = $template->content;
            
            // Replace variables
            $message = str_replace('{code}', $code, $message);
            $message = str_replace('{name}', $userName ?? 'User', $message);
            if ($resetUrl) {
                $message = str_replace('{reset_url}', $resetUrl, $message);
            }
        } else {
            // Use configured custom message or fallback to default
            $message = $this->settings->password_reset_text ?? "رمز إعادة تعيين كلمة المرور: {$code}\n\nهذا الرمز صالح لمدة 15 دقيقة.\n\nأو يمكنك الضغط على الرابط التالي:\n{$resetUrl}?code={$code}";
            
            // Replace variables in custom message
            $message = str_replace('{code}', $code, $message);
            $message = str_replace('{name}', $userName ?? 'User', $message);
            if ($resetUrl) {
                $message = str_replace('{reset_url}', $resetUrl, $message);
                $message = str_replace('{reset_link}', $resetUrl . '?code=' . $code, $message);
            }
        }

        $service = $this->settings->whatsapp_service;

        switch ($service) {
            case 'meta_cloud':
                return $this->sendViaMetaCloud($phoneNumber, $code, $userName, $resetUrl, $message, $useMetaTemplate);
            case 'evolution_api':
                return $this->sendViaEvolutionApi($phoneNumber, $code, $userName, $resetUrl, $message);
            default:
                throw new \Exception('Unknown WhatsApp service: ' . $service);
        }
    }

    /**
     * Send message via Meta Cloud API
     */
    protected function sendViaMetaCloud($phoneNumber, $code, $userName = null, $resetUrl = null, $message = null, $useMetaTemplate = false)
    {
        try {
            $accessToken = $this->settings->meta_access_token;
            $phoneNumberId = $this->settings->meta_phone_number_id;
            $templateName = $this->settings->meta_template_name;
            $templateLanguage = $this->settings->meta_template_language;

            if (!$accessToken || !$phoneNumberId) {
                throw new \Exception('Meta Cloud API configuration incomplete');
            }

            // Format phone number (remove + and ensure it starts with country code)
            $formattedPhone = $this->formatPhoneNumber($phoneNumber);

            // Auto-select password_reset template if no specific template is configured
            if (!$templateName && !$message && $useMetaTemplate) {
                $templateName = 'password_reset';
            }

            // If no template name is provided or custom message is provided, send as regular message
            if (!$templateName || $message) {
                $payload = [
                    "messaging_product" => "whatsapp",
                    "to" => $formattedPhone,
                    "type" => "text",
                    "text" => [
                        "body" => $message ?: "رمز إعادة تعيين كلمة المرور: {$code}"
                    ]
                ];
            } else {
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

                // Add reset URL if provided
                if ($resetUrl) {
                    $templateParams[] = [
                        "type" => "text",
                        "text" => $resetUrl
                    ];
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
            }

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
                'Content-Type' => 'application/json',
            ])->post("https://graph.facebook.com/v20.0/{$phoneNumberId}/messages", $payload);

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
    protected function sendViaEvolutionApi($phoneNumber, $code, $userName = null, $resetUrl = null, $message = null)
    {
        try {
            $apiUrl = $this->settings->evolution_api_url;
            $apiKey = $this->settings->evolution_api_key;
            $instanceName = $this->settings->evolution_instance_name;

            if (!$apiUrl || !$apiKey || !$instanceName) {
                throw new \Exception('Evolution API configuration incomplete');
            }

            // Format phone number
            $formattedPhone = $this->formatPhoneNumber($phoneNumber);
            
            // Log the phone number formatting for debugging
            Log::info('Phone number formatting', [
                'original' => $phoneNumber,
                'formatted' => $formattedPhone
            ]);

            // Use custom message if provided, otherwise prepare default message
            if (!$message) {
                $message = "رمز إعادة تعيين كلمة المرور: {$code}\n\nهذا الرمز صالح لمدة 15 دقيقة.";
                
                // Add reset URL if provided (only code, no identifier)
                if ($resetUrl) {
                    $message .= "\n\nأو يمكنك الضغط على الرابط التالي:\n{$resetUrl}?code={$code}";
                }
            }

            $payload = [
                "number" => $formattedPhone,
                "text" => $message,
                "options" => [
                    "delay" => 1200,
                    "presence" => "composing"
                ]
            ];

            $endpoint = "{$apiUrl}/message/sendText/{$instanceName}";
            
            Log::info('Evolution API Request Details', [
                'endpoint' => $endpoint,
                'payload' => $payload,
                'api_key_length' => strlen($apiKey)
            ]);

            $response = Http::withHeaders([
                'apikey' => $apiKey,
                'Content-Type' => 'application/json',
            ])->post($endpoint, $payload);

            if ($response->successful()) {
                Log::info('WhatsApp message sent via Evolution API', [
                    'phone' => $formattedPhone,
                    'instance' => $instanceName,
                    'response' => $response->json()
                ]);
                return true;
            } else {
                $errorResponse = $response->json();
                $errorMessage = $errorResponse['message'] ?? $errorResponse['error'] ?? 'Unknown error';
                
                Log::error('Evolution API error', [
                    'phone' => $formattedPhone,
                    'endpoint' => $endpoint,
                    'response' => $errorResponse,
                    'status' => $response->status(),
                    'error_message' => $errorMessage
                ]);
                throw new \Exception("Evolution API Error: {$errorMessage} (Status: {$response->status()})");
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
        
        // Handle Egyptian phone numbers first (most specific)
        // If it's 11 digits starting with 01, remove the 0 and add Egyptian country code 20
        if (preg_match('/^01\d{9}$/', $phone)) {
            $phone = '20' . substr($phone, 1);
        }
        // Handle Saudi Arabian phone numbers
        // If it's a 9-digit number starting with 5, add Saudi country code 966
        elseif (preg_match('/^5\d{8}$/', $phone)) {
            $phone = '966' . $phone;
        }
        // Handle Saudi numbers with leading 0 (10 digits starting with 05)
        // If it's 10 digits starting with 05, remove the 0 and add country code
        elseif (preg_match('/^05\d{8}$/', $phone)) {
            $phone = '966' . substr($phone, 1);
        }
        // Handle Saudi numbers with leading 0 (11 digits starting with 05)
        // If it's 11 digits starting with 05, remove the 0 and add country code
        elseif (preg_match('/^05\d{9}$/', $phone)) {
            $phone = '966' . substr($phone, 1);
        }
        // Handle Saudi landline numbers (7 digits starting with 1, 2, 3, 4, 6, 7, 8, 9)
        // If it's 7 digits starting with 1-9 (except 5), add Saudi country code 966
        elseif (preg_match('/^[1-46-9]\d{6}$/', $phone)) {
            $phone = '966' . $phone;
        }
        // Handle Saudi landline numbers with leading 0 (8 digits starting with 01-09 except 05)
        // If it's 8 digits starting with 01-09 (except 05), remove the 0 and add country code
        elseif (preg_match('/^0[1-46-9]\d{6}$/', $phone)) {
            $phone = '966' . substr($phone, 1);
        }
        // Handle different phone number formats (generic case)
        // If the number starts with a country code followed by 0, remove the 0
        // Examples: 2001147170572 -> 201147170572, 966501234567 -> 966501234567
        elseif (preg_match('/^(\d{1,4})0(\d+)$/', $phone, $matches)) {
            $countryCode = $matches[1];
            $localNumber = $matches[2];
            
            // For common country codes, remove the leading 0 from local number
            $commonCountryCodes = ['20', '966', '1', '44', '33', '49', '39', '34', '7', '81', '86', '91'];
            
            if (in_array($countryCode, $commonCountryCodes)) {
                $phone = $countryCode . $localNumber;
            }
        }
        
        return $phone;
    }

    /**
     * Clean message content for WhatsApp template parameters
     * WhatsApp doesn't allow newlines, tabs, or more than 4 consecutive spaces
     */
    protected function cleanMessageForWhatsApp($message)
    {
        // Replace newlines and tabs with spaces
        $message = str_replace(["\r\n", "\r", "\n", "\t"], ' ', $message);
        
        // Replace multiple consecutive spaces with single space
        $message = preg_replace('/\s+/', ' ', $message);
        
        // Trim leading and trailing spaces
        $message = trim($message);
        
        return $message;
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
            'meta_business_account_id' => 'Business Account ID',
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
            'evolution_instance_name' => 'Instance Name'
        ];

        foreach ($requiredFields as $field => $label) {
            if (empty($this->settings->$field)) {
                return [
                    'status' => 'error',
                    'message' => "Missing required field: {$label}"
                ];
            }
        }

        // Test the actual API connection
        try {
            $apiUrl = $this->settings->evolution_api_url;
            $apiKey = $this->settings->evolution_api_key;
            $instanceName = $this->settings->evolution_instance_name;
            
            // Test connection to Evolution API
            $response = Http::withHeaders([
                'apikey' => $apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(10)->get("{$apiUrl}/instance/connectionState/{$instanceName}");
            
            if ($response->successful()) {
                $responseData = $response->json();
                $connectionState = $responseData['instance']['state'] ?? 'unknown';
                
                return [
                    'status' => 'success',
                    'message' => "Evolution API configuration is complete. Instance state: {$connectionState}"
                ];
            } else {
                return [
                    'status' => 'error',
                    'message' => 'Evolution API connection failed: ' . $response->status()
                ];
            }
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Evolution API connection test failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Send welcome message to new user
     */
    public function sendWelcomeMessage($phoneNumber, $message, $userName = null)
    {
        $message = str_replace('{name}', $userName ?? 'User', $message);
        
        if ($this->settings->whatsapp_service === 'meta_cloud') {
            return $this->sendMetaCloudMessage($phoneNumber, $message, 'welcome');
        } elseif ($this->settings->whatsapp_service === 'evolution_api') {
            return $this->sendViaEvolutionApi($phoneNumber, $message, $userName);
        }
        
        throw new \Exception('No WhatsApp service configured');
    }

    /**
     * Send subscription expiration message
     */
    public function sendSubscriptionExpirationMessage($phoneNumber, $message, $userName = null, $packageName = null, $expiryDate = null)
    {
        $message = str_replace('{name}', $userName ?? 'User', $message);
        $message = str_replace('{package_name}', $packageName ?? 'Package', $message);
        $message = str_replace('{expiry_date}', $expiryDate ?? 'N/A', $message);
        
        if ($this->settings->whatsapp_service === 'meta_cloud') {
            return $this->sendMetaCloudMessage($phoneNumber, $message, 'subscription_expiration');
        } elseif ($this->settings->whatsapp_service === 'evolution_api') {
            return $this->sendViaEvolutionApi($phoneNumber, $message, $userName);
        }
        
        throw new \Exception('No WhatsApp service configured');
    }

    /**
     * Send subscription expired message (on expiration day)
     */
    public function sendSubscriptionExpiredMessage($phoneNumber, $message, $userName = null, $packageName = null, $expiryDate = null)
    {
        $message = str_replace('{name}', $userName ?? 'User', $message);
        $message = str_replace('{package_name}', $packageName ?? 'Package', $message);
        $message = str_replace('{expiry_date}', $expiryDate ?? 'N/A', $message);
        
        if ($this->settings->whatsapp_service === 'meta_cloud') {
            return $this->sendMetaCloudMessage($phoneNumber, $message, 'subscription_expired');
        } elseif ($this->settings->whatsapp_service === 'evolution_api') {
            return $this->sendViaEvolutionApi($phoneNumber, $message, $userName);
        }
        
        throw new \Exception('No WhatsApp service configured');
    }

    /**
     * Send Meta Cloud message with template support
     */
    protected function sendMetaCloudMessage($phoneNumber, $message, $messageType = 'default')
    {
        $templateName = null;
        $templateContent = null;
        
        // Clean message for WhatsApp template parameters
        $message = $this->cleanMessageForWhatsApp($message);
        
        Log::info('Meta Cloud message processing', [
            'phone' => $phoneNumber,
            'message_type' => $messageType,
            'message' => $message
        ]);
        
        // Get template name based on message type
        if ($messageType === 'welcome' && !empty($this->settings->welcome_message_template)) {
            $templateName = $this->settings->welcome_message_template;
        } elseif ($messageType === 'subscription_expiration' && !empty($this->settings->subscription_expiration_template)) {
            $templateName = $this->settings->subscription_expiration_template;
        } elseif ($messageType === 'subscription_expired' && !empty($this->settings->subscription_expired_template)) {
            $templateName = $this->settings->subscription_expired_template;
        } elseif ($messageType === 'password_reset' && !empty($this->settings->meta_template_name)) {
            $templateName = $this->settings->meta_template_name;
        }

        Log::info('Template selection', [
            'message_type' => $messageType,
            'template_name' => $templateName,
            'welcome_template_setting' => $this->settings->welcome_message_template ?? 'not_set'
        ]);

        // If template name is provided, try to get template content from database
        if ($templateName) {
            $template = \App\Models\WhatsAppTemplate::where('name', $templateName)->first();
            if ($template && $template->status) {
                $templateContent = $template->content;
                Log::info('Found database template', [
                    'template_name' => $templateName,
                    'template_content' => $templateContent
                ]);
            } else {
                Log::info('Template not found in database or inactive', [
                    'template_name' => $templateName,
                    'template_found' => $template ? 'yes' : 'no',
                    'template_status' => $template ? $template->status : 'N/A'
                ]);
            }
        }

        // Format phone number
        $formattedPhone = $this->formatPhoneNumber($phoneNumber);

        Log::info('Sending decision', [
            'formatted_phone' => $formattedPhone,
            'template_name' => $templateName,
            'template_content' => $templateContent ? 'has_content' : 'no_content',
            'sending_method' => $templateName && $templateContent ? 'database_template_as_regular' : 
                              ($templateName ? 'meta_template_or_regular' : 'regular_message')
        ]);

        if ($templateName && $templateContent) {
            // Send as regular message using database template content
            Log::info('Using database template content for regular message', [
                'template_name' => $templateName,
                'template_content' => $templateContent
            ]);
            return $this->sendRegularMessage($formattedPhone, $templateContent);
        } elseif ($templateName) {
            // Check if template exists in Meta Cloud API
            if ($this->checkMetaTemplateExists($templateName)) {
                // Send as template message using Meta Cloud API
                return $this->sendTemplateMessage($formattedPhone, $templateName, $message);
            } else {
                // Template doesn't exist in Meta Cloud, send as regular message
                Log::info('Template not found in Meta Cloud, sending as regular message', [
                    'template_name' => $templateName,
                    'message' => $message
                ]);
                return $this->sendRegularMessage($formattedPhone, $message);
            }
        } else {
            // Send as regular message
            return $this->sendRegularMessage($formattedPhone, $message);
        }
    }

    /**
     * Send template message via Meta Cloud API
     */
    protected function sendTemplateMessage($phoneNumber, $templateName, $message)
    {
        try {
            $url = "https://graph.facebook.com/v20.0/{$this->settings->meta_phone_number_id}/messages";
            
            $data = [
                'messaging_product' => 'whatsapp',
                'to' => $phoneNumber,
                'type' => 'template',
                'template' => [
                    'name' => $templateName,
                    'language' => [
                        'code' => $this->settings->meta_template_language ?? 'ar'
                    ],
                    'components' => [
                        [
                            'type' => 'body',
                            'parameters' => [
                                [
                                    'type' => 'text',
                                    'text' => $message
                                ]
                            ]
                        ]
                    ]
                ]
            ];

            Log::info('Meta Cloud template message request', [
                'phone' => $phoneNumber,
                'template_name' => $templateName,
                'message' => $message,
                'url' => $url,
                'data' => $data
            ]);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->settings->meta_access_token,
                'Content-Type' => 'application/json',
            ])->post($url, $data);

            if ($response->successful()) {
                Log::info('Meta Cloud template message sent successfully', [
                    'phone' => $phoneNumber,
                    'template_name' => $templateName,
                    'response' => $response->json()
                ]);
                return true;
            } else {
                Log::error('Meta Cloud template message failed', [
                    'phone' => $phoneNumber,
                    'template_name' => $templateName,
                    'response' => $response->json(),
                    'status' => $response->status()
                ]);
                throw new \Exception('Failed to send WhatsApp template message: ' . $response->body());
            }
        } catch (\Exception $e) {
            Log::error('Meta Cloud template message exception', [
                'phone' => $phoneNumber,
                'template_name' => $templateName,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Send regular message via Meta Cloud API
     */
    protected function sendRegularMessage($phoneNumber, $message)
    {
        try {
            $url = "https://graph.facebook.com/v20.0/{$this->settings->meta_phone_number_id}/messages";
            
            $data = [
                'messaging_product' => 'whatsapp',
                'to' => $phoneNumber,
                'type' => 'text',
                'text' => [
                    'body' => $message
                ]
            ];

            Log::info('Meta Cloud regular message request', [
                'phone' => $phoneNumber,
                'message' => $message,
                'url' => $url,
                'data' => $data
            ]);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->settings->meta_access_token,
                'Content-Type' => 'application/json',
            ])->post($url, $data);

            if ($response->successful()) {
                Log::info('Meta Cloud regular message sent successfully', [
                    'phone' => $phoneNumber,
                    'response' => $response->json()
                ]);
                return true;
            } else {
                Log::error('Meta Cloud regular message failed', [
                    'phone' => $phoneNumber,
                    'response' => $response->json(),
                    'status' => $response->status()
                ]);
                throw new \Exception('Failed to send WhatsApp regular message: ' . $response->body());
            }
        } catch (\Exception $e) {
            Log::error('Meta Cloud regular message exception', [
                'phone' => $phoneNumber,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Fetch WhatsApp templates from Facebook Meta API
     */
    public function fetchMetaTemplates()
    {
        try {
            $accessToken = $this->settings->meta_access_token;
            $businessAccountId = $this->settings->meta_business_account_id;

            if (!$accessToken || !$businessAccountId) {
                throw new \Exception('Meta Cloud API configuration incomplete');
            }

            $url = "https://graph.facebook.com/v20.0/{$businessAccountId}/message_templates";
            
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
                'Content-Type' => 'application/json',
            ])->get($url, [
                'fields' => 'name,status,category,language,components'
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $templates = [];
                
                if (isset($data['data'])) {
                    foreach ($data['data'] as $template) {
                        // Only include approved templates
                        if ($template['status'] === 'APPROVED') {
                            $templates[] = [
                                'name' => $template['name'],
                                'category' => $template['category'],
                                'language' => $template['language'],
                                'status' => $template['status']
                            ];
                        }
                    }
                }
                
                return $templates;
            } else {
                Log::error('Meta API template fetch error', [
                    'response' => $response->json(),
                    'status' => $response->status()
                ]);
                throw new \Exception('Failed to fetch templates from Meta API');
            }

        } catch (\Exception $e) {
            Log::error('Meta API template fetch exception', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Check if a specific Meta template exists
     */
    public function checkMetaTemplateExists($templateName)
    {
        try {
            $templates = $this->fetchMetaTemplates();
            
            foreach ($templates as $template) {
                if ($template['name'] === $templateName && $template['status'] === 'APPROVED') {
                    return true;
                }
            }
            
            return false;
        } catch (\Exception $e) {
            // If we can't fetch templates, assume template doesn't exist
            return false;
        }
    }

    /**
     * Send test message using selected template
     */
    public function sendTestMessage($phoneNumber, $templateName, $language = 'ar')
    {
        try {
            if (!$this->settings) {
                return [
                    'success' => false,
                    'message' => 'WhatsApp service not configured'
                ];
            }

            if ($this->settings->whatsapp_service === 'meta_cloud') {
                return $this->sendMetaTestMessage($phoneNumber, $templateName, $language);
            } elseif ($this->settings->whatsapp_service === 'evolution_api') {
                return $this->sendEvolutionTestMessage($phoneNumber, $templateName, $language);
            }

            return [
                'success' => false,
                'message' => 'Unknown WhatsApp service type'
            ];

        } catch (\Exception $e) {
            Log::error('WhatsApp test message error', [
                'phone' => $phoneNumber,
                'template' => $templateName,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Error sending test message: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Send test message using Meta Cloud API
     */
    protected function sendMetaTestMessage($phoneNumber, $templateName, $language = 'ar')
    {
        $accessToken = $this->settings->meta_access_token;
        $phoneNumberId = $this->settings->meta_phone_number_id;

        if (!$accessToken || !$phoneNumberId) {
            return [
                'success' => false,
                'message' => 'Meta Cloud API configuration incomplete'
            ];
        }

        $url = "https://graph.facebook.com/v20.0/{$phoneNumberId}/messages";

        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $phoneNumber,
            'type' => 'template',
            'template' => [
                'name' => $templateName,
                'language' => [
                    'code' => $language
                ]
            ]
        ];

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $accessToken,
            'Content-Type' => 'application/json',
        ])->post($url, $payload);

        if ($response->successful()) {
            Log::info('Meta test message sent successfully', [
                'phone' => $phoneNumber,
                'template' => $templateName,
                'response' => $response->json()
            ]);

            return [
                'success' => true,
                'message' => 'Test message sent successfully using template: ' . $templateName
            ];
        } else {
            $errorResponse = $response->json();
            Log::error('Meta test message failed', [
                'phone' => $phoneNumber,
                'template' => $templateName,
                'response' => $errorResponse,
                'status' => $response->status()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to send test message: ' . ($errorResponse['error']['message'] ?? 'Unknown error')
            ];
        }
    }

    /**
     * Send test message using Evolution API
     */
    protected function sendEvolutionTestMessage($phoneNumber, $templateName, $language = 'ar')
    {
        $apiUrl = $this->settings->evolution_api_url;
        $apiKey = $this->settings->evolution_api_key;
        $instanceName = $this->settings->evolution_instance_name;

        if (!$apiUrl || !$apiKey || !$instanceName) {
            return [
                'success' => false,
                'message' => 'Evolution API configuration incomplete'
            ];
        }

        $url = rtrim($apiUrl, '/') . '/message/sendText/' . $instanceName;

        // For Evolution API, we'll send a simple text message with template info
        $message = "Test message using template: {$templateName} (Language: {$language})";

        $payload = [
            'number' => $phoneNumber,
            'text' => $message
        ];

        $response = Http::withHeaders([
            'apikey' => $apiKey,
            'Content-Type' => 'application/json',
        ])->post($url, $payload);

        if ($response->successful()) {
            Log::info('Evolution test message sent successfully', [
                'phone' => $phoneNumber,
                'template' => $templateName,
                'response' => $response->json()
            ]);

            return [
                'success' => true,
                'message' => 'Test message sent successfully using template: ' . $templateName
            ];
        } else {
            $errorResponse = $response->json();
            Log::error('Evolution test message failed', [
                'phone' => $phoneNumber,
                'template' => $templateName,
                'response' => $errorResponse,
                'status' => $response->status()
            ]);

            return [
                'success' => false,
                'message' => 'Failed to send test message: ' . ($errorResponse['message'] ?? 'Unknown error')
            ];
        }
    }
}
