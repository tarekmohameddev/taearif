<?php

namespace App\Http\Controllers\Api\V1\WhatsApp;

use App\Domain\Communication\WhatsApp\Services\WhatsAppConversationService;
use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\Api\V1\WhatsApp\StoreConversationRequest;
use App\Http\Requests\Api\V1\WhatsApp\UpdateConversationRequest;
use App\Models\WaNumber;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ConversationController extends BaseApiController
{
    public function __construct(private readonly WhatsAppConversationService $conversationService) {}

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
