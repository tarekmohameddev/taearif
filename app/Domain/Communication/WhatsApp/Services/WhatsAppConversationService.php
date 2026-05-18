<?php

namespace App\Domain\Communication\WhatsApp\Services;

use App\Models\Conversation;
use App\Models\WaConversationState;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class WhatsAppConversationService
{
    public function listForUser(int $userId, array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = WaConversationState::query()
            ->with(['conversation', 'waNumber'])
            ->where('user_id', $userId)
            ->whereHas('conversation', fn ($q) => $q->where('channel', 'whatsapp'));

        if (isset($filters['status']) && $filters['status'] !== null && $filters['status'] !== '') {
            $query->where('status', (string) $filters['status']);
        }
        if (isset($filters['wa_number_id']) && $filters['wa_number_id'] !== null) {
            $query->where('wa_number_id', (int) $filters['wa_number_id']);
        }
        if (! empty($filters['search'])) {
            $term = '%' . $filters['search'] . '%';
            $query->whereHas('conversation', function ($q) use ($term) {
                $q->where('external_party_identifier', 'like', $term);
            });
        }

        $allowedSortColumns = [
            'last_message_time',
            'unread_count',
            'status',
            'created_at',
            'updated_at',
        ];
        $sortBy = $filters['sort_by'] ?? 'last_message_time';
        if (! in_array($sortBy, $allowedSortColumns, true)) {
            $sortBy = 'last_message_time';
        }
        $sortDir = strtolower($filters['sort_dir'] ?? 'desc') === 'asc' ? 'asc' : 'desc';
        $query->orderBy($sortBy, $sortDir);

        return $query->paginate(min(max($perPage, 1), 100));
    }

    public function findForUser(int $userId, int $conversationId): ?WaConversationState
    {
        return WaConversationState::query()
            ->with(['conversation', 'waNumber'])
            ->where('user_id', $userId)
            ->where('conversation_id', $conversationId)
            ->whereHas('conversation', fn ($q) => $q->where('channel', 'whatsapp'))
            ->first();
    }

    public function createOrReturnConversation(int $userId, string $externalPartyIdentifierNormalized, ?int $waNumberId = null): Conversation
    {
        // Full implementation in Gate 5B.2
        $conversation = Conversation::firstOrCreate(
            [
                'user_id' => $userId,
                'channel' => 'whatsapp',
                'external_party_identifier' => $externalPartyIdentifierNormalized,
            ],
            ['last_message_at' => now()]
        );

        $state = WaConversationState::firstOrNew(['conversation_id' => $conversation->id]);

        if (! $state->exists) {
            $state->user_id = $userId;
            $state->status = 'active';
            $state->is_starred = false;
            $state->unread_count = 0;
        }

        if ($state->wa_number_id === null && $waNumberId !== null) {
            $state->wa_number_id = $waNumberId;
        }

        $state->save();

        return $conversation;
    }

    public function markRead(WaConversationState $state): void
    {
        $state->update(['unread_count' => 0]);
    }

    public function toggleStarred(WaConversationState $state): void
    {
        $state->update(['is_starred' => ! $state->is_starred]);
    }

    public function updateStatus(WaConversationState $state, string $status): void
    {
        $state->update(['status' => $status]);
    }
}
