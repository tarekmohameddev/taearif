<?php

namespace App\Domain\Communication\Sms\Services;

use App\Domain\Communication\Contracts\CreditService;
use App\Domain\Communication\DTOs\IdempotencyStartResult;
use App\Domain\Communication\Exceptions\IdempotencyConflictException;
use App\Domain\Communication\Exceptions\InsufficientCreditsException;
use App\Domain\Communication\Sms\Contracts\SmsDispatcher;
use App\Domain\Communication\Sms\Support\SmsEndpoints;
use App\Domain\Communication\Services\IdempotencyService;
use App\Models\Api\marketing\UserCredit;
use App\Models\SmsMessageLog;
use InvalidArgumentException;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class SmsSingleMessageService
{
    public function __construct(
        private readonly IdempotencyService $idempotencyService,
        private readonly CreditService $creditService,
        private readonly SmsDispatcher $smsDispatcher,
        private readonly SmsRecipientResolverService $recipientResolver
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function send(int $userId, string $idempotencyKey, string $recipientPhone, string $content): array
    {
        $phone = $this->recipientResolver->normalizePhone($recipientPhone);
        if ($phone === null) {
            throw new InvalidArgumentException('Invalid recipient_phone.');
        }

        $trimmed = trim($content);
        if ($trimmed === '') {
            throw new InvalidArgumentException('content is required.');
        }

        $payload = [
            'recipient_phone' => $phone,
            'content' => $trimmed,
        ];

        $start = $this->idempotencyService->start($userId, $idempotencyKey, SmsEndpoints::SEND_SINGLE, $payload);
        if ($start->mode === IdempotencyStartResult::MODE_REPLAY && is_array($start->responsePayload)) {
            return $start->responsePayload;
        }
        if ($start->mode === IdempotencyStartResult::MODE_CONFLICT && $start->reason !== null) {
            throw new IdempotencyConflictException($start->reason);
        }

        $cost = UserCredit::getCostForMessageType('sms');
        if (!$this->creditService->hasSufficientCredits($userId, $cost)) {
            throw new InsufficientCreditsException($userId, $cost);
        }

        $log = null;
        $row = $start->row;
        $referenceId = (string) $row->id;

        DB::transaction(function () use ($userId, $phone, $trimmed, $cost, $referenceId, &$log): void {
            $this->creditService->deduct($userId, $cost, 'sms_single', $referenceId);

            $log = SmsMessageLog::create([
                'user_id' => $userId,
                'campaign_id' => null,
                'customer_id' => null,
                'recipient_phone' => $phone,
                'recipient_name' => null,
                'message' => $trimmed,
                'status' => 'pending',
                'meta' => ['source' => 'sms_single_api'],
            ]);
        });

        $this->smsDispatcher->dispatchSingleLog((int) $log->id);
        $log->refresh();

        if ($log->status !== 'sent') {
            $this->idempotencyService->fail($row, 'sms_provider_failed');
            throw new RuntimeException('SMS provider failed to send message.');
        }

        $responsePayload = [
            'log_id' => (int) $log->id,
            'status' => (string) $log->status,
            'recipient_phone' => (string) $log->recipient_phone,
            'gateway_message_id' => $log->gateway_message_id,
            'provider' => $log->provider,
            'sent_at' => $log->sent_at?->toISOString(),
        ];

        $this->idempotencyService->completeWithPayload($row, 'sms_single', (string) $log->id, $responsePayload);

        return $responsePayload;
    }
}

