<?php

namespace App\Http\Controllers\Api\apps\whatsapp;

use App\Http\Controllers\Controller;
use App\Models\Embedding;
use App\Models\User\RealestateManagement\Property as Property;
use App\Models\User\RealestateManagement\PropertyContent as PropertyContent;
use App\Models\User\RealestateManagement\UserPropertyCharacteristic as PropertyChar;
use App\Models\ChatHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use App\Models\User\UserDistrict;
use App\Models\User\RealestateManagement\ApiUserCategory;
use App\Models\User\UserCity;
use App\Models\ApiCustomer;
use App\Models\Api\ApiCustomerInquiry;
use App\Models\Api\UserPropertyRequest;
use App\Domain\PropertyRequests\Services\PropertyRequestLocationNormalizer;
use App\Models\User;
use App\Models\WhatsappUser;
use App\Domain\Communication\Contracts\CommunicationService;
use App\Domain\Communication\WhatsApp\Services\WhatsAppWebhookService;
use App\Domain\CustomersHub\Services\IgnoredCustomersService;
use Illuminate\Support\Str;


use OpenAI as OpenAIClient;
use App\Http\Requests\Api\Apps\Whatsapp\ChatRequest;


class ChatController extends Controller
{

    protected int $maxTurns = 10;
    protected string $systemInstructions;
    protected string $evolutionApiUrl;
    protected string $evolutionApiKey;
    protected string $evolutionApiInstance;

    protected CommunicationService $communicationService;
    protected WhatsAppWebhookService $whatsAppWebhookService;

    public function __construct(
        CommunicationService $communicationService,
        WhatsAppWebhookService $whatsAppWebhookService
    )
    {
        $this->communicationService = $communicationService;
        $this->whatsAppWebhookService = $whatsAppWebhookService;
        $this->openai = OpenAIClient::client(env('OPENAI_API_KEY'));
        $this->systemInstructions = implode("\n", [
            'أنت موظف دعم عملاء في شركة إدارة عقارات في السعودية.',
            '– ردودك ودية ودافئة، مع الجدية والوضوح.',
            '– استخدم جمل بسيطة لا تتجاوز 3 أسطر.',
            '_ slug لما تلاقي عقار, رد ب رابط العقار الموجود',
            '_ لو مافي عقارات رد ب طلبك غير متوفر حالياً',
            '– خارج العقارات: "عذرًا، أقدر أساعد بس في أمور إدارة العقارات."'
        ]);

        $this->evolutionApiUrl = rtrim(env('EVOLUTION_API_URL'), '/');
        $this->evolutionApiKey = env('EVOLUTION_API_KEY');
        $this->evolutionApiInstance = env('EVOLUTION_API_INSTANCE');
    }

