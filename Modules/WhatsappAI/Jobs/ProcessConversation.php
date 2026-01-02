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
use App\Models\ApiCustomer;

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
                Log::info('Conversation already processed', ['id' => $this->conversationId]);
                return;
            }

            // Check if still active (new messages came in)
            if ($conversation->isActive()) {
                Log::info('Conversation still active, skipping', ['id' => $this->conversationId]);
                return;
            }

            // Build transcript
            $transcript = $conversation->messages()
                ->where('message_type', 'text')
                ->pluck('content')
                ->filter()
                ->implode("\n");

            if (empty($transcript)) {
                Log::info('No text content in conversation', ['id' => $this->conversationId]);
                $conversation->update(['status' => 'archived']);
                return;
            }

            Log::info('Processing conversation with AI', [
                'id' => $this->conversationId,
                'message_count' => $conversation->message_count,
                'transcript_length' => strlen($transcript),
            ]);

            // Call OpenAI for analysis
            $extraction = $this->analyzeWithAI($transcript);

            // Update conversation with extracted data
            $conversation->update([
                'status' => 'processed',
                'processed_at' => now(),
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

            // Create inquiry if it's a real estate inquiry
            if ($extraction['is_real_estate_inquiry'] ?? false) {
                $inquiry = $this->createInquiry($conversation, $extraction, $transcript);
                $conversation->update(['inquiry_id' => $inquiry->id]);
                
                Log::info('Inquiry created from conversation', [
                    'conversation_id' => $conversation->id,
                    'inquiry_id' => $inquiry->id,
                ]);
            }

            Log::info('Conversation processed successfully', ['id' => $this->conversationId]);

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
     * Analyze conversation with OpenAI
     */
    private function analyzeWithAI(string $transcript): array
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

        Log::info('Calling OpenAI API', [
            'conversation_id' => $this->conversationId,
            'model' => $model,
            'prompt_length' => strlen($prompt),
        ]);

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
            // Handle TypeError from OpenAI client (usually means API returned error response)
            $errorDetails = [
                'error' => $e->getMessage(),
                'error_class' => 'TypeError',
                'conversation_id' => $this->conversationId,
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ];

            // Try to extract more context from the exception
            $previous = $e->getPrevious();
            if ($previous) {
                $errorDetails['previous_error'] = $previous->getMessage();
                $errorDetails['previous_class'] = get_class($previous);
            }

            Log::error('OpenAI API TypeError (likely invalid API response)', $errorDetails);

            return ['is_real_estate_inquiry' => false];
        } catch (\OpenAI\Exceptions\ErrorException $e) {
            // Handle OpenAI-specific exceptions
            $errorDetails = [
                'error' => $e->getMessage(),
                'error_class' => 'OpenAI\Exceptions\ErrorException',
                'conversation_id' => $this->conversationId,
            ];

            // Try to get response body if available
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
            // Log detailed error information for any other exceptions
            $errorDetails = [
                'error' => $e->getMessage(),
                'error_class' => get_class($e),
                'conversation_id' => $this->conversationId,
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ];

            // If it's an OpenAI exception, try to extract more details
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

            // Try to get previous exception
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
  "city": "string|null",
  "district": "string|null",
  "urgency": "urgent|soon|flexible|null",
  "furnished": bool|null,
  "summary": "ملخص قصير للمحادثة بالعربية (2-3 جمل)"
}

ملاحظات:
- إذا كانت المحادثة غير متعلقة بالعقارات، ضع is_real_estate_inquiry = false
- استخرج فقط المعلومات الواضحة، لا تفترض شيء غير موجود
- budget_min و budget_max بالأرقام فقط (بدون فواصل)
- إذا ذكر العميل ميزانية واحدة، ضعها في budget_max
- city و district بالعربية كما وردت في المحادثة
- urgency: "urgent" إذا كان يريد بسرعة، "soon" خلال شهر، "flexible" ليس مستعجل
- furnished: true إذا طلب مفروش، false إذا طلب غير مفروش، null إذا لم يذكر

أجب فقط بصيغة JSON بدون أي نص إضافي.
PROMPT;
    }

    /**
     * Create customer inquiry from extracted data
     */
    private function createInquiry(WhatsappConversation $conversation, array $extraction, string $transcript): ApiCustomerInquiry
    {
        // Ensure customer exists
        $customer = $this->ensureCustomer($conversation);

        // Prepare location string
        $location = implode(', ', array_filter([
            $extraction['district'] ?? null,
            $extraction['city'] ?? null,
        ]));

        // Prepare inquiry data
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
            'furnished' => $extraction['furnished'] ?? null,
            'urgency' => $extraction['urgency'] ?? null,
            'city' => $extraction['city'] ?? null,
            'district' => $extraction['district'] ?? null,
            'source_channel' => 'whatsapp_ai',
            'lang' => 'ar',
            'detected_entities_json' => json_encode($extraction),
        ];

        return ApiCustomerInquiry::create($inquiryData);
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
            'password' => bcrypt(\Illuminate\Support\Str::random(16)),
        ]);

        $conversation->update(['customer_id' => $customer->id]);

        return $customer;
    }
}

