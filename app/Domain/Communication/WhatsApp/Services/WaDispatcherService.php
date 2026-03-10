<?php

namespace App\Domain\Communication\WhatsApp\Services;

use App\Domain\Communication\Contracts\CreditService;
use App\Domain\Communication\Exceptions\ProviderSendFailedException;
use App\Domain\Communication\WhatsApp\Contracts\WaDispatcher;
use App\Jobs\DispatchWaCampaignJob;
use App\Models\WaCampaign;
use App\Models\WaMessageLog;
use App\Models\WaNumber;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WaDispatcherService implements WaDispatcher
{
    public function __construct(
        private readonly WhatsAppChannelSender $channelSender,
        private readonly CreditService $creditService
    ) {}

    public function dispatchCampaign(int $campaignId): void
    {
        $campaign = WaCampaign::query()->find($campaignId);
        if (! $campaign) {
            return;
        }
        $creditsPerMessage = $this->resolveCreditsPerMessage($campaign);

        $waNumber = WaNumber::query()->find($campaign->wa_number_id);
        if (! $waNumber || (int) $waNumber->user_id !== (int) $campaign->user_id) {
            $this->failSafeCampaign($campaign, 'WA_NUMBER_NOT_FOUND', 'wa_number not found or ownership mismatch');
            return;
        }

        $requireActive = (bool) config('communication.whatsapp.campaign.require_active_wa_number', true);
        if ($requireActive && ! in_array((string) $waNumber->status, ['active'], true)) {
            $this->failSafeCampaign($campaign, 'WA_NUMBER_NOT_ACTIVE', 'wa_number not active');
            return;
        }

        if (! $requireActive && ! in_array((string) $waNumber->status, ['active'], true)) {
            Log::warning('WaDispatcherService: sending with inactive wa_number (require_active=false)', [
                'campaign_id' => $campaign->id,
                'wa_number_id' => $waNumber->id,
                'user_id' => $campaign->user_id,
            ]);
            $meta = is_array($campaign->meta) ? $campaign->meta : [];
            if (empty($meta['sender_policy_override'])) {
                WaCampaign::query()->where('id', $campaign->id)->update([
                    'meta' => array_merge($meta, ['sender_policy_override' => true]),
                ]);
            }
        }

        $batchSize = max(1, (int) config('communication.whatsapp.batch_size', 100));
        $logs = WaMessageLog::query()
            ->where('campaign_id', $campaignId)
            ->where('status', 'pending')
            ->orderBy('id')
            ->limit($batchSize)
            ->get();

        foreach ($logs as $log) {
            $this->dispatchLog($log, $waNumber, $creditsPerMessage, true);
        }

        $remaining = WaMessageLog::query()
            ->where('campaign_id', $campaignId)
            ->where('status', 'pending')
            ->count();

        if ($remaining > 0) {
            DispatchWaCampaignJob::dispatch($campaignId)
                ->onQueue((string) config('communication.whatsapp.queue', 'communication'));
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
        $log = WaMessageLog::query()->find($logId);
        if (! $log) {
            return;
        }

        $waNumber = WaNumber::query()->find($log->wa_number_id);
        if (! $waNumber) {
            Log::warning('WaDispatcherService: log wa_number not found', ['log_id' => $logId]);
            $this->failSingleLogForPolicy($log, 'WA_NUMBER_NOT_FOUND', 'wa_number not found');
            return;
        }

        $ownerUserId = (int) $log->user_id;
        $campaign = null;
        if ($log->campaign_id !== null) {
            $campaign = WaCampaign::query()->find((int) $log->campaign_id);
            if ($campaign) {
                $ownerUserId = (int) $campaign->user_id;
            }
        }
        if ((int) $waNumber->user_id !== $ownerUserId) {
            $this->failSingleLogForPolicy($log, 'WA_NUMBER_NOT_FOUND', 'wa_number ownership mismatch');
            return;
        }

        $requireActive = (bool) config('communication.whatsapp.campaign.require_active_wa_number', true);
        if ($requireActive && ! in_array((string) $waNumber->status, ['active'], true)) {
            $this->failSingleLogForPolicy($log, 'WA_NUMBER_NOT_ACTIVE', 'wa_number not active');
            return;
        }

        if (! $requireActive && ! in_array((string) $waNumber->status, ['active'], true)) {
            Log::warning('WaDispatcherService: single-log dispatch with inactive wa_number (require_active=false)', [
                'log_id' => $log->id,
                'wa_number_id' => $waNumber->id,
                'user_id' => $ownerUserId,
            ]);
        }

        $creditsPerMessage = $campaign ? $this->resolveCreditsPerMessage($campaign) : 1;
        $this->dispatchLog($log, $waNumber, $creditsPerMessage, false);
    }

    private function dispatchLog(WaMessageLog $log, WaNumber $waNumber, int $creditsPerMessage, bool $retryEligible = false): void
    {
        if ($log->status !== 'pending') {
            return;
        }

        try {
            $result = $this->channelSender->send(
                $waNumber,
                $log->recipient_phone,
                $log->message
            );
        } catch (ProviderSendFailedException $e) {
            $result = null;
            Log::error('WaDispatcherService: ProviderSendFailedException', [
                'log_id' => $log->id,
                'message' => $e->getMessage(),
            ]);
        }

        if ($result !== null && $result->success) {
            $affected = WaMessageLog::query()
                ->where('id', $log->id)
                ->where('status', 'pending')
                ->update([
                    'status' => 'sent',
                    'gateway_message_id' => $result->provider_message_id,
                    'provider' => strtolower((string) $waNumber->provider),
                    'error_message' => null,
                    'sent_at' => now(),
                    'updated_at' => now(),
                ]);

            if ($affected === 1 && $log->campaign_id !== null) {
                $this->creditService->consumeReserved((int) $log->user_id, $creditsPerMessage, 'wa_message_log', (string) $log->id);
                WaCampaign::query()->where('id', $log->campaign_id)->increment('sent_count');
                WaCampaign::query()->where('id', $log->campaign_id)->decrement('reserved_credits', $creditsPerMessage);
            }
            return;
        }

        $errorMessage = $result !== null ? ($result->error_message ?: 'whatsapp_provider_failed') : 'ProviderSendFailedException';
        $provider = strtolower((string) $waNumber->provider);

        $affected = WaMessageLog::query()
            ->where('id', $log->id)
            ->where('status', 'pending')
            ->update([
                'status' => 'failed',
                'provider' => $provider,
                'error_message' => $errorMessage,
                'updated_at' => now(),
            ]);

        if ($affected !== 1) {
            return;
        }

        if ($log->campaign_id !== null) {
            WaCampaign::query()->where('id', $log->campaign_id)->increment('failed_count');
            $this->creditService->releaseReserved((int) $log->user_id, $creditsPerMessage, 'wa_message_log_failed', (string) $log->id);
            WaCampaign::query()->where('id', $log->campaign_id)->decrement('reserved_credits', $creditsPerMessage);
        } else {
            $this->refundIfNeeded((int) $log->id);
        }
    }

    private function refundIfNeeded(int $logId): void
    {
        DB::transaction(function () use ($logId): void {
            $log = WaMessageLog::query()
                ->where('id', $logId)
                ->lockForUpdate()
                ->first();

            if (! $log || $log->refund_processed_at !== null) {
                return;
            }

            $this->creditService->refund((int) $log->user_id, 1, 'wa_message_log', (string) $log->id);

            $log->update([
                'refund_processed_at' => now(),
            ]);
        });
    }

    /**
     * Fix #1: When wa_number is invalid or policy blocks send, fail the campaign safely:
     * set campaign failed, mark pending logs failed, release reserved credits, log once.
     */
    private function failSafeCampaign(WaCampaign $campaign, string $errorCode, string $logMessage): void
    {
        $creditsPerMessage = $this->resolveCreditsPerMessage($campaign);
        DB::transaction(function () use ($campaign, $errorCode, $creditsPerMessage): void {
            $campaign->refresh();
            if (in_array($campaign->status, ['sent', 'failed', 'cancelled'], true)) {
                return;
            }

            $pendingCount = WaMessageLog::query()
                ->where('campaign_id', $campaign->id)
                ->where('status', 'pending')
                ->update([
                    'status' => 'failed',
                    'error_message' => $errorCode,
                    'updated_at' => now(),
                ]);

            if ($pendingCount > 0) {
                $this->creditService->releaseReserved(
                    (int) $campaign->user_id,
                    $pendingCount * $creditsPerMessage,
                    'wa_campaign_fail_safe',
                    (string) $campaign->id
                );
                WaCampaign::query()->where('id', $campaign->id)->increment('failed_count', $pendingCount);
            }

            $campaign->update([
                'status' => 'failed',
                'sent_at' => now(),
                'reserved_credits' => 0,
            ]);
        });

        Log::error('WaDispatcherService.fail_safe_campaign', [
            'campaign_id' => $campaign->id,
            'wa_number_id' => $campaign->wa_number_id,
            'user_id' => $campaign->user_id,
            'error_code' => $errorCode,
            'message' => $logMessage,
        ]);
    }

    private function failSingleLogForPolicy(WaMessageLog $log, string $errorCode, string $reason): void
    {
        if ($log->status !== 'pending') {
            return;
        }

        $creditsPerMessage = 1;
        $campaign = null;
        if ($log->campaign_id !== null) {
            $campaign = WaCampaign::query()->find((int) $log->campaign_id);
            if ($campaign) {
                $creditsPerMessage = $this->resolveCreditsPerMessage($campaign);
            }
        }

        $affected = WaMessageLog::query()
            ->where('id', $log->id)
            ->where('status', 'pending')
            ->update([
                'status' => 'failed',
                'error_message' => $errorCode,
                'updated_at' => now(),
            ]);
        if ($affected !== 1) {
            return;
        }

        if ($campaign) {
            WaCampaign::query()->where('id', $campaign->id)->increment('failed_count');
            $this->creditService->releaseReserved((int) $log->user_id, $creditsPerMessage, 'wa_message_log_policy_failed', (string) $log->id);
            WaCampaign::query()->where('id', $campaign->id)->decrement('reserved_credits', $creditsPerMessage);
        } else {
            $this->refundIfNeeded((int) $log->id);
        }

        Log::warning('WaDispatcherService.fail_single_log_for_policy', [
            'log_id' => $log->id,
            'campaign_id' => $log->campaign_id,
            'user_id' => $log->user_id,
            'error_code' => $errorCode,
            'reason' => $reason,
        ]);
    }

    private function resolveCreditsPerMessage(WaCampaign $campaign): int
    {
        $meta = is_array($campaign->meta) ? $campaign->meta : [];
        if (isset($meta['credits_per_message']) && is_numeric($meta['credits_per_message'])) {
            return max(1, (int) $meta['credits_per_message']);
        }
        return 1;
    }
}