    protected function sendWhatsappMessage(string $recipientNumber, string $messageText)
    {
        if (empty($this->evolutionApiUrl) || empty($this->evolutionApiKey) || empty($this->evolutionApiInstance)) {
         //   Log::error('Evolution API URL, Key, or Instance not configured.');
            return false;
        }

        $endpoint = $this->evolutionApiUrl . '/message/sendText/' . $this->evolutionApiInstance;

        try {
            $http = Http::withHeaders([
                'apikey' => $this->evolutionApiKey,
                'Content-Type' => 'application/json',
            ]);

            if (env('EVOLUTION_API_VERIFY_SSL', true) === false) {
                $http = $http->withoutVerifying();
            }

            $response = $http->post($endpoint, [
                'number' => $recipientNumber,
                'options' => [
                    'delay' => 600,
                    'presence' => 'composing',
                 ],
               'text' => $messageText,
            ]);

            if ($response->successful()) {
                Log::info('Message sent successfully via Evolution API: ' . $response->body());
                return true;
            } else {
                Log::error('Failed to send message via Evolution API: ' . $response->status() . ' - ' . $response->body());
                return false;
            }
        } catch (\Exception $e) {
            Log::error('Exception sending message via Evolution API: ' . $e->getMessage());
            return false;
        }
    }

// ... (inside ChatController class)

public function handleEvolutionWebhook(Request $request)
    {
        Log::info('Evolution webhook: handler hit', ['has_data' => $request->has('data')]);

    $payload = $request->all();
 //Log::info('Evolution API Webhook received: ' . json_encode($payload));
    // ---- VALIDATE THE WEBHOOK (IMPORTANT FOR SECURITY) ----
    // Evolution API might have a way to verify webhooks (e.g., a secret token in headers).
    // Implement verification if available. For example:
    // $expectedToken = env('EVOLUTION_WEBHOOK_TOKEN');
    // if ($request->header('X-Evolution-Token') !== $expectedToken) {
    //     Log::warning('Invalid webhook token.');
    //     return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
    // }

    // Extract message details (this depends on Evolution API's webhook payload structure)
    // Common fields might include:
    // $messageType = $payload['event'] ?? null; // e.g., 'messages.upsert', 'messages.update'
    // $instance = $payload['instance'] ?? null;
    $data = $payload['data'] ?? null; // This usually contains the message details

    if (isset($data['key']['fromMe']) && $data['key']['fromMe'] === true) {
        Log::info('Ignoring own outgoing message from webhook.');
        return response()->json(['status' => 'ignored_own_message']);
    }

    $senderNumber = $data['key']['remoteJid'] ?? null; // Sender's WhatsApp ID (e.g., 1234567890@s.whatsapp.net)
    $messageContent = null;

    if (isset($data['message']['conversation'])) {
        $messageContent = $data['message']['conversation'];
    } elseif (isset($data['message']['extendedTextMessage']['text'])) {
        $messageContent = $data['message']['extendedTextMessage']['text'];
    }
    // Add handling for other message types if needed (images, audio, etc.)

    if ($senderNumber && $messageContent) {
        // Clean sender number if it has @s.whatsapp.net
        $senderNumber = str_replace('@s.whatsapp.net', '', $senderNumber);

        $resolvedTenant = $this->whatsAppWebhookService->resolveTenantFromPayload([
            'provider_account_id' => $payload['instance'] ?? null,
            'instance' => $payload['instance'] ?? null,
        ], 'evolution');

        $tenantOwnerId = null;
        $resolvedWaNumberId = null;
        if ($resolvedTenant !== null) {
            $resolvedUser = User::find((int) $resolvedTenant['user_id']);
            $tenantOwnerId = $resolvedUser && method_exists($resolvedUser, 'tenantOwnerId')
                ? (int) $resolvedUser->tenantOwnerId()
                : (int) $resolvedTenant['user_id'];
            $resolvedWaNumberId = (int) $resolvedTenant['wa_number_id'];
        }

        $evolutionNumber = config('communication.evolution_instance_number');
        if ($tenantOwnerId === null && $evolutionNumber) {
            $whatsappUser = WhatsappUser::where('number', $evolutionNumber)->first();
            if (! $whatsappUser) {
                $normalizedConfig = preg_replace('/\D/', '', (string) $evolutionNumber);
                if ($normalizedConfig !== '') {
                    $whatsappUser = WhatsappUser::whereNotNull('number')->get()->first(function ($wu) use ($normalizedConfig) {
                        return preg_replace('/\D/', '', (string) $wu->number) === $normalizedConfig;
                    });
                }
            }
            if ($whatsappUser) {
                $owner = User::find($whatsappUser->user_id);
                $tenantOwnerId = $owner && method_exists($owner, 'tenantOwnerId') ? $owner->tenantOwnerId() : null;
            }
        }

        Log::info('Evolution webhook: received', [
            'evolution_instance_number_config' => $evolutionNumber ?: '(empty)',
            'tenant_owner_id' => $tenantOwnerId,
            'wa_number_id' => $resolvedWaNumberId,
            'provider_message_id' => $data['key']['id'] ?? null,
            'sender' => $senderNumber,
        ]);

        if ($tenantOwnerId !== null) {
            $providerMessageId = $data['key']['id'] ?? null;
            Log::info('Evolution webhook: processing inbound message', [
                'tenant_owner_id' => $tenantOwnerId,
                'provider_message_id' => $providerMessageId,
                'sender' => $senderNumber,
                'content_length' => strlen($messageContent),
            ]);
            try {
                $this->communicationService->recordInboundMessage(
                    userId: (int) $tenantOwnerId,
                    externalPartyIdentifier: $senderNumber,
                    content: $messageContent,
                    channel: 'whatsapp',
                    providerMessageId: $providerMessageId,
                    meta: array_filter([
                        'source' => 'evolution_webhook',
                        'wa_number_id' => $resolvedWaNumberId,
                        'context' => ['instance' => $this->evolutionApiInstance ?? ''],
                    ], static fn ($value) => $value !== null)
                );
            } catch (\Throwable $e) {
                Log::warning('Evolution webhook: recordInboundMessage failed', ['message' => $e->getMessage()]);
            }

            $this->runChatFromPayload($messageContent, (int) $tenantOwnerId, $senderNumber);
        }

        return response()->json(['status' => 'received_and_processing']);
    } else {
      //  Log::warning('Webhook received but no valid sender or message content.');
        return response()->json(['status' => 'ignored_invalid_payload'], 400);
    }
}

public function handleWhatsappWebhook(Request $request)
{
    try {
        $payload = $request->all();


        if (isset($payload['whatsapp_number']) && isset($payload['message']) && isset($payload['inquiry_type'])) {
            // Normalize data
            $whatsappNumber = $payload['whatsapp_number'];
            $message = $payload['message'];
            $inquiryType = $payload['inquiry_type'];
            $propertyType = $payload['property_type'] ?? null;
            $propertyType = \App\Rules\PropertyTypeRule::normalize(is_string($propertyType) ? $propertyType : null);
            $sourceChannel = $payload['source_channel'] ?? 'whatsapp';
            $extra = $payload['extra'] ?? null;
            $lang = $payload['lang'] ?? 'ar';

            // Extract detected entities
            $detectedEntities = $payload['detected_entities'] ?? [];
            $budget = $detectedEntities['budget'] ?? null;
            $currency = $detectedEntities['currency'] ?? null;
            $location = $detectedEntities['location'] ?? null;
            $bedrooms = $detectedEntities['bedrooms'] ?? null;
            $bathrooms = $detectedEntities['bathrooms'] ?? null;
            $minAreaSqm = $detectedEntities['min_area_sqm'] ?? null;
            $maxAreaSqm = $detectedEntities['max_area_sqm'] ?? null;
            $furnished = $detectedEntities['furnished'] ?? null;
            $urgency = $detectedEntities['urgency'] ?? null;

            // Extract normalized location data
            $locationNormalized = $detectedEntities['location_normalized'] ?? [];
            $countryCode = $locationNormalized['country_code'] ?? null;
            $regionCode = $locationNormalized['region_code'] ?? null;
            $regionName = $locationNormalized['region_name'] ?? null;
            $city = $locationNormalized['city'] ?? null;
            $district = $locationNormalized['district'] ?? null;
            $latitude = $locationNormalized['latitude'] ?? null;
            $longitude = $locationNormalized['longitude'] ?? null;
            $locationConfidence = $locationNormalized['location_confidence'] ?? null;

            // Debug logging
            \Log::info('WhatsApp Webhook Debug', [
                'location_normalized' => $locationNormalized,
                'city' => $city,
                'district' => $district,
                'country_code' => $countryCode,
                'region_name' => $regionName,
            ]);

            $resolvedTenant = ! empty($extra)
                ? $this->whatsAppWebhookService->resolveTenantFromPayload(['phone_number_id' => $extra], 'meta')
                : null;

			$ownerUserId = $resolvedTenant !== null
                ? (int) $resolvedTenant['user_id']
                : (!empty($extra) ? $this->resolveUserIdFromWhatsappPhoneId($extra) : null);
            $resolvedWaNumberId = $resolvedTenant['wa_number_id'] ?? null;
			$phoneVariants = $this->buildPhoneVariants($whatsappNumber);
			$customer = $this->findCustomerByPhoneVariants($phoneVariants, $ownerUserId);
			$userId = $ownerUserId ?? ($customer ? $customer->user_id : null);

			$ownerUser = $userId !== null ? User::find($userId) : null;
			$tenantOwnerId = $ownerUser && method_exists($ownerUser, 'tenantOwnerId') ? $ownerUser->tenantOwnerId() : null;
			if ($tenantOwnerId !== null) {
				try {
					$this->communicationService->recordInboundMessage(
						userId: (int) $tenantOwnerId,
						externalPartyIdentifier: $whatsappNumber,
						content: $message,
						channel: 'whatsapp',
						providerMessageId: $payload['message_id'] ?? null,
						meta: array_filter([
                            'source' => 'whatsapp_webhook',
                            'wa_number_id' => $resolvedWaNumberId !== null ? (int) $resolvedWaNumberId : null,
                        ], static fn ($value) => $value !== null)
					);
				} catch (\Throwable $e) {
					Log::warning('WhatsApp webhook: recordInboundMessage failed', ['message' => $e->getMessage()]);
				}
			}

            // Prepare data for saving
            $inquiryData = [
                'user_id'        => $userId,
                'customer_id'    => $customer ? $customer->id : null,
                'phone_number'   => $whatsappNumber,
                'message'        => $message,
                'inquiry_type'   => $inquiryType,
                'property_type'  => $propertyType,
                'budget'         => $budget,
                'location'       => $location,

                // New monetary/preference fields
                'currency'       => $currency,
                'bedrooms'       => $bedrooms,
                'bathrooms'      => $bathrooms,
                'min_area_sqm'   => $minAreaSqm,
                'max_area_sqm'   => $maxAreaSqm,
                'furnished'      => $furnished,
                'urgency'        => $urgency,

                // Normalized location fields
                'country_code'   => $countryCode,
                'region_code'    => $regionCode,
                'region_name'    => $regionName,
                'city'           => $city,
                'district'       => $district,
                'latitude'       => $latitude,
                'longitude'      => $longitude,
                'location_confidence' => $locationConfidence,

                // Meta fields
                'source_channel' => $sourceChannel,
                'lang'           => $lang,
                'detected_entities_json' => json_encode($detectedEntities),
            ];

            // Debug the data being saved
            \Log::info('Inquiry Data Being Saved', $inquiryData);

            // Ignore-list guard: skip creating inquiry and property request if customer is ignored
            if ($userId !== null && app(IgnoredCustomersService::class)->isIgnored(
                (int) $userId,
                (string) $whatsappNumber,
                $customer ? $customer->id : null
            )) {
                Log::info('ChatController: phone/customer is on ignore list — skipping inquiry creation', [
                    'phone'           => $whatsappNumber,
                    'customer_id'     => $customer ? $customer->id : null,
                    'tenant_user_id'  => $userId,
                ]);
                return response()->json(['status' => 'ignored', 'message' => 'Customer is on ignore list'], 200);
            }

            // Save to inquiry table with all new fields
            $inquiry = ApiCustomerInquiry::create($inquiryData);

            // Also create users_property_requests (dual-insert)
            $locationCombined = implode(', ', array_filter([
                $district,
                $city,
            ]));

            $propertyRequestData = [
                'user_id' => $userId,
                'customer_id' => $customer ? $customer->id : null,
                'phone' => $whatsappNumber,
                'full_name' => $customer ? $customer->name : 'WhatsApp Customer',
                'notes' => $message,
                'inquiry_type' => $inquiryType,
                'property_type' => $propertyType,
                'purpose' => $this->mapInquiryTypeToPurpose($inquiryType),
                'budget_from' => null,
                'budget_to' => $budget,
                'currency' => $currency,
                'bedrooms' => $bedrooms,
                'bathrooms' => $bathrooms,
                'furnished' => $furnished,
                'seriousness' => $this->mapUrgencyToSeriousness($urgency),
                'city' => $city,
                'district' => $district,
                'location' => $locationCombined ?: null,
                'region' => $regionName ?? 'الرياض',
                'source' => 'whatsapp',
                'contact_on_whatsapp' => true,
                'lang' => $lang,
                'detected_entities_json' => json_encode($detectedEntities),
                'latitude' => $latitude,
                'longitude' => $longitude,
                'location_confidence' => $locationConfidence,
                'country_code' => $countryCode,
                'region_code' => $regionCode,
            ];

            $propertyRequestData = app(PropertyRequestLocationNormalizer::class)->normalize($propertyRequestData, 'whatsapp');
            UserPropertyRequest::create($propertyRequestData);

            // Debug what was actually saved
            \Log::info('Inquiry Saved Successfully', [
                'id' => $inquiry->id,
                'city' => $inquiry->city,
                'district' => $inquiry->district,
                'country_code' => $inquiry->country_code,
                'region_name' => $inquiry->region_name,
            ]);

            return response()->json([
                'status' => 'saved',
                'message' => 'Inquiry saved successfully',
                'data' => $inquiry,
            ], 201);
        }


        $entry = $payload['entry'][0]['changes'][0]['value'] ?? null;
        if (!$entry) {
            return response()->json(['status' => 'ignored', 'message' => 'Invalid payload structure'], 400);
        }

        $displayPhone = $entry['metadata']['display_phone_number'] ?? null;
        $fromNumber = $entry['messages'][0]['from'] ?? null;
        $contactName = $entry['contacts'][0]['profile']['name'] ?? 'Unknown';

        if (!$displayPhone || !$fromNumber) {
            return response()->json(['status' => 'ignored', 'message' => 'Missing required fields'], 422);
        }

        $resolvedTenant = $this->whatsAppWebhookService->resolveTenantFromPayload([
            'metadata' => $entry['metadata'] ?? [],
            'phone_number_id' => $entry['metadata']['phone_number_id'] ?? null,
            'display_phone_number' => $displayPhone,
        ], 'meta');

        $whatsappUser = WhatsappUser::where('number', $displayPhone)->first();

        if (!$whatsappUser && $resolvedTenant === null) {
            return response()->json([
                'status' => 'ignored',
                'message' => 'Display phone number not found in whatsapp_users',
            ], 404);
        }

        $userId = $resolvedTenant !== null ? (int) $resolvedTenant['user_id'] : (int) $whatsappUser->user_id;
        $resolvedWaNumberId = $resolvedTenant['wa_number_id'] ?? null;

        $metaMessage = $entry['messages'][0] ?? null;
        $messageText = null;
        $providerMessageId = null;
        if ($metaMessage) {
            $messageText = $metaMessage['text']['body'] ?? $metaMessage['text'] ?? null;
            if (is_array($messageText)) {
                $messageText = $metaMessage['text']['body'] ?? '';
            }
            $providerMessageId = $metaMessage['id'] ?? null;
        }
        $tenantOwner = User::find($userId);
        $metaTenantOwnerId = $tenantOwner && method_exists($tenantOwner, 'tenantOwnerId') ? $tenantOwner->tenantOwnerId() : null;
        if ($metaTenantOwnerId !== null && $messageText !== null && $messageText !== '') {
            try {
                $this->communicationService->recordInboundMessage(
                    userId: (int) $metaTenantOwnerId,
                    externalPartyIdentifier: $fromNumber,
                    content: (string) $messageText,
                    channel: 'whatsapp',
                    providerMessageId: $providerMessageId,
                    meta: array_filter([
                        'source' => 'meta_webhook',
                        'display_phone' => $displayPhone,
                        'wa_number_id' => $resolvedWaNumberId !== null ? (int) $resolvedWaNumberId : null,
                    ], static fn ($value) => $value !== null)
                );
            } catch (\Throwable $e) {
                Log::warning('WhatsApp webhook (Meta): recordInboundMessage failed', ['message' => $e->getMessage()]);
            }
        }

        // Check if customer already exists
        $existing = ApiCustomer::where('user_id', $userId)
            ->where('phone_number', $fromNumber)
            ->first();

        if (!$existing) {
            $newCustomer = ApiCustomer::create([
                'user_id'      => $userId,
                'name'         => $contactName,
                'phone_number' => $fromNumber,
                'priority'     => 1,
                'customers_hub_stage_id' => 'new_lead',
                'password'     => bcrypt('12345678'),
            ]);

            return response()->json([
                'status'  => 'created',
                'message' => 'Customer created',
                'data'    => $newCustomer,
            ], 201);
        }

        return response()->json([
            'status'  => 'exists',
            'message' => 'Customer already exists',
        ], 200);

    } catch (\Throwable $e) {
        Log::error('WhatsApp Webhook Error: ' . $e->getMessage(), [
            'trace' => $e->getTraceAsString(),
        ]);

        return response()->json([
            'status' => 'error',
            'message' => 'Internal error',
        ], 500);
    }
}




