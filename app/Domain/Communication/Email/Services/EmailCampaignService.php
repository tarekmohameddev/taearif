<?php

namespace App\Domain\Communication\Email\Services;

use App\Domain\Communication\Contracts\CreditService;
use App\Domain\Communication\DTOs\IdempotencyStartResult;
use App\Domain\Communication\Exceptions\IdempotencyConflictException;
use App\Domain\Communication\Exceptions\InsufficientCreditsException;
use App\Domain\Communication\Email\Support\EmailEndpoints;
use App\Domain\Communication\Services\IdempotencyService;
use App\Jobs\DispatchEmailCampaignJob;
use App\Models\Api\marketing\UserCredit;
use App\Models\EmailCampaign;
use App\Models\EmailMessageLog;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

class EmailCampaignService
{
    public function __construct(
        private readonly EmailRecipientResolverService $recipientResolver,
        private readonly IdempotencyService $idempotencyService,
        private readonly CreditService $creditService
    ) {}

    public function listForUser(int $userId, array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = EmailCampaign::query()
            ->where('user_id', $userId)
            ->with([
                'template:id,name',
                'creator:id,first_name,last_name,account_type,tenant_id',
                'creator.basic_setting:id,user_id,company_name',
                'user:id',
                'user.basic_setting:id,user_id,company_name',
            ]);

        if (!empty($filters['status'])) {
            $query->where('status', (string) $filters['status']);
        }

        return $query->orderByDesc('id')->paginate(min(max($perPage, 1), 100));
    }

    public function findForUser(int $userId, int $campaignId): ?EmailCampaign
    {
        return EmailCampaign::query()
            ->where('user_id', $userId)
            ->with([
                'template:id,name',
                'creator:id,first_name,last_name,account_type,tenant_id',
                'creator.basic_setting:id,user_id,company_name',
                'user:id',
                'user.basic_setting:id,user_id,company_name',
            ])
            ->find($campaignId);
    }

    public function create(int $userId, int $createdByUserId, array $data): EmailCampaign
    {
        $data['user_id'] = $userId;
        $data['created_by_user_id'] = $createdByUserId;
        $data['status'] = $data['status'] ?? 'draft';

        $campaign = EmailCampaign::create($data);
        $campaign->load([
            'template:id,name',
            'creator:id,first_name,last_name,account_type,tenant_id',
            'creator.basic_setting:id,user_id,company_name',
            'user:id',
            'user.basic_setting:id,user_id,company_name',
        ]);

        return $campaign;
    }

    public function update(EmailCampaign $campaign, array $data): EmailCampaign
    {
        if (!in_array($campaign->status, ['draft', 'scheduled', 'paused'], true)) {
            throw new InvalidArgumentException('Only draft, scheduled or paused campaigns can be updated.');
        }

        $campaign->update($data);

        return $campaign->refresh();
    }

    public function delete(EmailCampaign $campaign): void
    {
        if (!in_array($campaign->status, ['draft', 'scheduled'], true)) {
            throw new InvalidArgumentException('Only draft or scheduled campaigns can be deleted.');
        }

        $campaign->delete();
    }

