<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Communication\Contracts\CommunicationService;
use App\Domain\Communication\DTOs\SendMessageDto;
use App\Domain\Communication\Exceptions\ConversationNotFoundException;
use App\Domain\Communication\Exceptions\IdempotencyConflictException;
use App\Domain\Communication\Exceptions\InsufficientCreditsException;
use App\Domain\Communication\Exceptions\ProviderSendFailedException;
use App\Domain\Communication\Exceptions\UnsupportedChannelException;
use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    public function __construct(
        private readonly CommunicationService $communicationService
    ) {}

    /**
     * List messages for a conversation (tenant-scoped; 404 if conversation not owned).
     */
    public function index(Request $request, string $id): JsonResponse
    {
        $tenantOwnerId = auth()->user()->tenantOwnerId();

        $conversation = Conversation::where('user_id', $tenantOwnerId)->findOrFail($id);

        $perPage = (int) $request->input('per_page', 20);
        $perPage = min(max($perPage, 1), 100);

        $items = Message::where('conversation_id', $conversation->id)
            ->orderByDesc('created_at')
            ->paginate($perPage);

        $messages = $items->getCollection()->map(function (Message $m) {
            $meta = $m->meta;
            if (!\is_array($meta)) {
                $meta = \is_string($meta) ? (array) \json_decode($meta, true) : [];
            }
            return [
                'id' => (string) $m->id,
                'direction' => $m->direction,
                'status' => $m->status,
                'content' => \is_scalar($m->content) ? (string) $m->content : (string) \json_encode($m->content ?? ''),
                'provider_message_id' => $m->provider_message_id,
                'meta' => $meta,
                'created_at' => $m->created_at?->toISOString(),
            ];
        });

        return response()->json([
            'status' => 'success',
            'data' => [
                'messages' => $messages,
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
     * Send an outbound message (Phase 3). Requires Idempotency-Key header.
     */
    public function send(Request $request): JsonResponse
    {
        $idempotencyKey = $request->header('Idempotency-Key');
        if ($idempotencyKey === null || trim((string) $idempotencyKey) === '') {
            return response()->json([
                'status' => 'error',
                'code' => 'VALIDATION_ERROR',
                'message' => 'Idempotency-Key header is required.',
            ], 422);
        }

        $validated = $request->validate([
            'conversation_id' => 'required|integer',
            'content' => 'required|string',
            'channel' => 'nullable|string|in:whatsapp',
        ]);

        $channel = $validated['channel'] ?? 'whatsapp';
        $tenantOwnerId = (int) auth()->user()->tenantOwnerId();

        $dto = new SendMessageDto(
            userId: $tenantOwnerId,
            conversationId: (int) $validated['conversation_id'],
            content: (string) $validated['content'],
            channel: $channel,
        );

        try {
            $message = $this->communicationService->sendMessage($dto, trim((string) $idempotencyKey));
        } catch (UnsupportedChannelException $e) {
            return response()->json([
                'status' => 'error',
                'code' => 'VALIDATION_ERROR',
                'message' => $e->getMessage(),
            ], 422);
        } catch (ConversationNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'code' => 'CONVERSATION_NOT_FOUND',
                'message' => $e->getMessage(),
            ], 404);
        } catch (InsufficientCreditsException $e) {
            return response()->json([
                'status' => 'error',
                'code' => 'INSUFFICIENT_CREDITS',
                'message' => $e->getMessage(),
            ], 400);
        } catch (IdempotencyConflictException $e) {
            return response()->json([
                'status' => 'error',
                'code' => strtoupper((string) $e->reason),
                'message' => $e->getMessage(),
            ], 409);
        } catch (ProviderSendFailedException $e) {
            return response()->json([
                'status' => 'error',
                'code' => 'PROVIDER_SEND_FAILED',
                'message' => $e->getMessage(),
            ], 502);
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'message' => [
                    'id' => $message->id,
                    'conversation_id' => $message->conversation_id,
                    'direction' => $message->direction,
                    'status' => $message->status,
                    'content' => is_scalar($message->content) ? (string) $message->content : (string) json_encode($message->content ?? ''),
                    'provider_message_id' => $message->provider_message_id,
                ],
            ],
        ]);
    }
}
