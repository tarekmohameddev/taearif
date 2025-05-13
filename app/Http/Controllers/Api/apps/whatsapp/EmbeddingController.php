<?php

namespace App\Http\Controllers\Api\apps\whatsapp;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Embedding;
use OpenAI as OpenAIClient;


class EmbeddingController extends Controller
{
    protected $openai;

    public function __construct()
    {
    
        $this->openai = OpenAIClient::client(env('OPENAI_API_KEY'));
    }

    /**
     * Store a new text embedding
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'text' => 'required|string',
        ]);

        // Generate embedding via OpenAI
        $resp = $this->openai->embeddings()->create([
            'model' => env('OPENAI_EMBEDDING_MODEL', 'text-embedding-3-small'),
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
