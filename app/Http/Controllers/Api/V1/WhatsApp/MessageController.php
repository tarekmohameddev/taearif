<?php

namespace App\Http\Controllers\Api\V1\WhatsApp;

use App\Domain\Communication\Contracts\CommunicationService;
use App\Domain\Communication\DTOs\SendMessageDto;
use App\Domain\Communication\Exceptions\ConversationNotFoundException;
use App\Domain\Communication\Exceptions\IdempotencyConflictException;
use App\Domain\Communication\Exceptions\InsufficientCreditsException;
use App\Domain\Communication\Exceptions\ProviderSendFailedException;
use App\Domain\Communication\Exceptions\UnsupportedChannelException;
use App\Domain\Communication\Exceptions\WaNumberNotActiveException;
use App\Domain\Communication\Exceptions\WaNumberNotFoundException;
use App\Domain\Communication\Support\CommunicationEndpoints;
use App\Domain\Communication\WhatsApp\Services\WhatsAppTemplateService;
use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\Api\V1\WhatsApp\SendWhatsAppMessageRequest;
use App\Http\Requests\Api\V1\WhatsApp\SendWhatsAppTemplateRequest;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class MessageController extends BaseApiController
{
    public function __construct(
        private readonly CommunicationService $communicationService,
        private readonly WhatsAppTemplateService $templateService
    ) {}

    public function index(Request $request, int $id): JsonResponse
    {
        $tenantOwnerId = (int) auth()->user()->tenantOwnerId();

        $conversation = Conversation::where('user_id', $tenantOwnerId)->find($id);
        if (! $conversation) {
            return response()->json(['status' => 'error', 'code' => 'WA_CONVERSATION_NOT_FOUND', 'message' => 'Conversation not found.'], 404);
        }

        $perPage = (int) $request->input('per_page', 20);
        $perPage = min(max($perPage, 1), 100);

        $items = Message::where('conversation_id', $conversation->id)
            ->orderByDesc('created_at')
            ->paginate($perPage);

        $messages = $items->getCollection()->map(function (Message $m) {
            $meta = $m->meta;
            if (! is_array($meta)) {
                $meta = is_string($meta) ? (array) json_decode($meta, true) : [];
            }

            return [
                'id' => (string) $m->id,
                'direction' => $m->direction,
                'status' => $m->status,
                'content' => is_scalar($m->content) ? (string) $m->content : (string) json_encode($m->content ?? ''),
                'provider_message_id' => $m->provider_message_id,
                'meta' => $meta,
                'created_at' => $m->created_at?->toISOString(),
            ];
        });

        return $this->ok([
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

    public function send(SendWhatsAppMessageRequest $request, int $id): JsonResponse
    {
        $idempotencyKey = trim((string) request()->header('Idempotency-Key', '')) ?: Str::uuid()->toString();
        $validated = $request->validated();
        $tenantOwnerId = (int) auth()->user()->tenantOwnerId();

        $dto = new SendMessageDto(
            userId: $tenantOwnerId,
            conversationId: $id,
            content: (string) $validated['content'],
            channel: 'whatsapp',
            waNumberId: (int) $validated['wa_number_id'],
            endpointSignature: CommunicationEndpoints::WHATSAPP_SEND_MESSAGE,
        );

        try {
            $message = $this->communicationService->sendMessage($dto, $idempotencyKey);
        } catch (UnsupportedChannelException $e) {
            return response()->json(['status' => 'error', 'code' => 'VALIDATION_ERROR', 'message' => $e->getMessage()], 422);
        } catch (ConversationNotFoundException $e) {
            return response()->json(['status' => 'error', 'code' => 'WA_CONVERSATION_NOT_FOUND', 'message' => $e->getMessage()], 404);
        } catch (WaNumberNotFoundException $e) {
            return response()->json(['status' => 'error', 'code' => 'WA_NUMBER_NOT_FOUND', 'message' => $e->getMessage()], 404);
        } catch (WaNumberNotActiveException $e) {
            return response()->json(['status' => 'error', 'code' => 'WA_NUMBER_NOT_ACTIVE', 'message' => $e->getMessage()], 422);
        } catch (InsufficientCreditsException $e) {
            return response()->json(['status' => 'error', 'code' => 'INSUFFICIENT_CREDITS', 'message' => $e->getMessage()], 400);
        } catch (IdempotencyConflictException $e) {
            return response()->json(['status' => 'error', 'code' => strtoupper((string) $e->reason), 'message' => $e->getMessage()], 409);
        } catch (ProviderSendFailedException $e) {
            return response()->json(['status' => 'error', 'code' => 'PROVIDER_SEND_FAILED', 'message' => $e->getMessage()], 502);
        }

        return $this->ok([
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

    public function sendTemplate(SendWhatsAppTemplateRequest $request, int $id): JsonResponse
    {
        $idempotencyKey = trim((string) request()->header('Idempotency-Key', '')) ?: Str::uuid()->toString();
        $validated = $request->validated();
        $tenantOwnerId = (int) auth()->user()->tenantOwnerId();
        $template = $this->templateService->findForUser($tenantOwnerId, (int) $validated['template_id']);
        if (! $template) {
            return response()->json(['status' => 'error', 'code' => 'WA_TEMPLATE_NOT_FOUND', 'message' => 'Template not found.'], 404);
        }

        $variables = $validated['variables'] ?? [];
        $content = $this->templateService->renderContent($template, $variables);

        $dto = new SendMessageDto(
            userId: $tenantOwnerId,
            conversationId: $id,
            content: $content,
            channel: 'whatsapp',
            waNumberId: (int) $validated['wa_number_id'],
            endpointSignature: CommunicationEndpoints::WHATSAPP_SEND_TEMPLATE,
            templateId: (int) $validated['template_id'],
            variables: $variables,
        );

        try {
            $message = $this->communicationService->sendMessage($dto, $idempotencyKey);
        } catch (UnsupportedChannelException $e) {
            return response()->json(['status' => 'error', 'code' => 'VALIDATION_ERROR', 'message' => $e->getMessage()], 422);
        } catch (ConversationNotFoundException $e) {
            return response()->json(['status' => 'error', 'code' => 'WA_CONVERSATION_NOT_FOUND', 'message' => $e->getMessage()], 404);
        } catch (WaNumberNotFoundException $e) {
            return response()->json(['status' => 'error', 'code' => 'WA_NUMBER_NOT_FOUND', 'message' => $e->getMessage()], 404);
        } catch (WaNumberNotActiveException $e) {
            return response()->json(['status' => 'error', 'code' => 'WA_NUMBER_NOT_ACTIVE', 'message' => $e->getMessage()], 422);
        } catch (InsufficientCreditsException $e) {
            return response()->json(['status' => 'error', 'code' => 'INSUFFICIENT_CREDITS', 'message' => $e->getMessage()], 400);
        } catch (IdempotencyConflictException $e) {
            return response()->json(['status' => 'error', 'code' => strtoupper((string) $e->reason), 'message' => $e->getMessage()], 409);
        } catch (ProviderSendFailedException $e) {
            return response()->json(['status' => 'error', 'code' => 'PROVIDER_SEND_FAILED', 'message' => $e->getMessage()], 502);
        }

        return $this->ok([
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
