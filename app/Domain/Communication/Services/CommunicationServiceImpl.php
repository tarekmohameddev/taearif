<?php

namespace App\Domain\Communication\Services;

use App\Domain\Communication\Contracts\CommunicationService;
use App\Domain\Communication\Contracts\CreditService;
use App\Domain\Communication\Contracts\MessageDispatcher;
use App\Domain\Communication\DTOs\IdempotencyStartResult;
use App\Domain\Communication\DTOs\SendMessageDto;
use App\Domain\Communication\Events\ConversationOpened;
use App\Domain\Communication\Events\MessageReceived as MessageReceivedEvent;
use App\Domain\Communication\Events\MessageSent as MessageSentEvent;
use App\Domain\Communication\Exceptions\ConversationNotFoundException;
use App\Domain\Communication\Exceptions\IdempotencyConflictException;
use App\Domain\Communication\Exceptions\InsufficientCreditsException;
use App\Domain\Communication\Exceptions\UnsupportedChannelException;
use App\Domain\Communication\Exceptions\WaNumberNotActiveException;
use App\Domain\Communication\Exceptions\WaNumberNotFoundException;
use App\Domain\Communication\Support\CommunicationEndpoints;
use App\Models\Api\markting\UserCredit;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Models\WaConversationState;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CommunicationServiceImpl implements CommunicationService
{
    public function __construct(
        private readonly IdempotencyService $idempotencyService,
        private readonly CreditService $creditService,
        private readonly MessageDispatcher $messageDispatcher,
    ) {}

    public function recordInboundMessage(
        int $userId,
        string $externalPartyIdentifier,
        string $content,
        string $channel = 'whatsapp',
        ?string $providerMessageId = null,
        array $meta = []
    ): ?Message {
        try {
            if ($userId <= 0 || trim($externalPartyIdentifier) === '' || trim($content) === '') {
                Log::info('CommunicationService::recordInboundMessage skipped: missing required values', [
                    'userId' => $userId,
                    'has_identifier' => trim($externalPartyIdentifier) !== '',
                    'has_content' => trim($content) !== '',
                ]);
                return null;
            }

            $user = User::find($userId);
            if (! $user) {
                Log::info('CommunicationService::recordInboundMessage skipped: tenant owner mapping unresolved', [
                    'userId' => $userId,
                ]);
                return null;
            }

            $channel = strtolower(trim($channel));
            $normalizedIdentifier = $this->normalizeExternalPartyIdentifier($externalPartyIdentifier);

            if ($providerMessageId !== null && $providerMessageId !== '') {
                $existing = Message::where('provider_message_id', $providerMessageId)
                    ->where('user_id', $userId)
                    ->first();
                if ($existing) {
                    return $existing;
                }
            }

            $message = null;
            $conversationNewlyCreated = false;
            $conversation = null;

            DB::transaction(function () use (
                $userId,
                $channel,
                $normalizedIdentifier,
                $content,
                $providerMessageId,
                $meta,
                &$message,
                &$conversationNewlyCreated,
                &$conversation
            ) {
                $conversation = Conversation::firstOrCreate(
                    [
                        'user_id' => $userId,
                        'channel' => $channel,
                        'external_party_identifier' => $normalizedIdentifier,
                    ],
                    ['last_message_at' => now()]
                );
                $conversationNewlyCreated = $conversation->wasRecentlyCreated;

                $message = Message::create([
                    'conversation_id' => $conversation->id,
                    'user_id' => $userId,
                    'content' => $content,
                    'direction' => 'inbound',
                    'status' => 'received',
                    'provider_message_id' => $providerMessageId,
                    'meta' => $meta,
                ]);

                $conversation->update(['last_message_at' => now()]);

                // Always create/update WaConversationState for WhatsApp so the conversation appears in api/v1/whatsapp/conversations
                $waNumberId = $meta['wa_number_id'] ?? null;
                if ($channel === 'whatsapp') {
                    $state = WaConversationState::firstOrCreate(
                        ['conversation_id' => $conversation->id],
                        [
                            'user_id' => $userId,
                            'wa_number_id' => $waNumberId,
                            'status' => 'active',
                            'is_starred' => false,
                            'unread_count' => 0,
                        ]
                    );
                    $preview = is_scalar($content) ? (string) $content : (string) json_encode($content ?? '');
                    $state->increment('unread_count');
                    $state->update([
                        'last_message_preview' => \Illuminate\Support\Str::limit($preview, 500),
                        'last_message_time' => now(),
                    ]);
                }

                $messageForEvent = $message;
                $conversationForEvent = $conversation;
                $wasNewConversation = $conversationNewlyCreated;

                $dispatchInboundEvents = function () use ($messageForEvent, $wasNewConversation, $conversationForEvent) {
                    if ($wasNewConversation) {
                        ConversationOpened::dispatch(
                            (int) $conversationForEvent->id,
                            (int) $messageForEvent->id,
                            (int) $messageForEvent->user_id,
                            (string) $conversationForEvent->channel,
                            $messageForEvent->created_at?->toIso8601String() ?? now()->toIso8601String()
                        );
                    }
                    $metaArray = is_array($messageForEvent->meta) ? $messageForEvent->meta : [];
                    MessageReceivedEvent::dispatch(
                        (int) $messageForEvent->id,
                        (int) $messageForEvent->conversation_id,
                        (int) $messageForEvent->user_id,
                        (string) $conversationForEvent->channel,
                        (string) $messageForEvent->direction,
                        (string) $messageForEvent->content,
                        $metaArray,
                        $messageForEvent->created_at?->toIso8601String() ?? now()->toIso8601String()
                    );
                };
                try {
                    DB::afterCommit($dispatchInboundEvents);
                } catch (\Throwable $e) {
                    try {
                        $dispatchInboundEvents();
                    } catch (\Throwable $e2) {
                        Log::warning('CommunicationService::recordInboundMessage afterCommit fallback dispatch failed', ['error' => $e2->getMessage()]);
                    }
                }
            });

            return $message;
        } catch (\Throwable $e) {
            Log::error('CommunicationService::recordInboundMessage exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return null;
        }
    }

    public function sendMessage(SendMessageDto $dto, string $idempotencyKey): Message
    {
        $channel = strtolower(trim($dto->channel));
        if ($channel !== 'whatsapp') {
            throw new UnsupportedChannelException($dto->channel);
        }

        $conversation = Conversation::where('user_id', $dto->userId)
            ->find($dto->conversationId);

        if (! $conversation) {
            throw new ConversationNotFoundException($dto->conversationId, $dto->userId);
        }

        $meta = [];
        if ($dto->waNumberId !== null) {
            $waNumber = \App\Models\WaNumber::where('id', $dto->waNumberId)->where('user_id', $dto->userId)->first();
            if (! $waNumber) {
                throw new WaNumberNotFoundException($dto->waNumberId, $dto->userId);
            }
            if (strtolower((string) $waNumber->status) !== 'active') {
                throw new WaNumberNotActiveException($dto->waNumberId);
            }
            $meta['wa_number_id'] = $dto->waNumberId;
        }

        $endpoint = $dto->endpointSignature ?? CommunicationEndpoints::SEND_MESSAGE;
        $payload = [
            'channel' => $dto->channel,
            'content' => $dto->content,
            'conversation_id' => $dto->conversationId,
        ];
        if ($dto->waNumberId !== null) {
            $payload['wa_number_id'] = $dto->waNumberId;
        }
        if ($dto->templateId !== null) {
            $payload['template_id'] = $dto->templateId;
            $payload['variables'] = $dto->variables ?? [];
        }

        $result = $this->idempotencyService->start($dto->userId, $idempotencyKey, $endpoint, $payload);

        if ($result->mode === IdempotencyStartResult::MODE_REPLAY && $result->message !== null) {
            return $result->message;
        }

        if ($result->mode === IdempotencyStartResult::MODE_CONFLICT && $result->reason !== null) {
            throw new IdempotencyConflictException($result->reason);
        }

        $cost = UserCredit::getCostForMessageType('whatsapp');
        if (!$this->creditService->hasSufficientCredits($dto->userId, $cost)) {
            throw new InsufficientCreditsException($dto->userId, $cost);
        }

        $idempotencyRow = $result->row;
        $referenceType = 'communication_message';
        $referenceId = (string) $idempotencyRow->id;

        $message = null;
        DB::transaction(function () use ($dto, $cost, $referenceType, $referenceId, $conversation, $meta, &$message) {
            $this->creditService->deduct($dto->userId, $cost, $referenceType, $referenceId);

            $message = Message::create([
                'conversation_id' => $conversation->id,
                'user_id' => $dto->userId,
                'content' => $dto->content,
                'direction' => 'outbound',
                'status' => 'queued',
                'provider_message_id' => null,
                'meta' => $meta,
            ]);
        });

        if ($message === null) {
            throw new \RuntimeException('Message creation failed: no message returned.');
        }

        try {
            $this->messageDispatcher->dispatch($message);
        } catch (\Throwable $e) {
            DB::transaction(function () use ($dto, $cost, $referenceType, $referenceId, $idempotencyRow, $e) {
                $this->creditService->refund($dto->userId, $cost, $referenceType, $referenceId);
                $this->idempotencyService->fail($idempotencyRow, $e->getMessage());
            });
            throw $e;
        }

        DB::transaction(function () use ($idempotencyRow, $message) {
            $this->idempotencyService->complete($idempotencyRow, (int) $message->id);
            $messageForEvent = $message->fresh();
            if ($messageForEvent && $messageForEvent->conversation) {
                $conversation = $messageForEvent->conversation;
                $metaArray = is_array($messageForEvent->meta) ? $messageForEvent->meta : [];
                DB::afterCommit(function () use ($messageForEvent, $conversation, $metaArray) {
                    MessageSentEvent::dispatch(
                        (int) $messageForEvent->id,
                        (int) $messageForEvent->conversation_id,
                        (int) $messageForEvent->user_id,
                        (string) $conversation->channel,
                        (string) $messageForEvent->direction,
                        (string) $messageForEvent->content,
                        $metaArray,
                        $messageForEvent->created_at?->toIso8601String() ?? now()->toIso8601String()
                    );
                });
            }
        });

        $message->refresh();

        return $message;
    }

    private function normalizeExternalPartyIdentifier(string $value): string
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