    public function chat(ChatRequest $request)
    {
        $validated = $request->validated();
        $reply = $this->runChatFromPayload(
            $validated['message'],
            (int) $validated['user_id'],
            $validated['whatsapp_number']
        );
        return response()->json(['reply' => $reply ?? '']);
    }

    private function runChatFromPayload(string $message, int $userId, string $recipientWhatsappNumber): ?string
    {
        $userMessage = $message;

        // Load or init chat history
        $record = ChatHistory::firstOrCreate(
            ['user_id' => $userId],
            ['history' => []]
        );
        $history = $record->history;
        $history[] = ['role' => 'user', 'content' => $userMessage];

        // Build messages
        $messages = [];
        $messages[] = ['role' => 'system', 'content' => $this->systemInstructions];

        // Inject summary if exists
        if (!empty($history) && $history[0]['role'] === 'system_summary') {
            $messages[] = $history[0];
            $history = array_slice($history, 1);
        }

        // Append last 3 turns
        foreach (array_slice($history, -3) as $turn) {
            $messages[] = $turn;
        }

        // Define functions
        $functions = [
                [
                    'name' => 'search_properties',
                    'description' => 'دور على عقار حسب النوع (أرض او شقة او شقة في برج أو فيلا) او حسب المدينة او الحي, او حسب عدد الغرف',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'location' => ['type' => 'string'],
                            'min_bedrooms' => ['type' => 'integer'],
                            'max_price' => ['type' => 'number'],
                            'type' => ['type' => 'string'],
                            'purpose' => ['type' => 'string'],
                            'page' => ['type' => 'integer'],
                            'per_page' => ['type' => 'integer'],

                        ],
                        'required' => ['location'],
                    ],
                ],
            [
                'name' => 'get_faq_answer',
                'description' => 'إجابة عن الأسئلة الشائعة في إدارة العقارات',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'question' => ['type' => 'string'],
                    ],
                    'required' => ['question'],
                ],
            ],
        ];

        $reply = '';

        try {
            $model = env('OPENAI_CHAT_MODEL', 'gpt-4o-mini');
            Log::info('OpenAI chat request', ['model' => $model, 'message_count' => count($messages)]);

            $data = $this->callOpenAI([
                'model' => $model,
                'messages' => $messages,
                'functions' => $functions,
                'function_call' => 'auto',
            ]);

            $choice = $data['choices'][0]['message'];

            if (isset($choice['function_call'])) {
                $funcName = $choice['function_call']['name'];
                $args = json_decode($choice['function_call']['arguments'], true);

                $funcResponse = $funcName === 'search_properties'
                    ? $this->handleSearchProperties($args)
                    : $this->handleFaq($args);

                $messages[] = [
                    'role' => 'assistant',
                    'name' => $funcName,
                    'content' => json_encode($funcResponse),
                ];

                $final = $this->callOpenAI([
                    'model' => $model,
                    'messages' => $messages,
                ]);

                $reply = $final['choices'][0]['message']['content'];
                $history[] = ['role' => 'assistant', 'name' => $funcName, 'content' => json_encode($funcResponse)];
            } else {
                $reply = $choice['content'] ?? '';
            }
        } catch (\Throwable $e) {
            Log::error('OpenAI chat error in runChatFromPayload', [
                'message' => $e->getMessage(),
            ]);
            $reply = 'عذراً، حدث خطأ في الخدمة. يرجى المحاولة لاحقاً.';
        }

        $history[] = ['role' => 'assistant', 'content' => $reply];

        // Summarize if needed
        if (count($history) > $this->maxTurns) {
            Log::info('Chat history before summarize', ['history' => $history]);
            $summary = $this->summarizeHistory($history);
            $history = [['role' => 'system_summary', 'content' => $summary]];
        }

        // Save history
        $record->history = $history;
        $record->save();

        if (!empty($reply)) {
            $this->sendWhatsappMessage($recipientWhatsappNumber, $reply);
        }

        return $reply ?? '';
    }

    protected function handleSearchProperties(array $args): array
    {
        $userId   = 922;

        // Base query restricted to this user
        $query = Property::with([
            'category',
            'user',
            'contents',
            'proertyAmenities.amenity'
        ])->where('user_id', $userId);
        log::info($args);
        // Apply filters
            if (!empty($args['location'])) {
                $location = $this->normalizeArabic($args['location']);
                $tokens = explode(' ', preg_replace('/\s+/', ' ', trim($location)));

                $query->whereHas('contents', function ($q) use ($location, $tokens) {
                    $q->where(function ($qq) use ($location, $tokens) {
                        $qq->where('city_id', $this->mapCity($location))
                           ->orWhere('state_id', $this->mapState($location))
                           ->orWhere('title', 'like', "%{$location}%")
                           ->orWhere(function ($qqq) use ($tokens) {
                               foreach ($tokens as $token) {
                                   $qqq->where('address', 'like', "%{$token}%");
                               }
                           });
                    });
                });
            }

            if (!empty($args['min_bedrooms'])) {
                $query->where('beds', '>=', $args['min_bedrooms']);
            }

            if (!empty($args['max_price'])) {
                $query->where('price', '<=', $args['max_price']);
            }


            if (!empty($args['purpose'])) {
                $query->where('purpose', $args['purpose']);
            }

                if (!empty($args['type'])) {
                    log::info($args['type']);
                    log::info($this->mapCategory($args['type']));
                    $query->whereHas('contents', function ($q) use ($args) {
                        $q->where('category_id', $this->mapCategory($args['type']));
                    });
                }

            // Pagination
            $perPage = $args['page_size'] ?? 10;
            $page = $args['page'] ?? 1;

            $paginated = $query->paginate($perPage, ['*'], 'page', $page);

            // Format results
            $formatted = $paginated->getCollection()->map(fn($p) => [
                'id'               => $p->id,
                'title'            => optional($p->contents->first())->title ?? 'No Title',
                'address'          => optional($p->contents->first())->address ?? 'No Address',
                'slug'             => optional($p->contents->first())->slug,
                'price'            => $p->price,
                'type'             => $p->type,
                'beds'             => $p->beds,
                'bath'             => $p->bath,
                'area'             => $p->area,
                'transaction_type' => $p->purpose,
                'features'         => $p->features,
                'status'           => $p->status,
                'featured_image'   => asset($p->featured_image),
                'featured'         => (bool) $p->featured,
                'created_at'       => $p->created_at->toISOString(),
                'updated_at'       => $p->updated_at->toISOString(),
            ]);


        log::info($formatted);
        return [
            'properties' => $formatted,
            'pagination' => [
                'total'        => $paginated->total(),
                'per_page'     => $paginated->perPage(),
                'current_page' => $paginated->currentPage(),
                'last_page'    => $paginated->lastPage(),
                'from'         => $paginated->firstItem(),
                'to'           => $paginated->lastItem(),
            ],
        ];
    }

    private function normalizeArabic($text)
    {
        $replacements = [
            'أ' => 'ا',
            'إ' => 'ا',
            'آ' => 'ا',
            'ى' => 'ي',
            'ئ' => 'ي',
            'ؤ' => 'و',
            'ة' => 'ه',
            'ٱ' => 'ا',
            'گ' => 'ك',
            'چ' => 'ج',
            'ژ' => 'ز',
            'ڤ' => 'ف',
            'پ' => 'ب',
            'بن' => 'ابن',
        ];
        return strtr($text, $replacements);
    }


