<?php

namespace Modules\WhatsappAI\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;
use OpenAI;
use Modules\WhatsappAI\Entities\WhatsappConversation;
use App\Models\Api\ApiCustomerInquiry;
use App\Models\Api\UserPropertyRequest;
use App\Models\ApiCustomer;
use App\Domain\CustomersHub\Services\IgnoredCustomersService;

class ProcessConversation implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $conversationId;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(int $conversationId)
    {
        $this->conversationId = $conversationId;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            $conversation = WhatsappConversation::with(['messages', 'whatsappUser', 'customer'])
                ->find($this->conversationId);

            if (!$conversation) {
                Log::warning('Conversation not found', ['id' => $this->conversationId]);
                return;
            }

            // Skip if already processed
            if ($conversation->status === 'processed') {
                // Log::info('Conversation already processed', ['id' => $this->conversationId]);
                return;
            }

            // Check if still active (new messages came in)
            if ($conversation->isActive()) {
                // Log::info('Conversation still active, skipping', ['id' => $this->conversationId]);
                return;
            }

            // Build transcript only from messages that have not been processed yet.
            // On the first run (cursor is null) all messages are included.
            // On subsequent runs only messages after the cursor are included so we
            // analyse new content only and avoid creating duplicate records.
            $messageQuery = $conversation->messages()
                ->whereIn('message_type', ['text', 'image', 'video', 'document', 'location']);

            if ($conversation->last_processed_message_id) {
                $messageQuery->where('id', '>', $conversation->last_processed_message_id);
            }

            $newMessages = $messageQuery->orderBy('id')->get();

            if ($newMessages->isEmpty()) {
                $conversation->update(['status' => 'archived']);
                return;
            }

            // Track the highest message ID so we can advance the cursor after processing.
            $lastMessageId = $newMessages->max('id');

            $transcript = $newMessages
                ->pluck('content')
                ->filter()
                ->implode("\n");

            if (empty($transcript)) {
                $conversation->update(['status' => 'archived']);
                return;
            }

            // Call OpenAI for analysis
            $extraction = $this->analyzeWithAI($transcript);

            // Persist the AI-extracted fields on the conversation. Status and cursor
            // are intentionally NOT set here — they are only advanced after the
            // inquiry is successfully created below. This ensures that if inquiry
            // creation throws and the job retries, the status is still 'collecting'
            // (so the retry guard at the top of handle() does not short-circuit)
            // and the cursor has not moved (so the same new messages are re-analysed).
            $conversation->update([
                'is_real_estate_inquiry' => $extraction['is_real_estate_inquiry'] ?? false,
                'inquiry_type' => $extraction['inquiry_type'] ?? null,
                'property_type' => $extraction['property_type'] ?? null,
                'budget_min' => $extraction['budget_min'] ?? null,
                'budget_max' => $extraction['budget_max'] ?? null,
                'currency' => $extraction['currency'] ?? null,
                'bedrooms' => $extraction['bedrooms'] ?? null,
                'bathrooms' => $extraction['bathrooms'] ?? null,
                'city' => $extraction['city'] ?? null,
                'district' => $extraction['district'] ?? null,
                'urgency' => $extraction['urgency'] ?? null,
                'furnished' => $extraction['furnished'] ?? null,
                'ai_summary' => $extraction['summary'] ?? null,
                'extracted_data' => $extraction,
            ]);

            // Create/update inquiry if it's a real estate inquiry
            if ($extraction['is_real_estate_inquiry'] ?? false) {
                // Check ignore list before creating any records for this customer
                $ignoredService = app(IgnoredCustomersService::class);
                if ($ignoredService->isIgnored(
                    $conversation->user_id,
                    $conversation->customer_phone,
                    $conversation->customer_id ?: null
                )) {
                    Log::info('ProcessConversation: phone/customer is on ignore list — skipping inquiry and property request creation', [
                        'conversation_id' => $this->conversationId,
                        'phone'           => $conversation->customer_phone,
                        'customer_id'     => $conversation->customer_id,
                        'tenant_user_id'  => $conversation->user_id,
                    ]);
                    $conversation->update(['status' => 'archived']);
                    return;
                }

                $inquiry = $this->createInquiry($conversation, $extraction, $transcript);

                // Inquiry created successfully — now it is safe to mark the
                // conversation as processed and advance the cursor.
                $conversation->update([
                    'status' => 'processed',
                    'processed_at' => now(),
                    'last_processed_message_id' => $lastMessageId,
                    'inquiry_id' => $inquiry->id,
                ]);
            } else {
                // Not a real-estate inquiry — nothing to create, mark processed.
                $conversation->update([
                    'status' => 'processed',
                    'processed_at' => now(),
                    'last_processed_message_id' => $lastMessageId,
                ]);
            }

        } catch (\Throwable $e) {
            Log::error('ProcessConversation Job Error', [
                'conversation_id' => $this->conversationId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e; // Re-throw to trigger job retry
        }
    }

    /**
     * Analyze conversation with OpenAI.
     * Protected so tests can override via anonymous subclass.
     */
    protected function analyzeWithAI(string $transcript): array
    {
        $apiKey = config('openai.api_key');
        
        // Validate API key
        if (empty($apiKey)) {
            Log::error('OpenAI API key is not configured', [
                'conversation_id' => $this->conversationId,
            ]);
            return ['is_real_estate_inquiry' => false];
        }

        // Validate API key format (should start with sk-)
        if (!str_starts_with($apiKey, 'sk-')) {
            Log::error('OpenAI API key format appears invalid (should start with sk-)', [
                'conversation_id' => $this->conversationId,
                'key_prefix' => substr($apiKey, 0, 5) . '...',
            ]);
            return ['is_real_estate_inquiry' => false];
        }

        $model = config('whatsappai.model', 'gpt-4o-mini');
        $prompt = $this->buildPrompt($transcript);

        try {
            $client = OpenAI::client($apiKey);

            $response = $client->chat()->create([
                'model' => $model,
                'temperature' => 0.1,
                'messages' => [
                    ['role' => 'user', 'content' => $prompt]
                ],
                'response_format' => ['type' => 'json_object'],
            ]);

            // Validate response structure
            if (!isset($response->choices) || empty($response->choices)) {
                Log::error('OpenAI API returned empty choices', [
                    'conversation_id' => $this->conversationId,
                    'response' => json_encode($response),
                ]);
                return ['is_real_estate_inquiry' => false];
            }

            $content = $response->choices[0]->message->content ?? null;
            
            if (empty($content)) {
                Log::error('OpenAI API returned empty content', [
                    'conversation_id' => $this->conversationId,
                ]);
                return ['is_real_estate_inquiry' => false];
            }

            $data = json_decode($content, true);

            if (!$data || !is_array($data)) {
                Log::error('Failed to parse AI response', [
                    'content' => $content,
                    'conversation_id' => $this->conversationId,
                ]);
                return ['is_real_estate_inquiry' => false];
            }

            return $data;

        } catch (\TypeError $e) {
            $errorDetails = [
                'error' => $e->getMessage(),
                'error_class' => 'TypeError',
                'conversation_id' => $this->conversationId,
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ];

            $previous = $e->getPrevious();
            if ($previous) {
                $errorDetails['previous_error'] = $previous->getMessage();
                $errorDetails['previous_class'] = get_class($previous);
            }

            Log::error('OpenAI API TypeError (likely invalid API response)', $errorDetails);

            return ['is_real_estate_inquiry' => false];
        } catch (\OpenAI\Exceptions\ErrorException $e) {
            $errorDetails = [
                'error' => $e->getMessage(),
                'error_class' => 'OpenAI\Exceptions\ErrorException',
                'conversation_id' => $this->conversationId,
            ];

            if (method_exists($e, 'getResponse')) {
                try {
                    $response = $e->getResponse();
                    if ($response) {
                        $errorDetails['api_response'] = is_string($response) ? $response : json_encode($response);
                    }
                } catch (\Exception $ex) {
                    // Ignore
                }
            }

            Log::error('OpenAI API ErrorException', $errorDetails);

            return ['is_real_estate_inquiry' => false];
        } catch (\Throwable $e) {
            $errorDetails = [
                'error' => $e->getMessage(),
                'error_class' => get_class($e),
                'conversation_id' => $this->conversationId,
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ];

            if (method_exists($e, 'getResponse')) {
                try {
                    $response = $e->getResponse();
                    if ($response) {
                        $errorDetails['api_response'] = is_string($response) ? $response : json_encode($response);
                    }
                } catch (\Exception $ex) {
                    // Ignore if we can't get response
                }
            }

            $previous = $e->getPrevious();
            if ($previous) {
                $errorDetails['previous_error'] = $previous->getMessage();
                $errorDetails['previous_class'] = get_class($previous);
            }

            Log::error('OpenAI API Error', $errorDetails);

            return ['is_real_estate_inquiry' => false];
        }
    }

    /**
     * Build AI prompt in Arabic
     */
    private function buildPrompt(string $transcript): string
    {
        return <<<PROMPT
أنت محلل متخصص في استخراج بيانات العملاء المهتمين بالعقارات من محادثات الواتساب.

المحادثة:
{$transcript}

قم بتحليل المحادثة واستخراج المعلومات التالية بصيغة JSON:

{
  "is_real_estate_inquiry": bool,
  "inquiry_type": "buy|rent|invest|null",
  "property_type": "apartment|villa|land|townhouse|penthouse|studio|duplex|building|commercial|office|shop|warehouse|null",
  "budget_min": number|null,
  "budget_max": number|null,
  "currency": "SAR|USD|AED|null",
  "bedrooms": number|null,
  "bathrooms": number|null,
  "area_min": number|null,
  "area_max": number|null,
  "city": "string|null",
  "district": "string|null",
  "latitude": number|null,
  "longitude": number|null,
  "urgency": "urgent|soon|flexible|null",
  "furnished": bool|null,
  "summary": "ملخص قصير للمحادثة بالعربية (2-3 جمل)"
}

ملاحظات:
- إذا كانت المحادثة غير متعلقة بالعقارات، ضع is_real_estate_inquiry = false
- استخرج فقط المعلومات الواضحة، لا تفترض شيء غير موجود
- budget_min و budget_max بالأرقام فقط (بدون فواصل)
- إذا ذكر العميل ميزانية واحدة، ضعها في budget_max
- area_min و area_max بالمتر المربع كأرقام فقط (مثال: "200 متر" → area_max: 200)
- إذا ذكر العميل مساحة واحدة، ضعها في area_max
- city و district بالعربية كما وردت في المحادثة
- إذا وجدت رسالة موقع بالشكل [Location: lat, lng]، استخرج latitude و longitude منها
- urgency: "urgent" إذا كان يريد بسرعة، "soon" خلال شهر، "flexible" ليس مستعجل
- furnished: true إذا طلب مفروش، false إذا طلب غير مفروش، null إذا لم يذكر
- inquiry_type: "buy" للشراء، "rent" للإيجار، "invest" للاستثمار
- اختر نوع العقار الرئيسي إذا ذُكر أكثر من نوع

أجب فقط بصيغة JSON بدون أي نص إضافي.
PROMPT;
    }

    /**
     * Create customer inquiry from extracted data.
     * Also upserts the property request: updates the earliest active request for
     * this customer with any missing structured fields and appends the new AI
     * summary to the notes. Creates a new property request only when none exists.
     */
    private function createInquiry(WhatsappConversation $conversation, array $extraction, string $transcript): ApiCustomerInquiry
    {
        $customer = $this->ensureCustomer($conversation);

        $location = implode(', ', array_filter([
            $extraction['district'] ?? null,
            $extraction['city'] ?? null,
        ]));

        // Create api_customer_inquiry (always — one per processing session for history)
        $inquiryData = [
            'user_id' => $conversation->user_id,
            'customer_id' => $customer->id,
            'phone_number' => $conversation->customer_phone,
            'message' => $extraction['summary'] ?? $transcript,
            'inquiry_type' => $extraction['inquiry_type'] ?? null,
            'property_type' => $extraction['property_type'] ?? null,
            'budget' => $extraction['budget_max'] ?? $extraction['budget_min'] ?? null,
            'location' => $location ?: null,
            'currency' => $extraction['currency'] ?? 'SAR',
            'bedrooms' => $extraction['bedrooms'] ?? null,
            'bathrooms' => $extraction['bathrooms'] ?? null,
            'min_area_sqm' => $extraction['area_min'] ?? null,
            'max_area_sqm' => $extraction['area_max'] ?? null,
            'furnished' => $extraction['furnished'] ?? null,
            'urgency' => $extraction['urgency'] ?? null,
            'city' => $extraction['city'] ?? null,
            'district' => $extraction['district'] ?? null,
            'source_channel' => 'whatsapp_ai',
            'lang' => 'ar',
            'detected_entities_json' => json_encode($extraction),
        ];

        $inquiry = ApiCustomerInquiry::create($inquiryData);

        // Resolve lat/lng: from AI extraction (location message) or extraction data
        $latitude = $extraction['latitude'] ?? null;
        $longitude = $extraction['longitude'] ?? null;
        if ($latitude === null) {
            $latitude = $this->extractLatLngFromMessages($conversation, 'latitude');
        }
        if ($longitude === null) {
            $longitude = $this->extractLatLngFromMessages($conversation, 'longitude');
        }

        // Resolve region dynamically from city name
        $regionName = $this->resolveRegionFromCity($conversation->user_id, $extraction['city'] ?? null);

        $this->upsertPropertyRequest($customer, $extraction, $conversation, $location, $latitude, $longitude, $regionName);

        return $inquiry;
    }

    /**
     * Find the customer's earliest active property request and fill in any missing
     * structured fields. The notes field is always appended with the new session
     * summary so it builds a running history. If no active request exists, a new
     * one is created (first-time behaviour).
     */
    private function upsertPropertyRequest(
        ApiCustomer $customer,
        array $extraction,
        WhatsappConversation $conversation,
        string $location,
        ?float $latitude,
        ?float $longitude,
        ?string $regionName
    ): void {
        // Priority 1: match by customer_id (exact, avoids cross-customer collisions).
        $existing = UserPropertyRequest::where('user_id', $conversation->user_id)
            ->where('customer_id', $customer->id)
            ->where('is_active', 1)
            ->orderBy('created_at')
            ->first();

        // Priority 2: phone fallback — only when the matched row is unclaimed
        // (customer_id IS NULL) or already belongs to the same customer.
        // This prevents accidentally merging data into another customer's request
        // when two distinct customer records share the same phone number.
        if (!$existing) {
            $existing = UserPropertyRequest::where('user_id', $conversation->user_id)
                ->where('phone', $conversation->customer_phone)
                ->where(function ($q) use ($customer) {
                    $q->whereNull('customer_id')
                      ->orWhere('customer_id', $customer->id);
                })
                ->where('is_active', 1)
                ->orderBy('created_at')
                ->first();
        }

        $newSummary = $extraction['summary'] ?? null;

        if ($existing) {
            // Structured fields: only fill when currently NULL
            $structuredFields = [
                'property_type'  => $extraction['property_type'] ?? null,
                'purpose'        => $this->mapInquiryTypeToPurpose($extraction['inquiry_type'] ?? null),
                'budget_from'    => $extraction['budget_min'] ?? null,
                'budget_to'      => $extraction['budget_max'] ?? null,
                'currency'       => $extraction['currency'] ?? null,
                'bedrooms'       => $extraction['bedrooms'] ?? null,
                'bathrooms'      => $extraction['bathrooms'] ?? null,
                'furnished'      => $extraction['furnished'] ?? null,
                'area_from'      => $extraction['area_min'] ?? null,
                'area_to'        => $extraction['area_max'] ?? null,
                'seriousness'    => $this->mapUrgencyToSeriousness($extraction['urgency'] ?? null),
                'city'           => $extraction['city'] ?? null,
                'district'       => $extraction['district'] ?? null,
                'location'       => $location ?: null,
                'region'         => $regionName,
                'latitude'       => $latitude,
                'longitude'      => $longitude,
                'inquiry_type'   => $extraction['inquiry_type'] ?? null,
            ];

            $updates = [];
            foreach ($structuredFields as $field => $value) {
                if ($value !== null && $existing->$field === null) {
                    $updates[$field] = $value;
                }
            }

            // Notes: always append new summary (separated by divider) so we get a
            // running history of every session's AI summary on the same request.
            if ($newSummary !== null) {
                $updates['notes'] = $existing->notes
                    ? $existing->notes . "\n---\n" . $newSummary
                    : $newSummary;
            }

            if (!empty($updates)) {
                $existing->update($updates);
            }

            return;
        }

        // No existing active request — create a fresh one (first-time behaviour)
        UserPropertyRequest::create([
            'user_id'             => $conversation->user_id,
            'customer_id'         => $customer->id,
            'phone'               => $conversation->customer_phone,
            'full_name'           => $conversation->customer_name ?? 'WhatsApp Customer',
            'notes'               => $newSummary,
            'inquiry_type'        => $extraction['inquiry_type'] ?? null,
            'property_type'       => $extraction['property_type'] ?? null,
            'purpose'             => $this->mapInquiryTypeToPurpose($extraction['inquiry_type'] ?? null),
            'budget_from'         => $extraction['budget_min'] ?? null,
            'budget_to'           => $extraction['budget_max'] ?? null,
            'currency'            => $extraction['currency'] ?? 'SAR',
            'bedrooms'            => $extraction['bedrooms'] ?? null,
            'bathrooms'           => $extraction['bathrooms'] ?? null,
            'furnished'           => $extraction['furnished'] ?? null,
            'area_from'           => $extraction['area_min'] ?? null,
            'area_to'             => $extraction['area_max'] ?? null,
            'seriousness'         => $this->mapUrgencyToSeriousness($extraction['urgency'] ?? null),
            'city'                => $extraction['city'] ?? null,
            'district'            => $extraction['district'] ?? null,
            'location'            => $location ?: null,
            'region'              => $regionName,
            'latitude'            => $latitude,
            'longitude'           => $longitude,
            'source'              => 'whatsapp',
            'contact_on_whatsapp' => true,
            'lang'                => 'ar',
            'detected_entities_json' => json_encode($extraction),
        ]);
    }

    /**
     * Ensure customer exists in database
     */
    private function ensureCustomer(WhatsappConversation $conversation): ApiCustomer
    {
        // Try to find existing customer
        if ($conversation->customer_id) {
            $customer = $conversation->customer;
            if ($customer) {
                return $customer;
            }
        }

        // Try to find by phone
        $customer = ApiCustomer::where('user_id', $conversation->user_id)
            ->where('phone_number', $conversation->customer_phone)
            ->first();

        if ($customer) {
            $conversation->update(['customer_id' => $customer->id]);
            return $customer;
        }

        // Create new customer
        $customer = ApiCustomer::create([
            'user_id' => $conversation->user_id,
            'name' => $conversation->customer_name ?? 'WhatsApp Customer',
            'phone_number' => $conversation->customer_phone,
            'priority_id' => 1,
            'password' => bcrypt('12345678'),
        ]);

        $conversation->update(['customer_id' => $customer->id]);

        return $customer;
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

    /**
     * Parse lat/lng from location messages stored as "[Location: lat, lng]".
     */
    private function extractLatLngFromMessages(WhatsappConversation $conversation, string $field): ?float
    {
        $locationMessage = $conversation->messages()
            ->where('message_type', 'location')
            ->first(['content']);

        if (!$locationMessage || !$locationMessage->content) {
            return null;
        }

        // Content format: "[Location: 24.7136, 46.6753]"
        if (preg_match('/\[Location:\s*([-\d.]+),\s*([-\d.]+)\]/', $locationMessage->content, $matches)) {
            return $field === 'latitude' ? (float) $matches[1] : (float) $matches[2];
        }

        return null;
    }

    /**
     * Try to find the region name for a given city in user_cities table.
     * Falls back to null when the city cannot be resolved.
     */
    private function resolveRegionFromCity(?int $userId, ?string $cityName): ?string
    {
        if (!$cityName) {
            return null;
        }

        try {
            $row = \Illuminate\Support\Facades\DB::table('user_cities')
                ->where('user_id', $userId)
                ->where(function ($q) use ($cityName) {
                    $q->where('name_ar', $cityName)
                      ->orWhere('name_en', $cityName);
                })
                ->first(['region_name_ar', 'region_name']);

            if ($row) {
                return $row->region_name_ar ?? $row->region_name ?? null;
            }
        } catch (\Throwable $e) {
            // Non-critical: log and continue without region
            \Illuminate\Support\Facades\Log::warning('ProcessConversation: could not resolve region from city', [
                'city' => $cityName,
                'error' => $e->getMessage(),
            ]);
        }

        return null;
    }
}
