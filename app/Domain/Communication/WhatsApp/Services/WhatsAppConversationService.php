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
            ->with(['conversation', 'waNumber', 'aiState'])
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

        if ($this->isTruthyFilter($filters['needs_attention'] ?? null)) {
            $excluded = WaConversationState::HANDOFF_REASONS_EXCLUDED_FROM_ATTENTION;
            $query->whereHas('aiState', function ($q) use ($excluded) {
                $q->whereNotNull('bot_paused_until')
                    ->where('bot_paused_until', '>', now())
                    ->where(function ($inner) use ($excluded) {
                        $inner->whereNull('handoff_reason')
                            ->orWhereNotIn('handoff_reason', $excluded);
                    });
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

        $paginator = $query->paginate(min(max($perPage, 1), 100));
        $paginator->getCollection()->each(function (WaConversationState $state) {
            // Expose only needs_attention / handoff_reason / bot_paused_until via appends.
            $state->makeHidden(['aiState']);
        });

        return $paginator;
    }

    public function findForUser(int $userId, int $conversationId): ?WaConversationState
    {
        $state = WaConversationState::query()
            ->with(['conversation', 'waNumber', 'aiState'])
            ->where('user_id', $userId)
            ->where('conversation_id', $conversationId)
            ->whereHas('conversation', fn ($q) => $q->where('channel', 'whatsapp'))
            ->first();

        return $this->hideAiStateRelation($state);
    }

    public function findForUserByConversationOrStateId(int $userId, int $id): ?WaConversationState
    {
        $state = $this->findForUser($userId, $id);
        if ($state !== null) {
            return $state;
        }

        $state = WaConversationState::query()
            ->with(['conversation', 'waNumber', 'aiState'])
            ->where('user_id', $userId)
            ->where('id', $id)
            ->whereHas('conversation', fn ($q) => $q->where('channel', 'whatsapp'))
            ->first();

        return $this->hideAiStateRelation($state);
    }

    private function hideAiStateRelation(?WaConversationState $state): ?WaConversationState
    {
        if ($state !== null) {
            $state->makeHidden(['aiState']);
        }

        return $state;
    }

    private function isTruthyFilter(mixed $value): bool
    {
        if ($value === null || $value === '') {
            return false;
        }

        if (is_bool($value)) {
            return $value;
        }

        return in_array(strtolower((string) $value), ['1', 'true', 'yes'], true);
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
