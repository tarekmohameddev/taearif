<?php

namespace App\Domain\Communication\Email\Services;

use App\Domain\Communication\Contracts\CreditService;
use App\Domain\Communication\Services\DeliveryAttemptRecorder;
use App\Domain\Communication\Email\Contracts\EmailDispatcher;
use App\Domain\Communication\Email\Contracts\EmailGatewayClient;
use App\Jobs\DispatchEmailCampaignJob;
use App\Models\EmailCampaign;
use App\Models\EmailMessageLog;
use Illuminate\Support\Facades\DB;

class EmailDispatcherService implements EmailDispatcher
{
    public function __construct(
        private readonly EmailGatewayClient $gatewayClient,
        private readonly CreditService $creditService,
        private readonly ?DeliveryAttemptRecorder $deliveryAttemptRecorder = null
    ) {}

    public function dispatchCampaign(int $campaignId): void
    {
        $campaign = EmailCampaign::query()->find($campaignId);
        if (!$campaign) {
            return;
        }

        $batchSize = max(1, (int) config('communication.email.batch_size', 100));
        $logs = EmailMessageLog::query()
            ->where('campaign_id', $campaignId)
            ->where('status', 'pending')
            ->orderBy('id')
            ->limit($batchSize)
            ->get();

        foreach ($logs as $log) {
            $this->dispatchLog($log, true);
        }

        $remaining = EmailMessageLog::query()
            ->where('campaign_id', $campaignId)
            ->where('status', 'pending')
            ->count();

        if ($remaining > 0) {
            DispatchEmailCampaignJob::dispatch($campaignId)
                ->onQueue((string) config('communication.email.queue', 'communication'));
            return;
        }

        $campaign->refresh();
        $finalStatus = $campaign->sent_count > 0 ? 'sent' : 'failed';
        $campaign->update([
            'status' => $finalStatus,
            'sent_at' => now(),
        ]);
    }

    public function dispatchSingleLog(int $logId): void
    {
        $log = EmailMessageLog::query()->find($logId);
        if (!$log) {
            return;
        }

        $this->dispatchLog($log, false);
    }

    private function dispatchLog(EmailMessageLog $log, bool $retryEligible = false): void
    {
        if ($log->status !== 'pending') {
            return;
        }

        $result = $this->gatewayClient->sendEmail(
            $log->recipient_email,
            $log->subject,
            $log->body_html,
            $log->body_text,
            null,
            null,
            ['log_id' => $log->id, 'campaign_id' => $log->campaign_id]
        );

        if ($result->success) {
            $affected = EmailMessageLog::query()
                ->where('id', $log->id)
                ->where('status', 'pending')
                ->update([
                    'status' => 'sent',
                    'gateway_message_id' => $result->gatewayMessageId,
                    'provider' => $result->provider,
                    'error_message' => null,
                    'sent_at' => now(),
                    'updated_at' => now(),
                ]);

            if ($affected === 1 && $log->campaign_id !== null) {
                $this->creditService->consumeReserved((int) $log->user_id, 1, 'email_message_log', (string) $log->id);
                EmailCampaign::query()->where('id', $log->campaign_id)->increment('sent_count');
                EmailCampaign::query()->where('id', $log->campaign_id)->decrement('reserved_credits');
            }

            if ($this->shouldRecordAttempts()) {
                $this->deliveryAttemptRecorder->recordEmailLogAttempt(
                    $log->fresh(),
                    $result->provider,
                    true,
                    $result->gatewayMessageId,
                    false,
                    $retryEligible,
                    null,
                    null,
                    null,
                    $result->rawResponse
                );
            }
            return;
        }

        $affected = EmailMessageLog::query()
            ->where('id', $log->id)
            ->where('status', 'pending')
            ->update([
                'status' => 'failed',
                'provider' => $result->provider,
                'error_message' => $result->error ?: 'email_provider_failed',
                'updated_at' => now(),
            ]);

        if ($affected !== 1) {
            return;
        }

        if ($log->campaign_id !== null) {
            EmailCampaign::query()->where('id', $log->campaign_id)->increment('failed_count');
        }

        if ($this->shouldRecordAttempts()) {
            $this->deliveryAttemptRecorder->recordEmailLogAttempt(
                $log->fresh(),
                $result->provider,
                false,
                null,
                $result->isTransientFailure,
                $retryEligible,
                null,
                $result->error,
                null,
                $result->rawResponse
            );
        }

        if ($log->campaign_id !== null) {
            $this->creditService->releaseReserved((int) $log->user_id, 1, 'email_message_log_failed', (string) $log->id);
            EmailCampaign::query()->where('id', $log->campaign_id)->decrement('reserved_credits');
        } else {
            $this->refundIfNeeded((int) $log->id);
        }
    }

    private function refundIfNeeded(int $logId): void
    {
        DB::transaction(function () use ($logId): void {
            $log = EmailMessageLog::query()
                ->where('id', $logId)
                ->lockForUpdate()
                ->first();

            if (!$log || $log->refund_processed_at !== null) {
                return;
            }

            $this->creditService->refund((int) $log->user_id, 1, 'email_message_log', (string) $log->id);

            $log->update([
                'refund_processed_at' => now(),
            ]);
        });
    }

    private function shouldRecordAttempts(): bool
    {
        return config('communication.reliability.enabled', false) && $this->deliveryAttemptRecorder !== null;
    }
}