    /**
     * @param array<int, mixed> $customerIds
     * @param array<int, mixed> $manualEmails
     * @return array<string, mixed>
     */
    public function sendCampaign(
        int $userId,
        int $campaignId,
        string $idempotencyKey,
        array $customerIds = [],
        array $manualEmails = []
    ): array {
        $queue = (string) config('communication.email.queue', 'communication');
        $dispatchNow = true;
        $delayUntil = null;
        $responsePayload = [];

        $payload = [
            'campaign_id' => $campaignId,
            'customer_ids' => array_values(array_map('intval', $customerIds)),
            'manual_emails' => array_values(array_map('strval', $manualEmails)),
        ];

        DB::transaction(function () use (
            $userId,
            $campaignId,
            $idempotencyKey,
            $payload,
            $customerIds,
            $manualEmails,
            &$dispatchNow,
            &$delayUntil,
            &$responsePayload
        ): void {
            $start = $this->idempotencyService->start($userId, $idempotencyKey, EmailEndpoints::SEND_CAMPAIGN, $payload);
            if ($start->mode === IdempotencyStartResult::MODE_REPLAY && is_array($start->responsePayload)) {
                $responsePayload = $start->responsePayload;
                $dispatchNow = false;
                return;
            }
            if ($start->mode === IdempotencyStartResult::MODE_CONFLICT && $start->reason !== null) {
                throw new IdempotencyConflictException($start->reason);
            }

            $campaign = EmailCampaign::query()
                ->where('id', $campaignId)
                ->where('user_id', $userId)
                ->lockForUpdate()
                ->first();

            if (!$campaign) {
                throw (new ModelNotFoundException())->setModel(EmailCampaign::class, [$campaignId]);
            }

            if (!in_array($campaign->status, ['draft', 'scheduled'], true)) {
                throw new InvalidArgumentException('Campaign status is not sendable.');
            }

            if ($campaign->dispatch_reference !== null && !in_array($campaign->status, ['sent', 'failed', 'cancelled'], true)) {
                throw new IdempotencyConflictException(IdempotencyStartResult::REASON_IN_PROGRESS);
            }

            $recipients = $this->recipientResolver->resolve($userId, $customerIds, $manualEmails);
            if (count($recipients) === 0) {
                throw new InvalidArgumentException('No valid email addresses from the given customer_ids or manual_emails.');
            }

            $creditsPerMessage = UserCredit::getCostForMessageType('email');
            $requiredCredits = count($recipients) * $creditsPerMessage;
            if (!$this->creditService->hasSufficientCredits($userId, $requiredCredits)) {
                throw new InsufficientCreditsException($userId, $requiredCredits);
            }

            $this->creditService->reserve($userId, $requiredCredits, 'email_campaign', (string) $campaignId);

            $dispatchReference = (string) Str::uuid();
            $now = now();

            $rows = [];
            foreach ($recipients as $recipient) {
                $rows[] = [
                    'user_id' => $userId,
                    'campaign_id' => $campaign->id,
                    'customer_id' => $recipient['customer_id'],
                    'recipient_email' => $recipient['email'],
                    'recipient_name' => $recipient['name'],
                    'subject' => (string) $campaign->subject,
                    'body_html' => (string) $campaign->body_html,
                    'body_text' => $campaign->body_text,
                    'status' => 'pending',
                    'meta' => json_encode(['dispatch_reference' => $dispatchReference]),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            foreach (array_chunk($rows, 1000) as $chunk) {
                EmailMessageLog::insert($chunk);
            }

            $recipientCount = count($rows);
            $meta = array_merge($campaign->meta ?? [], [
                'send_customer_ids' => array_values(array_map('intval', $customerIds)),
                'send_manual_emails' => array_values(array_map('strval', $manualEmails)),
            ]);
            $scheduledAt = $campaign->scheduled_at;
            if ($scheduledAt !== null && $scheduledAt->isFuture()) {
                $campaign->update([
                    'status' => 'scheduled',
                    'dispatch_reference' => $dispatchReference,
                    'recipient_count' => $recipientCount,
                    'reserved_credits' => $recipientCount,
                    'meta' => $meta,
                ]);
                $dispatchNow = false;
                $delayUntil = $scheduledAt;
            } else {
                $campaign->update([
                    'status' => 'in_progress',
                    'dispatch_reference' => $dispatchReference,
                    'recipient_count' => $recipientCount,
                    'reserved_credits' => $recipientCount,
                    'meta' => $meta,
                ]);
                $dispatchNow = true;
            }

            $campaign->refresh();
            $responsePayload = [
                'campaign_id' => (int) $campaign->id,
                'status' => (string) $campaign->status,
                'recipient_count' => (int) $campaign->recipient_count,
                'dispatch_reference' => (string) $campaign->dispatch_reference,
                'queued_at' => now()->toISOString(),
                'scheduled_at' => $campaign->scheduled_at?->toISOString(),
            ];

            $this->idempotencyService->completeWithPayload(
                $start->row,
                'email_campaign',
                (string) $campaign->id,
                $responsePayload
            );
        });

        if (empty($responsePayload)) {
            throw new RuntimeException('Failed to prepare campaign dispatch.');
        }

        if (isset($responsePayload['dispatch_reference']) && $dispatchNow) {
            DispatchEmailCampaignJob::dispatch((int) $responsePayload['campaign_id'])
                ->onQueue($queue);
        } elseif (isset($responsePayload['dispatch_reference']) && $delayUntil !== null) {
            DispatchEmailCampaignJob::dispatch((int) $responsePayload['campaign_id'])
                ->onQueue($queue)
                ->delay($delayUntil);
        }

        return $responsePayload;
    }

    /**
     * @return array<string, mixed>
     */
    public function pause(int $userId, int $campaignId): array
    {
        $campaign = EmailCampaign::query()
            ->where('id', $campaignId)
            ->where('user_id', $userId)
            ->lockForUpdate()
            ->first();

        if (!$campaign) {
            throw (new ModelNotFoundException())->setModel(EmailCampaign::class, [$campaignId]);
        }

        if (!in_array($campaign->status, ['in_progress', 'scheduled'], true)) {
            throw new InvalidArgumentException('Only in-progress or scheduled campaigns can be paused.');
        }

        $pausedCount = 0;

        DB::transaction(function () use ($userId, $campaign, &$pausedCount): void {
            $pausedCount = EmailMessageLog::query()
                ->where('campaign_id', $campaign->id)
                ->where('status', 'pending')
                ->update(['status' => 'paused']);

            if ($pausedCount > 0) {
                $this->creditService->releaseReserved(
                    $userId,
                    $pausedCount,
                    'email_campaign_pause',
                    (string) $campaign->id
                );
            }

            $newReserved = max(0, (int) $campaign->reserved_credits - $pausedCount);
            $campaign->update([
                'status' => 'paused',
                'reserved_credits' => $newReserved,
            ]);
        });

        $userCredit = UserCredit::where('user_id', $userId)->first();
        $balanceAfterRelease = $userCredit ? $userCredit->available_credits : 0;

        return [
            'campaign_id' => (int) $campaign->id,
            'status' => 'paused',
            'sent_count' => (int) $campaign->sent_count,
            'paused_count' => $pausedCount,
            'credit_info' => [
                'consumed' => (int) $campaign->sent_count,
                'released' => $pausedCount,
                'balance_after_release' => $balanceAfterRelease,
                'note' => $pausedCount > 0
                    ? "Campaign paused. {$pausedCount} reserved credits returned to your balance."
                    : 'Campaign paused. No pending messages to release.',
            ],
        ];
    }

    /**
     * @param array<int, mixed> $customerIds
     * @param array<int, mixed> $manualEmails
     * @return array<string, mixed>
     */
    public function resume(
        int $userId,
        int $campaignId,
        string $idempotencyKey,
        string $mode,
        array $customerIds = [],
        array $manualEmails = []
    ): array {
        $queue = (string) config('communication.email.queue', 'communication');
        $responsePayload = [];

        $payload = [
            'campaign_id' => $campaignId,
            'mode' => $mode,
            'customer_ids' => array_values(array_map('intval', $customerIds)),
            'manual_emails' => array_values(array_map('strval', $manualEmails)),
        ];

        DB::transaction(function () use (
            $userId,
            $campaignId,
            $idempotencyKey,
            $payload,
            $mode,
            $customerIds,
            $manualEmails,
            $queue,
            &$responsePayload
        ): void {
            $start = $this->idempotencyService->start($userId, $idempotencyKey, EmailEndpoints::RESUME_CAMPAIGN, $payload);
            if ($start->mode === IdempotencyStartResult::MODE_REPLAY && is_array($start->responsePayload)) {
                $responsePayload = $start->responsePayload;
                return;
            }
            if ($start->mode === IdempotencyStartResult::MODE_CONFLICT && $start->reason !== null) {
                throw new IdempotencyConflictException($start->reason);
            }

            $campaign = EmailCampaign::query()
                ->where('id', $campaignId)
                ->where('user_id', $userId)
                ->lockForUpdate()
                ->first();

            if (!$campaign) {
                throw (new ModelNotFoundException())->setModel(EmailCampaign::class, [$campaignId]);
            }

            if ($campaign->status !== 'paused') {
                throw new InvalidArgumentException('Only paused campaigns can be resumed.');
            }

            $creditsPerMessage = UserCredit::getCostForMessageType('email');

            if ($mode === 'continue') {
                $pausedLogs = EmailMessageLog::query()
                    ->where('campaign_id', $campaignId)
                    ->where('status', 'paused')
                    ->get();

                $count = $pausedLogs->count();
                if ($count === 0) {
                    throw new InvalidArgumentException('No paused recipients to continue.');
                }

                $requiredCredits = $count * $creditsPerMessage;
                if (!$this->creditService->hasSufficientCredits($userId, $requiredCredits)) {
                    throw new InsufficientCreditsException($userId, $requiredCredits);
                }

                $this->creditService->reserve($userId, $requiredCredits, 'email_campaign_resume', (string) $campaignId);

                $currentSubject = (string) $campaign->subject;
                $currentBodyHtml = (string) $campaign->body_html;
                $currentBodyText = $campaign->body_text;
                EmailMessageLog::query()
                    ->where('campaign_id', $campaignId)
                    ->where('status', 'paused')
                    ->update([
                        'status' => 'pending',
                        'subject' => $currentSubject,
                        'body_html' => $currentBodyHtml,
                        'body_text' => $currentBodyText,
                        'updated_at' => now(),
                    ]);

                $campaign->update([
                    'status' => 'in_progress',
                    'reserved_credits' => (int) $campaign->reserved_credits + $count,
                ]);

                $userCredit = UserCredit::where('user_id', $userId)->first();
                $balanceAfterReserve = $userCredit ? $userCredit->available_credits : 0;

                $responsePayload = [
                    'campaign_id' => (int) $campaign->id,
                    'status' => 'in_progress',
                    'mode' => 'continue',
                    'recipient_count' => $count,
                    'credit_info' => [
                        'reserved' => $requiredCredits,
                        'balance_after_reserve' => $balanceAfterReserve,
                        'note' => "{$count} credits reserved to send to remaining {$count} recipients.",
                    ],
                ];
            } else {
                EmailMessageLog::query()
                    ->where('campaign_id', $campaignId)
                    ->where('status', 'paused')
                    ->update(['status' => 'cancelled']);

                $meta = $campaign->meta ?? [];
                $resolveCustomerIds = !empty($customerIds) ? $customerIds : ($meta['send_customer_ids'] ?? []);
                $resolveManualEmails = !empty($manualEmails) ? $manualEmails : ($meta['send_manual_emails'] ?? []);

                if (empty($resolveCustomerIds) && empty($resolveManualEmails)) {
                    throw new InvalidArgumentException('No recipients to restart. Provide customer_ids or manual_emails, or ensure campaign was sent with recipients.');
                }

                $recipients = $this->recipientResolver->resolve($userId, $resolveCustomerIds, $resolveManualEmails);
                if (count($recipients) === 0) {
                    throw new InvalidArgumentException('No valid email addresses from the given customer_ids or manual_emails.');
                }

                $recipientCount = count($recipients);
                $requiredCredits = $recipientCount * $creditsPerMessage;
                if (!$this->creditService->hasSufficientCredits($userId, $requiredCredits)) {
                    throw new InsufficientCreditsException($userId, $requiredCredits);
                }

                $this->creditService->reserve($userId, $requiredCredits, 'email_campaign_restart', (string) $campaignId);

                $dispatchReference = (string) Str::uuid();
                $now = now();
                $currentSubject = (string) $campaign->subject;
                $currentBodyHtml = (string) $campaign->body_html;
                $currentBodyText = $campaign->body_text;

                $rows = [];
                foreach ($recipients as $recipient) {
                    $rows[] = [
                        'user_id' => $userId,
                        'campaign_id' => $campaign->id,
                        'customer_id' => $recipient['customer_id'],
                        'recipient_email' => $recipient['email'],
                        'recipient_name' => $recipient['name'],
                        'subject' => $currentSubject,
                        'body_html' => $currentBodyHtml,
                        'body_text' => $currentBodyText,
                        'status' => 'pending',
                        'meta' => json_encode(['dispatch_reference' => $dispatchReference]),
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }

                foreach (array_chunk($rows, 1000) as $chunk) {
                    EmailMessageLog::insert($chunk);
                }

                $newMeta = array_merge($meta, [
                    'send_customer_ids' => $resolveCustomerIds,
                    'send_manual_emails' => $resolveManualEmails,
                ]);

                $campaign->update([
                    'status' => 'in_progress',
                    'dispatch_reference' => $dispatchReference,
                    'recipient_count' => $recipientCount,
                    'reserved_credits' => $recipientCount,
                    'sent_count' => 0,
                    'failed_count' => 0,
                    'meta' => $newMeta,
                ]);

                $userCredit = UserCredit::where('user_id', $userId)->first();
                $balanceAfterReserve = $userCredit ? $userCredit->available_credits : 0;

                $responsePayload = [
                    'campaign_id' => (int) $campaign->id,
                    'status' => 'in_progress',
                    'mode' => 'restart',
                    'recipient_count' => $recipientCount,
                    'credit_info' => [
                        'reserved' => $requiredCredits,
                        'balance_after_reserve' => $balanceAfterReserve,
                        'note' => "{$recipientCount} credits reserved to send to all {$recipientCount} recipients from the beginning.",
                    ],
                ];
            }

            $this->idempotencyService->completeWithPayload(
                $start->row,
                'email_campaign_resume',
                (string) $campaignId,
                $responsePayload
            );
        });

        if (empty($responsePayload)) {
            throw new RuntimeException('Failed to prepare campaign resume.');
        }

        $campaign = EmailCampaign::query()->where('id', $campaignId)->where('user_id', $userId)->first();
        if ($campaign) {
            DispatchEmailCampaignJob::dispatch((int) $campaign->id)->onQueue($queue);
        }

        return $responsePayload;
    }
}

