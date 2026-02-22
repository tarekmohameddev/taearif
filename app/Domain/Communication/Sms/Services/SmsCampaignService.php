<?php

namespace App\Domain\Communication\Sms\Services;

use App\Domain\Communication\Contracts\CreditService;
use App\Domain\Communication\DTOs\IdempotencyStartResult;
use App\Domain\Communication\Exceptions\IdempotencyConflictException;
use App\Domain\Communication\Exceptions\InsufficientCreditsException;
use App\Domain\Communication\Sms\Support\SmsEndpoints;
use App\Domain\Communication\Services\IdempotencyService;
use App\Jobs\DispatchSmsCampaignJob;
use App\Models\Api\markting\UserCredit;
use App\Models\SmsCampaign;
use App\Models\SmsMessageLog;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

class SmsCampaignService
{
    public function __construct(
        private readonly SmsRecipientResolverService $recipientResolver,
        private readonly IdempotencyService $idempotencyService,
        private readonly CreditService $creditService
    ) {}

    public function listForUser(int $userId, array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = SmsCampaign::query()
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

    public function findForUser(int $userId, int $campaignId): ?SmsCampaign
    {
        return SmsCampaign::query()
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

    public function create(int $userId, int $createdByUserId, array $data): SmsCampaign
    {
        $data['user_id'] = $userId;
        $data['created_by_user_id'] = $createdByUserId;
        $data['status'] = $data['status'] ?? 'draft';

        $campaign = SmsCampaign::create($data);
        $campaign->load([
            'template:id,name',
            'creator:id,first_name,last_name,account_type,tenant_id',
            'creator.basic_setting:id,user_id,company_name',
            'user:id',
            'user.basic_setting:id,user_id,company_name',
        ]);

        return $campaign;
    }

    public function update(SmsCampaign $campaign, array $data): SmsCampaign
    {
        if (!in_array($campaign->status, ['draft', 'scheduled'], true)) {
            throw new InvalidArgumentException('Only draft or scheduled campaigns can be updated.');
        }

        $campaign->update($data);

        return $campaign->refresh();
    }

    public function delete(SmsCampaign $campaign): void
    {
        if (!in_array($campaign->status, ['draft', 'scheduled'], true)) {
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
        $queue = (string) config('communication.sms.queue', 'communication');
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
            $start = $this->idempotencyService->start($userId, $idempotencyKey, SmsEndpoints::SEND_CAMPAIGN, $payload);
            if ($start->mode === IdempotencyStartResult::MODE_REPLAY && is_array($start->responsePayload)) {
                $responsePayload = $start->responsePayload;
                $dispatchNow = false;
                return;
            }
            if ($start->mode === IdempotencyStartResult::MODE_CONFLICT && $start->reason !== null) {
                throw new IdempotencyConflictException($start->reason);
            }

            $campaign = SmsCampaign::query()
                ->where('id', $campaignId)
                ->where('user_id', $userId)
                ->lockForUpdate()
                ->first();

            if (!$campaign) {
                throw (new ModelNotFoundException())->setModel(SmsCampaign::class, [$campaignId]);
            }

            if (!in_array($campaign->status, ['draft', 'scheduled'], true)) {
                throw new InvalidArgumentException('Campaign status is not sendable.');
            }

            if ($campaign->dispatch_reference !== null && !in_array($campaign->status, ['sent', 'failed', 'cancelled'], true)) {
                throw new IdempotencyConflictException(IdempotencyStartResult::REASON_IN_PROGRESS);
            }

            $recipients = $this->recipientResolver->resolve($userId, $customerIds, $manualPhones);
            if (count($recipients) === 0) {
                throw new InvalidArgumentException('No valid phone numbers from the given customer_ids or manual_phones. Ensure customer IDs exist and have a valid phone (8–16 digits), and that manual_phones are valid (8–16 digits).');
            }

            $creditsPerMessage = UserCredit::getCostForMessageType('sms');
            $requiredCredits = count($recipients) * $creditsPerMessage;
            if (!$this->creditService->hasSufficientCredits($userId, $requiredCredits)) {
                throw new InsufficientCreditsException($userId, $requiredCredits);
            }

            $this->creditService->deduct($userId, $requiredCredits, 'sms_campaign', (string) $start->row->id);

            $dispatchReference = (string) Str::uuid();
            $now = now();

            $rows = [];
            foreach ($recipients as $recipient) {
                $rows[] = [
                    'user_id' => $userId,
                    'campaign_id' => $campaign->id,
                    'customer_id' => $recipient['customer_id'],
                    'recipient_phone' => $recipient['phone'],
                    'recipient_name' => $recipient['name'],
                    'message' => (string) $campaign->message,
                    'status' => 'pending',
                    'meta' => json_encode(['dispatch_reference' => $dispatchReference]),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            foreach (array_chunk($rows, 1000) as $chunk) {
                SmsMessageLog::insert($chunk);
            }

            $scheduledAt = $campaign->scheduled_at;
            if ($scheduledAt !== null && $scheduledAt->isFuture()) {
                $campaign->update([
                    'status' => 'scheduled',
                    'dispatch_reference' => $dispatchReference,
                    'recipient_count' => count($rows),
                ]);
                $dispatchNow = false;
                $delayUntil = $scheduledAt;
            } else {
                $campaign->update([
                    'status' => 'in_progress',
                    'dispatch_reference' => $dispatchReference,
                    'recipient_count' => count($rows),
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
                'sms_campaign',
                (string) $campaign->id,
                $responsePayload
            );
        });

        if (empty($responsePayload)) {
            throw new RuntimeException('Failed to prepare campaign dispatch.');
        }

        if (isset($responsePayload['dispatch_reference']) && $dispatchNow) {
            DispatchSmsCampaignJob::dispatch((int) $responsePayload['campaign_id'])
                ->onQueue($queue);
        } elseif (isset($responsePayload['dispatch_reference']) && $delayUntil !== null) {
            DispatchSmsCampaignJob::dispatch((int) $responsePayload['campaign_id'])
                ->onQueue($queue)
                ->delay($delayUntil);
        }

        return $responsePayload;
    }
}
