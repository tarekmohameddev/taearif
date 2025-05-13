<?php

namespace App\Http\Controllers\Api\apps\whatsapp;

use App\Http\Controllers\Controller;
use App\Models\Embedding;
use App\Models\User\RealestateManagement\Property;
use App\Models\User\RealestateManagement\PropertyContent;
use App\Models\ChatHistory;
use Illuminate\Http\Request;
use OpenAI as OpenAIClient;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ChatController extends Controller
{
    protected $openai;
    protected $maxTurns = 10;
    protected $systemInstructions = <<<INSTR
أنت موظف دعم عملاء في شركة إدارة عقارات في السعودية
- اجعل ردودك ودية ودافئة، كما لو كنت تتحاور مع جار، مع الحفاظ على الجدية والوضوح.
- استخدم جمل بسيطة ومختصرة لا تتجاوز سطرين إلى ثلاثة أسطر.
- عند حاجتك للتوضيح، اجعل طلبك بسيطًا ومباشرًا، مثلًا: "وش رقم الشقّة لو سمحت؟"
- إذا كان السؤال خارج نطاق العقارات، رد بعبارة: "عذرًا، أقدر أساعد بس في أمور إدارة العقارات."

# Output Format
- الردود بجمل قصيرة، لا تزيد عن ثلاثة أسطر.
- تجنب الودية الزائدة, وخليك طبيعي
- تجنب الثناء في اول كل رد

# Notes
- الهدف هو توفير تجربة تواصل مريحة وداعمة للعميل، مع الحفاظ على الالتزام بنطاق المساعدة المتعلق بالعقارات فقط.
INSTR;

    public function __construct()
    {
        $this->openai = OpenAIClient::client(env('OPENAI_API_KEY'));
    }

    public function chat(Request $request)
    {
        $user = $request->user();
        $userId = $user->id;
        $userMessage = $request->input('message');

        // Fetch or init history
        $record = ChatHistory::firstOrCreate(
            ['user_id' => $userId],
            ['history' => []]
        );

        $history = $record->history;
        $history[] = ['role' => 'user', 'content' => $userMessage];

        // Embedding search for FAQs/context
        $embedResp = $this->openai->embeddings()->create([
            'model' => env('OPENAI_EMBEDDING_MODEL','text-embedding-3-small'),
            'input' => $userMessage
        ]);

        $userVec = $embedResp['data'][0]['embedding'];
        $scores = Embedding::all()->map(fn($emb) => [
            'text' => $emb->text,
            'score' => $this->cosineSimilarity($userVec, $emb->embedding)
        ])->sortByDesc('score')->pluck('text')->take(2)->toArray();

        // Prepare messages
        $messages = [];
        if (!empty($history) && $history[0]['role'] === 'system_summary') {
            $messages[] = ['role'=>'system','content'=>$history[0]['content']];
            $history = array_slice($history, 1);
        } else {
            $messages[] = ['role'=>'system','content'=>$this->systemInstructions];
        }
        $messages[] = ['role'=>'system','content'=>'المعلومات ذات الصلة:' . PHP_EOL . '- ' . implode(PHP_EOL . '- ', $scores)];
        foreach (array_slice($history, -5) as $turn) {
            $messages[] = $turn;
        }

        // Function schema for property search
        $functions = [[
            'name' => 'search_properties',
            'description' => 'ابحث عن عقارات حسب المعايير',
            'parameters' => [
                'type' => 'object',
                'properties' => [
                    'location' => ['type'=>'string','description'=>'المدينة أو الحي'],
                    'min_bedrooms' => ['type'=>'integer','description'=>'الحد الأدنى لعدد الغرف'],
                    'max_price' => ['type'=>'number','description'=>'الالحد الأقصى للسعر (SAR)'],
                ],
                'required' => ['location']
            ]
        ]];

        // Chat completion
        log::info($messages);
        $resp = $this->openai->chat()->create([
            'model' => env('OPENAI_CHAT_MODEL','gpt-4.1-nano'),
            'messages' => $messages,
            'functions' => $functions,
            'function_call' => 'auto'
        ]);
        log::info(json_encode($resp));
        $assistantMsg = $resp['choices'][0]['message'];

        // Handle function calls
        if (isset($assistantMsg['function_call'])) {
            log::info('enter function calling');
            $call = $assistantMsg['function_call'];
            $args = json_decode($call['arguments'], true);
            // $props = Property::where('location', 'like', "%{$args['location']}%")
            //     ->when($args['min_bedrooms'] ?? null, fn($q, $v) => $q->where('bedrooms', '>=', $v))
            //     ->when($args['max_price'] ?? null, fn($q, $v) => $q->where('price', '<=', $v))
            //     ->limit(3)->get();

            log::info($args);
            $props = Property::select('user_properties.*')
            ->join('user_property_contents', 'user_property_contents.property_id', '=', 'user_properties.id')
            ->where('user_property_contents.address', 'like', "%{$args['location']}%")
            ->when($args['min_bedrooms'] ?? null, fn($q, $v) => $q->where('user_properties.beds', '>=', $v))
            ->when($args['max_price'] ?? null, fn($q, $v) => $q->where('user_properties.price', '<=', $v))
            ->limit(3)
            ->get();
        
            log::info(json_encode($props));
            $funcData = $props->map(fn($p) => [
                'title' => $p->title,
                'location' => $p->address,
                'bedrooms' => $p->beds,
                'price' => $p->price,
                'link' => url("/properties/{$p->id}")
            ]);

            // Append function response to history
            $history[] = [
                'role' => 'assistant',
                'name' => $call['name'],
                'content' => json_encode($funcData)
            ];

            // Get assistant natural language reply using function data
            $messages = array_merge($messages, [[
                'role' => 'assistant',
                'name' => $call['name'],
                'content' => json_encode($funcData)
            ]]);
            $finalResp = $this->openai->chat()->create([
                'model' => env('OPENAI_CHAT_MODEL','gpt-4.1-nano'),
                'messages' => $messages
            ]);
            $reply = $finalResp['choices'][0]['message']['content'];
        } else {
            // Direct assistant reply
            $reply = $assistantMsg['content'];
        }

        // Append assistant reply to history
        $history[] = ['role' => 'assistant', 'content' => $reply];

        // Summarize if too long
        if (count($history) > $this->maxTurns) {
            $summary = $this->summarizeHistory($history);
            $history = [['role' => 'system_summary', 'content' => $summary]];
        }

        // Save updated history
        $record->history = $history;
        $record->save();

        return response()->json(['reply' => $reply]);
    }

    private function cosineSimilarity(array $a, array $b): float
    {
        $dot = array_sum(array_map(fn($i) => $a[$i] * $b[$i], array_keys($a)));
        $normA = sqrt(array_sum(array_map(fn($x) => $x * $x, $a)));
        $normB = sqrt(array_sum(array_map(fn($x) => $x * $x, $b)));
        return $dot / ($normA * $normB);
    }

    private function summarizeHistory(array $history): string
    {
        $text = collect($history)->map(fn($m) => ucfirst($m['role']) . ": " . $m['content'])->join("\n");
        $resp = $this->openai->chat()->create([
            'model' => env('OPENAI_CHAT_MODEL','gpt-4.1-nano'),
            'messages' => [
                ['role' => 'system', 'content' => 'قم بتلخيص المحادثة التالية بإيجاز بالعربية مع التركيز على معايير المستخدم.'],
                ['role' => 'user', 'content' => $text]
            ],
            'max_tokens' => 200
        ]);
        return $resp['choices'][0]['message']['content'];
    }
}
