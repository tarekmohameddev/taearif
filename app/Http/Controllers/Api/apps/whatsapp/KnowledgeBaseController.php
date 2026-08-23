<?php

namespace App\Http\Controllers\Api\apps\whatsapp;

use App\Domain\Ai\Knowledge\EmbeddingService;
use App\Http\Controllers\Api\BaseApiController;
use App\Models\AiKnowledgeChunk;
use App\Models\AiKnowledgeSource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * CRUD and ingestion endpoints for the per-tenant knowledge base.
 * Without these endpoints the retrieval layer stays permanently empty.
 */
class KnowledgeBaseController extends BaseApiController
{
    public function __construct(
        private readonly EmbeddingService $embedder,
    ) {}

    /**
     * List all knowledge sources for the authenticated tenant.
     */
    public function index(): JsonResponse
    {
        $sources = AiKnowledgeSource::where('user_id', auth()->id())
            ->orderByDesc('created_at')
            ->get(['id', 'type', 'name', 'chunk_count', 'active', 'last_indexed_at', 'created_at']);

        return response()->json(['data' => $sources]);
    }

    /**
     * Create a new knowledge source and index its content immediately.
     *
     * Accepts a plain-text body. For file uploads wire the calling client
     * to send the file text as the `content` field.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'    => 'required|string|max:255',
            'type'    => 'required|in:text,faq,property_faq,document',
            'content' => 'required|string|min:10|max:100000',
            'active'  => 'boolean',
        ]);

        $userId = (int) auth()->id();

        return DB::transaction(function () use ($validated, $userId) {
            $source = AiKnowledgeSource::create([
                'user_id'  => $userId,
                'type'     => $validated['type'],
                'name'     => $validated['name'],
                'active'   => $validated['active'] ?? true,
            ]);

            $chunkCount = $this->embedder->indexSource($source, $validated['content']);
            $source->refresh();

            return response()->json([
                'id'          => $source->id,
                'name'        => $source->name,
                'chunk_count' => $chunkCount,
                'message'     => 'Source indexed successfully.',
            ], 201);
        });
    }

    /**
     * Show a single source with its chunk count.
     */
    public function show(int $id): JsonResponse
    {
        $source = AiKnowledgeSource::where('user_id', auth()->id())->findOrFail($id);
        return response()->json($source);
    }

    /**
     * Update source name/active flag and optionally re-index if content provided.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $source = AiKnowledgeSource::where('user_id', auth()->id())->findOrFail($id);

        $validated = $request->validate([
            'name'    => 'sometimes|string|max:255',
            'active'  => 'sometimes|boolean',
            'content' => 'sometimes|string|min:10|max:100000',
        ]);

        $userId = (int) auth()->id();

        return DB::transaction(function () use ($source, $validated, $userId) {
            if (isset($validated['name']))   { $source->name   = $validated['name']; }
            if (isset($validated['active'])) { $source->active = $validated['active']; }

            if (isset($validated['content'])) {
                // Delete old chunks and re-index
                AiKnowledgeChunk::where('source_id', $source->id)->delete();
                $this->embedder->indexSource($source, $validated['content']);
                $source->refresh();
            }

            $source->save();

            return response()->json(['success' => true, 'chunk_count' => $source->chunk_count]);
        });
    }

    /**
     * Delete a knowledge source and all its chunks.
     */
    public function destroy(int $id): JsonResponse
    {
        $source = AiKnowledgeSource::where('user_id', auth()->id())->findOrFail($id);

        DB::transaction(function () use ($source) {
            AiKnowledgeChunk::where('source_id', $source->id)->delete();
            $source->delete();
        });

        Cache::forget('ai.embedding.matrix.' . (int) auth()->id());

        return response()->json(['success' => true]);
    }

    // ─── FAQ Candidates management ────────────────────────────────────────────

    /**
     * List auto-promoted FAQ candidates so tenants can review / correct them.
     */
    public function faqCandidates(Request $request): JsonResponse
    {
        $userId = (int) auth()->id();
        $status = $request->get('status', 'auto_approved');

        $candidates = \App\Models\BotFaqCandidate::where('user_id', $userId)
            ->where('approval_status', $status)
            ->orderByDesc('occurrence_count')
            ->paginate(25);

        return response()->json($candidates);
    }

    /**
     * Update the drafted answer for an FAQ candidate (tenant correction).
     */
    public function updateFaqCandidate(Request $request, int $id): JsonResponse
    {
        $candidate = \App\Models\BotFaqCandidate::where('user_id', auth()->id())->findOrFail($id);

        $validated = $request->validate([
            'drafted_answer'   => 'required|string|min:5|max:2000',
            'approval_status'  => 'sometimes|in:auto_approved,pending,rejected',
        ]);

        $userId = (int) auth()->id();

        DB::transaction(function () use ($candidate, $validated, $userId) {
            $candidate->update($validated);

            // Update the corresponding knowledge source chunk if it exists
            if ($candidate->knowledge_source_id) {
                $source = AiKnowledgeSource::where('user_id', $userId)
                    ->find($candidate->knowledge_source_id);
                if ($source) {
                    $newContent = $candidate->question . "\n" . $validated['drafted_answer'];
                    AiKnowledgeChunk::where('source_id', $source->id)->delete();
                    $this->embedder->indexSource($source, $newContent);
                }
            }
        });

        return response()->json(['success' => true]);
    }
}
