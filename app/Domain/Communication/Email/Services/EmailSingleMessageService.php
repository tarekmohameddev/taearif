<?php

namespace App\Domain\Communication\Email\Services;

use App\Domain\Communication\Contracts\CreditService;
use App\Domain\Communication\DTOs\IdempotencyStartResult;
use App\Domain\Communication\Exceptions\IdempotencyConflictException;
use App\Domain\Communication\Exceptions\InsufficientCreditsException;
use App\Domain\Communication\Email\Contracts\EmailDispatcher;
use App\Domain\Communication\Email\Support\EmailEndpoints;
use App\Domain\Communication\Services\IdempotencyService;
use App\Models\Api\marketing\UserCredit;
use App\Models\EmailMessageLog;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

class EmailSingleMessageService
{
    public function __construct(
        private readonly IdempotencyService $idempotencyService,
        private readonly CreditService $creditService,
        private readonly EmailDispatcher $emailDispatcher,
        private readonly EmailRecipientResolverService $recipientResolver
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function send(
        int $userId,
        string $idempotencyKey,
        string $recipientEmail,
        string $subject,
        string $bodyHtml,
        ?string $bodyText = null
    ): array {
        $email = $this->recipientResolver->normalizeEmail($recipientEmail);
        if ($email === null) {
            throw new InvalidArgumentException('Invalid recipient_email.');
        }

        $trimmedSubject = trim($subject);
        if ($trimmedSubject === '') {
            throw new InvalidArgumentException('subject is required.');
        }

        $trimmedBodyHtml = trim($bodyHtml);
        if ($trimmedBodyHtml === '') {
            throw new InvalidArgumentException('body_html is required.');
        }

        $payload = [
            'recipient_email' => $email,
            'subject' => $trimmedSubject,
            'body_html' => $trimmedBodyHtml,
            'body_text' => $bodyText,
        ];

        $start = $this->idempotencyService->start($userId, $idempotencyKey, EmailEndpoints::SEND_SINGLE, $payload);
        if ($start->mode === IdempotencyStartResult::MODE_REPLAY && is_array($start->responsePayload)) {
            return $start->responsePayload;
        }
        if ($start->mode === IdempotencyStartResult::MODE_CONFLICT && $start->reason !== null) {
            throw new IdempotencyConflictException($start->reason);
        }

        $cost = UserCredit::getCostForMessageType('email');
        if (!$this->creditService->hasSufficientCredits($userId, $cost)) {
            throw new InsufficientCreditsException($userId, $cost);
        }

        $log = null;
        $row = $start->row;
        $referenceId = (string) $row->id;

        DB::transaction(function () use ($userId, $email, $trimmedSubject, $trimmedBodyHtml, $bodyText, $cost, $referenceId, &$log): void {
            $this->creditService->deduct($userId, $cost, 'email_single', $referenceId);

            $log = EmailMessageLog::create([
                'user_id' => $userId,
                'campaign_id' => null,
                'customer_id' => null,
                'recipient_email' => $email,
                'recipient_name' => null,
                'subject' => $trimmedSubject,
                'body_html' => $trimmedBodyHtml,
                'body_text' => $bodyText,
                'status' => 'pending',
                'meta' => ['source' => 'email_single_api'],
            ]);
        });

        $this->emailDispatcher->dispatchSingleLog((int) $log->id);
        $log->refresh();

        if ($log->status !== 'sent') {
            $this->idempotencyService->fail($row, 'email_provider_failed');
            throw new RuntimeException('Email provider failed to send message.');
        }

        $responsePayload = [
            'log_id' => (int) $log->id,
            'status' => (string) $log->status,
            'recipient_email' => (string) $log->recipient_email,
            'gateway_message_id' => $log->gateway_message_id,
            'provider' => $log->provider,
            'sent_at' => $log->sent_at?->toISOString(),
        ];

        $this->idempotencyService->completeWithPayload($row, 'email_single', (string) $log->id, $responsePayload);

        return $responsePayload;
    }
}

