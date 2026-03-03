<?php

namespace App\Domain\Communication\Sms\Services;

use App\Domain\Communication\Contracts\CreditService;
use App\Domain\Communication\Services\DeliveryAttemptRecorder;
use App\Domain\Communication\Sms\Contracts\SmsDispatcher;
use App\Domain\Communication\Sms\Contracts\SmsGatewayClient;
use App\Jobs\DispatchSmsCampaignJob;
use App\Models\SmsCampaign;
use App\Models\SmsMessageLog;
use Illuminate\Support\Facades\DB;

class SmsDispatcherService implements SmsDispatcher
{
    public function __construct(
        private readonly SmsGatewayClient $gatewayClient,
        private readonly CreditService $creditService,
        private readonly ?DeliveryAttemptRecorder $deliveryAttemptRecorder = null
    ) {}

    public function dispatchCampaign(int $campaignId): void
    {
        $campaign = SmsCampaign::query()->find($campaignId);
        if (!$campaign) {
            return;
        }

        $batchSize = max(1, (int) config('communication.sms.batch_size', 200));
        $logs = SmsMessageLog::query()
            ->where('campaign_id', $campaignId)
            ->where('status', 'pending')
            ->orderBy('id')
            ->limit($batchSize)
            ->get();

        foreach ($logs as $log) {
            $this->dispatchLog($log, true);
        }

        $remaining = SmsMessageLog::query()
            ->where('campaign_id', $campaignId)
            ->where('status', 'pending')
            ->count();

        if ($remaining > 0) {
            DispatchSmsCampaignJob::dispatch($campaignId)
                ->onQueue((string) config('communication.sms.queue', 'communication'));
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
        $log = SmsMessageLog::query()->find($logId);
        if (!$log) {
            return;
        }

        $this->dispatchLog($log, false);
    }

    private function dispatchLog(SmsMessageLog $log, bool $retryEligible = false): void
    {
        if ($log->status !== 'pending') {
            return;
        }

        $result = $this->gatewayClient->sendText(
            $log->recipient_phone,
            $log->message,
            null,
            ['log_id' => $log->id, 'campaign_id' => $log->campaign_id]
        );

        if ($result->success) {
            $affected = SmsMessageLog::query()
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
                $this->creditService->consumeReserved((int) $log->user_id, 1, 'sms_message_log', (string) $log->id);
                SmsCampaign::query()->where('id', $log->campaign_id)->increment('sent_count');
                SmsCampaign::query()->where('id', $log->campaign_id)->decrement('reserved_credits');
            }

            if ($this->shouldRecordAttempts()) {
                $this->deliveryAttemptRecorder->recordSmsLogAttempt(
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

        $affected = SmsMessageLog::query()
            ->where('id', $log->id)
            ->where('status', 'pending')
            ->update([
                'status' => 'failed',
                'provider' => $result->provider,
                'error_message' => $result->error ?: 'sms_provider_failed',
                'updated_at' => now(),
            ]);

        if ($affected !== 1) {
            return;
        }

        if ($log->campaign_id !== null) {
            SmsCampaign::query()->where('id', $log->campaign_id)->increment('failed_count');
        }

        if ($this->shouldRecordAttempts()) {
            $this->deliveryAttemptRecorder->recordSmsLogAttempt(
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
            $this->creditService->releaseReserved((int) $log->user_id, 1, 'sms_message_log_failed', (string) $log->id);
            SmsCampaign::query()->where('id', $log->campaign_id)->decrement('reserved_credits');
        } else {
            $this->refundIfNeeded((int) $log->id);
        }
    }

    private function refundIfNeeded(int $logId): void
    {
        DB::transaction(function () use ($logId): void {
            $log = SmsMessageLog::query()
                ->where('id', $logId)
                ->lockForUpdate()
                ->first();

            if (!$log || $log->refund_processed_at !== null) {
                return;
            }

            $this->creditService->refund((int) $log->user_id, 1, 'sms_message_log', (string) $log->id);

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

