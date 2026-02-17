<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class ConversationController extends Controller
{
    private function toIsoString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        if ($value instanceof \DateTimeInterface) {
            return $value->format(\DateTimeInterface::ATOM);
        }
        if (\is_string($value)) {
            try {
                return Carbon::parse($value)->format(\DateTimeInterface::ATOM);
            } catch (\Throwable) {
                return $value;
            }
        }
        return null;
    }
    /**
     * List conversations for the authenticated tenant (tenant-owner scoped).
     */
    public function index(Request $request): JsonResponse
    {
        $tenantOwnerId = auth()->user()->tenantOwnerId();

        $perPage = (int) $request->input('per_page', 20);
        $perPage = min(max($perPage, 1), 100);

        $query = Conversation::query()
            ->where('user_id', $tenantOwnerId)
            ->with(['messages' => fn ($q) => $q->latest()->limit(1)])
            ->withCount('messages')
            ->orderByDesc('last_message_at');

        if ($request->filled('search')) {
            $term = '%' . $request->input('search') . '%';
            $query->where(function ($q) use ($term) {
                $q->where('external_party_identifier', 'like', $term)
                    ->orWhereHas('messages', fn ($mq) => $mq->where('content', 'like', $term));
            });
        }

        $items = $query->paginate($perPage);

        $conversations = $items->getCollection()->map(function (Conversation $c) {
            $latest = $c->messages->first();
            $contentPreview = null;
            if ($latest !== null) {
                $raw = $latest->content;
                $contentPreview = \is_scalar($raw) ? Str::limit((string) $raw, 100) : Str::limit((string) \json_encode($raw ?? []), 100);
            }
            return [
                'id' => (string) $c->id,
                'user_id' => (int) $c->user_id,
                'channel' => $c->channel,
                'external_party_identifier' => $c->external_party_identifier,
                'last_message_at' => $this->toIsoString($c->last_message_at),
                'message_count' => $c->messages_count,
                'latest_message_preview' => $latest ? [
                    'content' => $contentPreview,
                    'direction' => $latest->direction,
                    'created_at' => $this->toIsoString($latest->created_at),
                ] : null,
                'created_at' => $this->toIsoString($c->created_at),
                'updated_at' => $this->toIsoString($c->updated_at),
            ];
        });

        return response()->json([
            'status' => 'success',
            'data' => [
                'conversations' => $conversations,
                'pagination' => [
                    'current_page' => $items->currentPage(),
                    'per_page' => $items->perPage(),
                    'total' => $items->total(),
                    'last_page' => $items->lastPage(),
                ],
            ],
        ]);
    }

    /**
     * Show a single conversation (tenant-scoped; 404 if not owned).
     */
    public function show(string $id): JsonResponse
    {
        $tenantOwnerId = auth()->user()->tenantOwnerId();

        $conversation = Conversation::where('user_id', $tenantOwnerId)
            ->with(['messages' => fn ($q) => $q->latest()->limit(1)])
            ->withCount('messages')
            ->findOrFail($id);

        $latest = $conversation->messages->first();

        return response()->json([
            'status' => 'success',
            'data' => [
                'id' => (string) $conversation->id,
                'user_id' => (int) $conversation->user_id,
                'channel' => $conversation->channel,
                'external_party_identifier' => $conversation->external_party_identifier,
                'last_message_at' => $this->toIsoString($conversation->last_message_at),
                'message_count' => $conversation->messages_count,
                'latest_message_preview' => $latest ? [
                    'id' => (string) $latest->id,
                    'content' => $latest->content,
                    'direction' => $latest->direction,
                    'status' => $latest->status,
                    'created_at' => $this->toIsoString($latest->created_at),
                ] : null,
                'created_at' => $this->toIsoString($conversation->created_at),
                'updated_at' => $this->toIsoString($conversation->updated_at),
            ],
        ]);
    }
}