private function mapCity(string $name): int
{
    return UserCity::where('name_ar', $name)->value('id') ?? 0;
}

private function mapState(string $name): int
{
    return UserDistrict::where('name_ar', $name)->value('id') ?? 0;
}

private function mapCategory(string $name): int
{
    return ApiUserCategory::where('name', $name)->value('id') ?? 0;
}

	/**
	 * Resolve owner user_id from whatsapp_users.phone_id
	 */
	private function resolveUserIdFromWhatsappPhoneId($phoneId): ?int
	{
		$owner = WhatsappUser::where('phone_id', $phoneId)->first();
		return $owner ? $owner->user_id : null;
	}

	/**
	 * Build common phone variants to match (raw, stripped '+', and with '+')
	 */
	private function buildPhoneVariants(string $phone): array
	{
		$normalized = ltrim($phone, '+');
		return [$phone, $normalized, '+' . $normalized];
	}

	/**
	 * Find ApiCustomer by phone variants and optional owner user scope
	 */
	private function findCustomerByPhoneVariants(array $variants, ?int $userId): ?ApiCustomer
	{
		$query = ApiCustomer::whereIn('phone_number', $variants);
		if (!empty($userId)) {
			$query->where('user_id', $userId);
		}
		return $query->first();
	}

	private function mapUrgencyToSeriousness(?string $urgency): ?string
	{
		return match ($urgency) {
			'urgent' => 'مستعد فورًا',
			'soon' => 'خلال شهر',
			'flexible' => 'لاحقًا / استكشاف فقط',
			default => null,
		};
	}

	private function mapInquiryTypeToPurpose(?string $inquiryType): ?string
	{
		return match ($inquiryType) {
			'rent' => 'rent',
			'buy', 'invest' => 'sale',
			default => null,
		};
	}

    protected function handleFaq(array $args): array
    {
        $question = $args['question'];
        $embedRes = $this->openai->embeddings()->create([
            'model' => env('OPENAI_EMBEDDING_MODEL','text-embedding-3-small'),
            'input' => $question,
        ]);
        $qVec = $embedRes['data'][0]['embedding'];

        $best = Embedding::all()->reduce(function($carry, Embedding $emb) use ($qVec) {
            $score = $this->cosineSimilarity($qVec, $emb->embedding);
            if ($carry === null || $score > $carry[1]) {
                return [$emb->text, $score];
            }
            return $carry;
        }, null);

        $parts = preg_split('/\nج[:：]/u', $best[0], 2);
        $answer = trim($parts[1] ?? $best[0]);
        return ['answer' => $answer];
    }

    private function callOpenAI(array $payload): array
    {
        $apiKey = env('OPENAI_API_KEY');
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
            'Content-Type'  => 'application/json',
        ])->timeout(30)->post('https://api.openai.com/v1/chat/completions', $payload);

        $body = $response->body();

        if (!$response->successful()) {
            Log::error('OpenAI HTTP error', ['status' => $response->status(), 'body' => $body]);
            throw new \RuntimeException('OpenAI API error ' . $response->status() . ': ' . $body);
        }

        $data = $response->json();

        if (!is_array($data) || !isset($data['choices'])) {
            Log::error('OpenAI unexpected response', ['body' => $body]);
            throw new \RuntimeException('OpenAI unexpected response: ' . $body);
        }

        return $data;
    }

    private function summarizeHistory(array $history): string
    {
        $text = Collection::make($history)
            ->map(fn($m) => ucfirst($m['role']) . ": " . $m['content'])
            ->join("\n");

        try {
            $data = $this->callOpenAI([
                'model' => env('OPENAI_CHAT_MODEL', 'gpt-4o-mini'),
                'messages' => [
                    ['role' => 'system', 'content' => 'سّو ملخص بسيط للمحادثة بالتركيز على معايير المستخدم.'],
                    ['role' => 'user',   'content' => $text],
                ],
                'max_tokens' => 200,
            ]);

            return $data['choices'][0]['message']['content'];
        } catch (\Throwable $e) {
            Log::error('OpenAI summarize error', ['message' => $e->getMessage()]);
            return 'ملخص المحادثة غير متاح حالياً.';
        }
    }



    private function cosineSimilarity(array $a, array $b): float
    {
        $dot = $na = $nb = 0;
        foreach ($a as $i => $v) {
            $dot += $v * $b[$i];
            $na  += $v * $v;
            $nb  += $b[$i] * $b[$i];
        }
        return $dot / (sqrt($na) * sqrt($nb));
    }
}
