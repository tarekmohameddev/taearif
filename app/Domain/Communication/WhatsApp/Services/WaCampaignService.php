<?php

namespace App\Domain\Communication\WhatsApp\Services;

use App\Domain\Communication\Contracts\CreditService;
use App\Domain\Communication\DTOs\IdempotencyStartResult;
use App\Domain\Communication\Exceptions\IdempotencyConflictException;
use App\Domain\Communication\Exceptions\InsufficientCreditsException;
use App\Domain\Communication\WhatsApp\Support\WaEndpoints;
use App\Domain\Communication\Services\IdempotencyService;
use App\Jobs\DispatchWaCampaignJob;
use App\Models\Api\marketing\UserCredit;
use App\Models\WaCampaign;
use App\Models\WaMessageLog;
use App\Models\WaNumber;
use App\Models\WaTemplate;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

class WaCampaignService
{
    public function __construct(
        private readonly WaRecipientResolverService $recipientResolver,
        private readonly IdempotencyService $idempotencyService,
        private readonly CreditService $creditService,
        private readonly WhatsAppTemplateService $templateService
    ) {}

    public function listForUser(int $userId, array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = WaCampaign::query()
            ->where('user_id', $userId)
            ->with([
                'template:id,name',
                'waNumber:id,phone_number,name,status',
                'creator:id,first_name,last_name,account_type,tenant_id',
                'creator.basic_setting:id,user_id,company_name',
                'user:id',
                'user.basic_setting:id,user_id,company_name',
            ]);

        if (! empty($filters['status'])) {
            $query->where('status', (string) $filters['status']);
        }

        return $query->orderByDesc('id')->paginate(min(max($perPage, 1), 100));
    }

    public function findForUser(int $userId, int $campaignId): ?WaCampaign
    {
        return WaCampaign::query()
            ->where('user_id', $userId)
            ->with([
                'template:id,name',
                'waNumber:id,phone_number,name,status',
                'creator:id,first_name,last_name,account_type,tenant_id',
                'creator.basic_setting:id,user_id,company_name',
                'user:id',
                'user.basic_setting:id,user_id,company_name',
            ])
            ->find($campaignId);
    }

    /**
     * Enforce content XOR: exactly one of message or template_id must be set.
     */
    private function assertContentXor(?string $message, mixed $templateId): void
    {
        $hasMessage = $message !== null && trim($message) !== '';
        $hasTemplate = $templateId !== null && $templateId !== '';
        if (! $hasMessage && ! $hasTemplate) {
            throw new InvalidArgumentException('WA_CAMPAIGN_CONTENT_REQUIRED');
        }
        if ($hasMessage && $hasTemplate) {
            throw new InvalidArgumentException('WA_CAMPAIGN_CONTENT_CONFLICT');
        }
    }

    /**
     * Ensure wa_number_id is owned by userId and optionally active (DS-3).
     *
     * @throws InvalidArgumentException WA_NUMBER_NOT_FOUND (404) or WA_NUMBER_NOT_ACTIVE (422)
     */
    private function ensureWaNumberForCampaign(int $userId, int $waNumberId, bool $forSend): WaNumber
    {
        $waNumber = WaNumber::query()
            ->where('id', $waNumberId)
            ->where('user_id', $userId)
            ->first();

        if (! $waNumber) {
            throw new InvalidArgumentException('WA_NUMBER_NOT_FOUND');
        }

        $requireActive = (bool) config('communication.whatsapp.campaign.require_active_wa_number');
        if ($forSend && $requireActive && ! in_array($waNumber->status, ['active'], true)) {
            throw new InvalidArgumentException('WA_NUMBER_NOT_ACTIVE');
        }

        if ($forSend && ! $requireActive && ! in_array($waNumber->status, ['active'], true)) {
            // Log warning and allow; campaign meta will record override (done in sendCampaign)
        }

        return $waNumber;
    }

