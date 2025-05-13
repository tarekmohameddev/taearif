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

use OpenAI as OpenAIClient;


class ChatController extends Controller
{

    protected int $maxTurns = 10;
    protected string $systemInstructions;

    public function __construct()
    {

        $this->openai = OpenAIClient::client(env('OPENAI_API_KEY'));
        $this->systemInstructions = implode("\n", [
            'أنت موظف دعم عملاء في شركة إدارة عقارات في السعودية.',
            '– ردودك ودية ودافئة، مع الجدية والوضوح.',
            '– استخدم جمل بسيطة لا تتجاوز 3 أسطر.',
            '– للتوضيح: اسأل بشكل مباشر (مثال: "وش رقم الشقّة؟").',
            '– خارج العقارات: "عذرًا، أقدر أساعد بس في أمور إدارة العقارات."'
        ]);
    }

    public function chat(Request $request)
    {
        $userId = $request->user()->id;
        $userMessage = $request->input('message');

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
                'description' => 'ابحث عن عقارات حسب المعايير',
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
            $history[] = ['role' => 'assistant', 'name' => $funcName, 'content' => json_encode($funcResponse)];
        } else {
            // Direct reply
            $reply = $choice['content'] ?? '';
        }

        $history[] = ['role' => 'assistant', 'content' => $reply];

        // Summarize if needed
        if (count($history) > $this->maxTurns) {
            $summary = $this->summarizeHistory($history);
            $history = [['role' => 'system_summary', 'content' => $summary]];
        }

        // Save history
        $record->history = $history;
        $record->save();

        return response()->json(['reply' => $reply]);
    }

    protected function handleSearchProperties(array $args): array
    {
        $userId   = auth()->id();

        // Base query restricted to this user
        $query = Property::with([
            'category',
            'user',
            'contents',
            'proertyAmenities.amenity'
        ])->where('user_id', $userId);

        // Apply filters
        if (!empty($args['location'])) {
            $location = $args['location'];
            $query->whereHas('contents', fn($q) =>
                $q->where('language_id', 1)
                  ->where(fn($qq) =>
                      $qq->where('city_id', $this->mapCity($location))
                         ->orWhere('title', 'like', "%{$location}%")
                         ->orWhere('address', 'like', "%{$location}%")
                  )
            );
        }
        if (!empty($args['min_bedrooms'])) {
            $query->where('beds', '>=', $args['min_bedrooms']);
        }
        if (!empty($args['max_price'])) {
            $query->where('price', '<=', $args['max_price']);
        }
        if (!empty($args['type'])) {
            $query->where('type', $args['type']);
        }
        if (!empty($args['purpose'])) {
            $query->where('purpose', $args['purpose']);
        }

        // Pagination parameters
        $perPage = $args['page_size'] ?? 10;
        $page    = $args['page'] ?? 1;

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

    private function mapCity(string $name): int
    {
        $map = ['الرياض' => 1, 'جدة' => 2];
        return $map[$name] ?? 0;
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