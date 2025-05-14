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

use OpenAI as OpenAIClient;


class ChatController extends Controller
{

    protected int $maxTurns = 10;
    protected string $systemInstructions;
    protected string $evolutionApiUrl;
    protected string $evolutionApiKey;
    protected string $evolutionApiInstance;

    public function __construct()
    {
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
            $response = Http::withHeaders([
                'apikey' => $this->evolutionApiKey,
                'Content-Type' => 'application/json',
            ])->post($endpoint, [
                'number' => $recipientNumber, // Ensure this is the full WhatsApp number (e.g., country code + number)
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

        // You need to map the $senderNumber to a $userId in your system
        // This is a placeholder; implement your own user lookup logic
        // Prepare a request object or parameters to call your existing chat logic
        $internalRequest = new Request([
            'message' => $messageContent,
            'user_id' => 922, // Pass the identified or created user ID
            'whatsapp_number' => $senderNumber // Pass the sender's WhatsApp number for the reply
        ]);

        // Call your chat processing logic
        // Make sure the 'chat' method can handle being called this way
        // and that it knows to use $recipientWhatsappNumber for the reply
        $this->chat($internalRequest);

        return response()->json(['status' => 'received_and_processing']);
    } else {
      //  Log::warning('Webhook received but no valid sender or message content.');
        return response()->json(['status' => 'ignored_invalid_payload'], 400);
    }
}

    public function chat(Request $request)
    {
        $userMessage = $request->input('message');
        $userId = $request->input('user_id'); // Or derive from recipientWhatsappNumber if this is from a webhook
        $recipientWhatsappNumber = $request->input('whatsapp_number'); // This needs to be provided


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

        // Call API
        $response = $this->openai->chat()->create([
            'model' => env('OPENAI_CHAT_MODEL', 'gpt-4.1-nano'),
            'messages' => $messages,
            'functions' => $functions,
            'function_call' => 'auto',
        ]);
        log::info(json_encode($response));
        $choice = $response['choices'][0]['message'];
        $reply = '';

        // Handle function call
        if (isset($choice['function_call'])) {
            $funcName = $choice['function_call']['name'];
            $args = json_decode($choice['function_call']['arguments'], true);

            if ($funcName === 'search_properties') {
                $funcResponse = $this->handleSearchProperties($args);
            } else {
                $funcResponse = $this->handleFaq($args);
            }

            // Inject function response
            $messages[] = [
                'role' => 'assistant',
                'name' => $funcName,
                'content' => json_encode($funcResponse),
            ];

            // Final LLM call to generate natural reply
            $final = $this->openai->chat()->create([
                'model' => env('OPENAI_CHAT_MODEL', 'gpt-4.1-nano'),
                'messages' => $messages,
            ]);

            $reply = $final['choices'][0]['message']['content'];
            log::info('reply'.$reply);
            $history[] = ['role' => 'assistant', 'name' => $funcName, 'content' => json_encode($funcResponse)];
        } else {
            // Direct reply
            $reply = $choice['content'] ?? '';
        }

        $history[] = ['role' => 'assistant', 'content' => $reply];

        // Summarize if needed
        if (count($history) > $this->maxTurns) {
            log::info($history);
            $summary = $this->summarizeHistory($history);
            $history = [['role' => 'system_summary', 'content' => $summary]];
        }

        // Save history
        $record->history = $history;
        $record->save();

        if (!empty($reply)) {
            $this->sendWhatsappMessage($recipientWhatsappNumber, $reply);
        }

        return response()->json(['reply' => $reply]);
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

    private function summarizeHistory(array $history): string
    {
        $text = Collection::make($history)
            ->map(fn($m) => ucfirst($m['role']) . ": " . $m['content'])
            ->join("\n");

        $resp = $this->openai->chat()->create([
            'model' => env('OPENAI_CHAT_MODEL','gpt-4.1-nano'),
            'messages' => [
                ['role' => 'system', 'content' => 'سّو ملخص بسيط للمحادثة بالتركيز على معايير المستخدم.'],
                ['role' => 'user',   'content' => $text],
            ],
            'max_tokens' => 200,
        ]);

        return $resp['choices'][0]['message']['content'];
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