    /**
     * Get the effective message text for a campaign: rendered template or plain message.
     */
    private function getEffectiveMessage(WaCampaign $campaign): string
    {
        if ($campaign->template_id) {
            $template = WaTemplate::query()
                ->where('id', $campaign->template_id)
                ->where('user_id', $campaign->user_id)
                ->first();
            if (! $template) {
                throw new InvalidArgumentException('Template not found for campaign.');
            }
            $variables = $campaign->meta['variables'] ?? [];
            if (! is_array($variables)) {
                $variables = [];
            }
            return $this->templateService->renderContent($template, $variables);
        }
        $msg = $campaign->message;
        if ($msg === null || trim($msg) === '') {
            throw new InvalidArgumentException('WA_CAMPAIGN_CONTENT_REQUIRED');
        }
        return trim($msg);
    }

    private function getCurrentCreditsPerMessage(): int
    {
        return max(1, (int) UserCredit::getCostForMessageType('whatsapp'));
    }

    private function getCampaignCreditsPerMessage(WaCampaign $campaign): int
    {
        $meta = is_array($campaign->meta) ? $campaign->meta : [];
        if (isset($meta['credits_per_message']) && is_numeric($meta['credits_per_message'])) {
            return max(1, (int) $meta['credits_per_message']);
        }

        return $this->getCurrentCreditsPerMessage();
    }

    public function create(int $userId, int $createdByUserId, array $data): WaCampaign
    {
        $this->assertContentXor(
            isset($data['message']) ? (string) $data['message'] : null,
            $data['template_id'] ?? null
        );

        $this->ensureWaNumberForCampaign($userId, (int) $data['wa_number_id'], false);

        $data['user_id'] = $userId;
        $data['created_by_user_id'] = $createdByUserId;
        $data['status'] = $data['status'] ?? 'draft';

        $campaign = WaCampaign::create($data);
        $campaign->load([
            'template:id,name',
            'waNumber:id,phone_number,name,status',
            'creator:id,first_name,last_name,account_type,tenant_id',
            'creator.basic_setting:id,user_id,company_name',
            'user:id',
            'user.basic_setting:id,user_id,company_name',
        ]);

        return $campaign;
    }

    public function update(WaCampaign $campaign, array $data): WaCampaign
    {
        if (! in_array($campaign->status, ['draft', 'scheduled', 'paused'], true)) {
            throw new InvalidArgumentException('Only draft, scheduled or paused campaigns can be updated.');
        }

        $message = array_key_exists('message', $data) ? ($data['message'] ?? null) : $campaign->message;
        $templateId = array_key_exists('template_id', $data) ? ($data['template_id'] ?? null) : $campaign->template_id;
        $this->assertContentXor($message, $templateId);

        if (isset($data['wa_number_id'])) {
            $this->ensureWaNumberForCampaign((int) $campaign->user_id, (int) $data['wa_number_id'], false);
        }

        $campaign->update($data);

        return $campaign->refresh();
    }

    public function delete(WaCampaign $campaign): void
    {
        if (! in_array($campaign->status, ['draft', 'scheduled'], true)) {
            throw new InvalidArgumentException('Only draft or scheduled campaigns can be deleted.');
        }

        $campaign->delete();
    }

