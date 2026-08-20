<?php

namespace App\Http\Controllers\Api\apps\whatsapp;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Apps\Whatsapp\StoreEmbeddingRequest;
use Illuminate\Http\Request;
use App\Models\Embedding;
use OpenAI as OpenAIClient;


class EmbeddingController extends Controller
{
    protected $openai;

    public function __construct()
    {
    
        $this->openai = OpenAIClient::client((string) config('openai.api_key', ''));
    }

    /**
     * Store a new text embedding
     */
    public function store(StoreEmbeddingRequest $request)
    {
        $data = $request->validated();

        // Generate embedding via OpenAI
        $resp = $this->openai->embeddings()->create([
            'model' => (string) config('openai.embedding_model', 'text-embedding-3-small'),
            'input' => $data['text'],
        ]);
        $vector = $resp['data'][0]['embedding'];

        // Save to database
        $emb = Embedding::create([
            'text' => $data['text'],
            'embedding' => $vector,
        ]);

        return response()->json($emb, 201);
    }
}
