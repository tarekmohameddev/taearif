<?php

namespace App\Http\Controllers\Api\V1\WhatsApp;

use App\Domain\Communication\WhatsApp\Bot\HandoffService;
use App\Domain\Communication\WhatsApp\Services\WhatsAppConversationService;
use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\Api\V1\WhatsApp\StoreConversationRequest;
use App\Http\Requests\Api\V1\WhatsApp\UpdateConversationRequest;
use App\Models\WaNumber;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ConversationController extends BaseApiController
{
    public function __construct(
        private readonly WhatsAppConversationService $conversationService,
        private readonly HandoffService $handoffService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $userId = (int) auth()->user()->tenantOwnerId();
        $perPage = (int) $request->input('per_page', 20);
        $items = $this->conversationService->listForUser($userId, [
            'status' => $request->input('status'),
            'wa_number_id' => $request->input('wa_number_id'),
            'search' => $request->input('search'),
            'sort_by' => $request->input('sort_by'),
            'sort_dir' => $request->input('sort_dir'),
            'needs_attention' => $request->input('needs_attention'),
        ], $perPage);

        return $this->ok([
            'data' => $items->items(),
            'pagination' => [
                'current_page' => $items->currentPage(),
                'per_page' => $items->perPage(),
                'total' => $items->total(),
                'last_page' => $items->lastPage(),
            ],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $userId = (int) auth()->user()->tenantOwnerId();
        $state = $this->conversationService->findForUserByConversationOrStateId($userId, $id);

        if (! $state) {
            return response()->json(['status' => 'error', 'code' => 'WA_CONVERSATION_NOT_FOUND', 'message' => 'Conversation not found.'], 404);
        }

        return $this->ok(['data' => $state]);
    }

    public function store(StoreConversationRequest $request): JsonResponse
    {
        $userId = (int) auth()->user()->tenantOwnerId();
        $validated = $request->validated();
        $waNumberId = null;
        if (isset($validated['wa_number_id'])) {
            $waNumber = WaNumber::where('id', (int) $validated['wa_number_id'])->where('user_id', $userId)->first();
            if (! $waNumber) {
                return response()->json(['status' => 'error', 'code' => 'WA_NUMBER_NOT_FOUND', 'message' => 'WhatsApp number not found.'], 404);
            }
            $waNumberId = $waNumber->id;
        }

        $normalized = $this->normalizeIdentifier($validated['external_party_identifier']);
        $conversation = $this->conversationService->createOrReturnConversation($userId, $normalized, $waNumberId);
        $state = $this->conversationService->findForUser($userId, (int) $conversation->id);

        return $this->ok(['data' => $state ? $state->load('conversation', 'waNumber') : $conversation]);
    }

    public function update(UpdateConversationRequest $request, int $id): JsonResponse
    {
        $userId = (int) auth()->user()->tenantOwnerId();
        $state = $this->conversationService->findForUserByConversationOrStateId($userId, $id);

        if (! $state) {
            return response()->json(['status' => 'error', 'code' => 'WA_CONVERSATION_NOT_FOUND', 'message' => 'Conversation not found.'], 404);
        }

        $validated = $request->validated();
        if (isset($validated['status'])) {
            $this->conversationService->updateStatus($state, $validated['status']);
        }

        return $this->ok(['data' => $state->refresh()]);
    }

    public function read(int $id): JsonResponse
    {
        $userId = (int) auth()->user()->tenantOwnerId();
        $state = $this->conversationService->findForUserByConversationOrStateId($userId, $id);

        if (! $state) {
            return response()->json(['status' => 'error', 'code' => 'WA_CONVERSATION_NOT_FOUND', 'message' => 'Conversation not found.'], 404);
        }

        $this->conversationService->markRead($state);

        return $this->ok(['data' => $state->refresh()]);
    }

    public function star(int $id): JsonResponse
    {
        $userId = (int) auth()->user()->tenantOwnerId();
        $state = $this->conversationService->findForUserByConversationOrStateId($userId, $id);

        if (! $state) {
            return response()->json(['status' => 'error', 'code' => 'WA_CONVERSATION_NOT_FOUND', 'message' => 'Conversation not found.'], 404);
        }

        $this->conversationService->toggleStarred($state);

        return $this->ok(['data' => $state->refresh()]);
    }

    /**
     * Resume the AI bot for a conversation that was paused due to an agent takeover.
     * Only `agent_takeover` pauses may be cleared here; safety/compliance pauses are excluded.
     */
    public function resumeBot(int $id): JsonResponse
    {
        $userId = (int) auth()->user()->tenantOwnerId();
        $state  = $this->conversationService->findForUserByConversationOrStateId($userId, $id);

        if (! $state) {
            return response()->json(['status' => 'error', 'code' => 'WA_CONVERSATION_NOT_FOUND', 'message' => 'Conversation not found.'], 404);
        }

        $aiState = $state->aiState()->first();

        if ($aiState === null) {
            return response()->json(['status' => 'error', 'code' => 'BOT_STATE_NOT_FOUND', 'message' => 'No AI bot state found for this conversation.'], 404);
        }

        // Guard: only allow resuming agent_takeover pauses; other reasons (compliance, etc.) must not be bypassed.
        if ($aiState->isBotPaused() && $aiState->handoff_reason !== 'agent_takeover') {
            return response()->json([
                'status'  => 'error',
                'code'    => 'BOT_PAUSE_NOT_RESUMABLE',
                'message' => 'The bot cannot be manually resumed while paused for: ' . $aiState->handoff_reason . '.',
            ], 422);
        }

        $this->handoffService->resumeBot($aiState);

        $state->unsetRelation('aiState');

        return $this->ok([
            'bot_paused_until' => null,
            'handoff_reason'   => null,
            'needs_attention'  => false,
        ]);
    }

    private function normalizeIdentifier(string $value): string
    {
        $value = trim($value);
        $value = preg_replace('/[\s\-]+/', '', $value);
        if (preg_match('/^\+?\d+$/', $value)) {
            $value = ltrim($value, '+');
            if (strlen($value) > 0 && $value[0] !== '0') {
                $value = '+' . $value;
            }
        }

        return $value;
    }
}