    /**
     * @param array<int, mixed> $customerIds
     * @param array<int, mixed> $manualPhones
     * @return array<string, mixed>
     */
    public function sendCampaign(
        int $userId,
        int $campaignId,
        string $idempotencyKey,
        array $customerIds = [],
        array $manualPhones = []
    ): array {
        $queue = (string) config('communication.whatsapp.queue');
        $dispatchNow = true;
        $delayUntil = null;
        $responsePayload = [];

        $payload = [
            'campaign_id' => $campaignId,
            'customer_ids' => array_values(array_map('intval', $customerIds)),
            'manual_phones' => array_values(array_map('strval', $manualPhones)),
        ];

        DB::transaction(function () use (
            $userId,
            $campaignId,
            $idempotencyKey,
            $payload,
            $customerIds,
            $manualPhones,
            &$dispatchNow,
            &$delayUntil,
            &$responsePayload
        ): void {
            $start = $this->idempotencyService->start($userId, $idempotencyKey, WaEndpoints::SEND_CAMPAIGN, $payload);
            if ($start->mode === IdempotencyStartResult::MODE_REPLAY && is_array($start->responsePayload)) {
                $responsePayload = $start->responsePayload;
                $dispatchNow = false;
                return;
            }
            if ($start->mode === IdempotencyStartResult::MODE_CONFLICT && $start->reason !== null) {
                throw new IdempotencyConflictException($start->reason);
            }

            $campaign = WaCampaign::query()
                ->where('id', $campaignId)
                ->where('user_id', $userId)
                ->lockForUpdate()
                ->first();

            if (! $campaign) {
                throw (new ModelNotFoundException())->setModel(WaCampaign::class, [$campaignId]);
            }

            if (! in_array($campaign->status, ['draft', 'scheduled'], true)) {
                throw new InvalidArgumentException('Campaign status is not sendable.');
            }

            if ($campaign->dispatch_reference !== null && ! in_array($campaign->status, ['sent', 'failed', 'cancelled'], true)) {
                throw new IdempotencyConflictException(IdempotencyStartResult::REASON_IN_PROGRESS);
            }

            $waNumber = $this->ensureWaNumberForCampaign($userId, (int) $campaign->wa_number_id, true);
            $requireActive = (bool) config('communication.whatsapp.campaign.require_active_wa_number');
            $meta = array_merge($campaign->meta ?? [], [
                'sender_policy' => [
                    'require_active' => $requireActive,
                    'evaluated_at' => now()->toISOString(),
                ],
            ]);
            if (! $requireActive && ! in_array($waNumber->status, ['active'], true)) {
                $meta['sender_policy_override'] = true;
            }
            $campaign->update(['meta' => $meta]);

            $recipients = $this->recipientResolver->resolve($userId, $customerIds, $manualPhones);
            if (count($recipients) === 0) {
                throw new InvalidArgumentException('No valid phone numbers from the given customer_ids or manual_phones. Ensure customer IDs exist and have a valid phone (8–16 digits), and that manual_phones are valid (8–16 digits).');
            }

            $creditsPerMessage = $this->getCurrentCreditsPerMessage();
            $requiredCredits = count($recipients) * $creditsPerMessage;
            if (! $this->creditService->hasSufficientCredits($userId, $requiredCredits)) {
                throw new InsufficientCreditsException($userId, $requiredCredits);
            }

            $this->creditService->reserve($userId, $requiredCredits, 'wa_campaign', (string) $campaignId);

            $messageText = $this->getEffectiveMessage($campaign);
            $dispatchReference = (string) Str::uuid();
            $now = now();
            $waNumberId = (int) $campaign->wa_number_id;

            $rows = [];
            foreach ($recipients as $recipient) {
                $rows[] = [
                    'user_id' => $userId,
                    'campaign_id' => $campaign->id,
                    'customer_id' => $recipient['customer_id'],
                    'wa_number_id' => $waNumberId,
                    'recipient_phone' => $recipient['phone'],
                    'recipient_name' => $recipient['name'],
                    'message' => $messageText,
                    'status' => 'pending',
                    'meta' => json_encode(['dispatch_reference' => $dispatchReference]),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            foreach (array_chunk($rows, 1000) as $chunk) {
                WaMessageLog::insert($chunk);
            }

            $recipientCount = count($rows);
            $meta = array_merge($campaign->meta ?? [], [
                'send_customer_ids' => array_values(array_map('intval', $customerIds)),
                'send_manual_phones' => array_values(array_map('strval', $manualPhones)),
                'credits_per_message' => $creditsPerMessage,
            ]);
            $scheduledAt = $campaign->scheduled_at;
            if ($scheduledAt !== null && $scheduledAt->isFuture()) {
                $campaign->update([
                    'status' => 'scheduled',
                    'dispatch_reference' => $dispatchReference,
                    'recipient_count' => $recipientCount,
                    'reserved_credits' => $requiredCredits,
                    'meta' => $meta,
                ]);
                $dispatchNow = false;
                $delayUntil = $scheduledAt;
            } else {
                $campaign->update([
                    'status' => 'in_progress',
                    'dispatch_reference' => $dispatchReference,
                    'recipient_count' => $recipientCount,
                    'reserved_credits' => $requiredCredits,
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
                'wa_campaign',
                (string) $campaign->id,
                $responsePayload
            );
        });

        if (empty($responsePayload)) {
            throw new RuntimeException('Failed to prepare campaign dispatch.');
        }

        if (isset($responsePayload['dispatch_reference']) && $dispatchNow) {
            DispatchWaCampaignJob::dispatch((int) $responsePayload['campaign_id'])
                ->onQueue($queue);
        } elseif (isset($responsePayload['dispatch_reference']) && $delayUntil !== null) {
            DispatchWaCampaignJob::dispatch((int) $responsePayload['campaign_id'])
                ->onQueue($queue)
                ->delay($delayUntil);
        }

        return $responsePayload;
    }

    /**
     * Pause an in-progress or scheduled campaign.
     *
     * @return array<string, mixed>
     */
    public function pause(int $userId, int $campaignId): array
    {
        $campaign = WaCampaign::query()
            ->where('id', $campaignId)
            ->where('user_id', $userId)
            ->lockForUpdate()
            ->first();

        if (! $campaign) {
            throw (new ModelNotFoundException())->setModel(WaCampaign::class, [$campaignId]);
        }

        if (! in_array($campaign->status, ['in_progress', 'scheduled'], true)) {
            throw new InvalidArgumentException('Only in-progress or scheduled campaigns can be paused.');
        }

        $pausedCount = 0;

        $creditsPerMessage = $this->getCampaignCreditsPerMessage($campaign);
        $releasedCredits = 0;

        DB::transaction(function () use ($userId, $campaign, $creditsPerMessage, &$pausedCount, &$releasedCredits): void {
            $pausedCount = WaMessageLog::query()
                ->where('campaign_id', $campaign->id)
                ->where('status', 'pending')
                ->update(['status' => 'paused']);

            $releasedCredits = $pausedCount * $creditsPerMessage;
            if ($pausedCount > 0) {
                $this->creditService->releaseReserved(
                    $userId,
                    $releasedCredits,
                    'wa_campaign_pause',
                    (string) $campaign->id
                );
            }

            $newReserved = max(0, (int) $campaign->reserved_credits - $releasedCredits);
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
                'consumed' => (int) $campaign->sent_count * $creditsPerMessage,
                'released' => $releasedCredits,
                'balance_after_release' => $balanceAfterRelease,
                'note' => $pausedCount > 0
                    ? "Campaign paused. {$releasedCredits} reserved credits returned to your balance."
                    : 'Campaign paused. No pending messages to release.',
            ],
        ];
    }

    /**
     * Resume a paused campaign (continue or restart).
     *
     * @param array<int, mixed> $customerIds
     * @param array<int, mixed> $manualPhones
     * @return array<string, mixed>
     */
    public function resume(
        int $userId,
        int $campaignId,
        string $idempotencyKey,
        string $mode,
        array $customerIds = [],
        array $manualPhones = []
    ): array {
        $queue = (string) config('communication.whatsapp.queue');
        $responsePayload = [];

        $payload = [
            'campaign_id' => $campaignId,
            'mode' => $mode,
            'customer_ids' => array_values(array_map('intval', $customerIds)),
            'manual_phones' => array_values(array_map('strval', $manualPhones)),
        ];

        DB::transaction(function () use (
            $userId,
            $campaignId,
            $idempotencyKey,
            $payload,
            $mode,
            $customerIds,
            $manualPhones,
            $queue,
            &$responsePayload
        ): void {
            $start = $this->idempotencyService->start($userId, $idempotencyKey, WaEndpoints::RESUME_CAMPAIGN, $payload);
            if ($start->mode === IdempotencyStartResult::MODE_REPLAY && is_array($start->responsePayload)) {
                $responsePayload = $start->responsePayload;
                return;
            }
            if ($start->mode === IdempotencyStartResult::MODE_CONFLICT && $start->reason !== null) {
                throw new IdempotencyConflictException($start->reason);
            }

            $campaign = WaCampaign::query()
                ->where('id', $campaignId)
                ->where('user_id', $userId)
                ->lockForUpdate()
                ->first();

            if (! $campaign) {
                throw (new ModelNotFoundException())->setModel(WaCampaign::class, [$campaignId]);
            }

            if ($campaign->status !== 'paused') {
                throw new InvalidArgumentException('Only paused campaigns can be resumed.');
            }

            $this->ensureWaNumberForCampaign($userId, (int) $campaign->wa_number_id, true);

            $creditsPerMessage = $this->getCampaignCreditsPerMessage($campaign);
            $currentMessage = $this->getEffectiveMessage($campaign);

            if ($mode === 'continue') {
                $pausedLogs = WaMessageLog::query()
                    ->where('campaign_id', $campaignId)
                    ->where('status', 'paused')
                    ->get();

                $count = $pausedLogs->count();
                if ($count === 0) {
                    throw new InvalidArgumentException('No paused recipients to continue.');
                }

                $requiredCredits = $count * $creditsPerMessage;
                if (! $this->creditService->hasSufficientCredits($userId, $requiredCredits)) {
                    throw new InsufficientCreditsException($userId, $requiredCredits);
                }

                $this->creditService->reserve($userId, $requiredCredits, 'wa_campaign_resume', (string) $campaignId);

                WaMessageLog::query()
                    ->where('campaign_id', $campaignId)
                    ->where('status', 'paused')
                    ->update([
                        'status' => 'pending',
                        'message' => $currentMessage,
                        'updated_at' => now(),
                    ]);

                $campaign->update([
                    'status' => 'in_progress',
                    'reserved_credits' => (int) $campaign->reserved_credits + $requiredCredits,
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
                        'note' => "{$requiredCredits} credits reserved to send to remaining {$count} recipients.",
                    ],
                ];
            } else {
                WaMessageLog::query()
                    ->where('campaign_id', $campaignId)
                    ->where('status', 'paused')
                    ->update(['status' => 'cancelled']);

                $meta = $campaign->meta ?? [];
                $resolveCustomerIds = ! empty($customerIds) ? $customerIds : ($meta['send_customer_ids'] ?? []);
                $resolveManualPhones = ! empty($manualPhones) ? $manualPhones : ($meta['send_manual_phones'] ?? []);

                if (empty($resolveCustomerIds) && empty($resolveManualPhones)) {
                    throw new InvalidArgumentException('No recipients to restart. Provide customer_ids or manual_phones, or ensure campaign was sent with recipients.');
                }

                $recipients = $this->recipientResolver->resolve($userId, $resolveCustomerIds, $resolveManualPhones);
                if (count($recipients) === 0) {
                    throw new InvalidArgumentException('No valid phone numbers from the given customer_ids or manual_phones.');
                }

                $recipientCount = count($recipients);
                $requiredCredits = $recipientCount * $creditsPerMessage;
                if (! $this->creditService->hasSufficientCredits($userId, $requiredCredits)) {
                    throw new InsufficientCreditsException($userId, $requiredCredits);
                }

                $this->creditService->reserve($userId, $requiredCredits, 'wa_campaign_restart', (string) $campaignId);

                $dispatchReference = (string) Str::uuid();
                $now = now();
                $waNumberId = (int) $campaign->wa_number_id;

                $rows = [];
                foreach ($recipients as $recipient) {
                    $rows[] = [
                        'user_id' => $userId,
                        'campaign_id' => $campaign->id,
                        'customer_id' => $recipient['customer_id'],
                        'wa_number_id' => $waNumberId,
                        'recipient_phone' => $recipient['phone'],
                        'recipient_name' => $recipient['name'],
                        'message' => $currentMessage,
                        'status' => 'pending',
                        'meta' => json_encode(['dispatch_reference' => $dispatchReference]),
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }

                foreach (array_chunk($rows, 1000) as $chunk) {
                    WaMessageLog::insert($chunk);
                }

                $newMeta = array_merge($meta, [
                    'send_customer_ids' => $resolveCustomerIds,
                    'send_manual_phones' => $resolveManualPhones,
                    'credits_per_message' => $creditsPerMessage,
                ]);

                $campaign->update([
                    'status' => 'in_progress',
                    'dispatch_reference' => $dispatchReference,
                    'recipient_count' => $recipientCount,
                    'reserved_credits' => $requiredCredits,
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
                        'note' => "{$requiredCredits} credits reserved to send to all {$recipientCount} recipients from the beginning.",
                    ],
                ];
            }

            $this->idempotencyService->completeWithPayload(
                $start->row,
                'wa_campaign_resume',
                (string) $campaignId,
                $responsePayload
            );
        });

        if (empty($responsePayload)) {
            throw new RuntimeException('Failed to prepare campaign resume.');
        }

        $campaign = WaCampaign::query()->where('id', $campaignId)->where('user_id', $userId)->first();
        if ($campaign) {
            DispatchWaCampaignJob::dispatch((int) $campaign->id)->onQueue($queue);
        }

        return $responsePayload;
    }
}
