<?php

namespace App\Console\Commands;

use App\Models\CommunicationWebhookEvent;
use App\Models\Message;
use App\Models\SmsMessageLog;
use App\Domain\Communication\Services\StatusTransitionGuard;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ReconcileCommunicationDelivery extends Command
{
    protected $signature = 'communication:reconcile-delivery';

    protected $description = 'Reconcile delivery state from webhook events and optional provider polling.';

    public function __construct(
        private readonly StatusTransitionGuard $statusGuard
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        if (! config('communication.reliability.enabled', false)) {
            return self::SUCCESS;
        }

        $lookbackDays = (int) config('communication.reliability.reconcile.lookback_days', 30);
        $cutoff = now()->subDays($lookbackDays);

        $this->reconcileMessages($cutoff);
        $this->reconcileSmsLogs($cutoff);

        return self::SUCCESS;
    }

    private function reconcileMessages(\DateTimeInterface $cutoff): void
    {
        $messages = Message::query()
            ->where('status', 'sent')
            ->where('created_at', '>=', $cutoff)
            ->whereNotNull('provider_message_id')
            ->limit(200)
            ->get();

        foreach ($messages as $message) {
            $providerMsgId = $message->provider_message_id;
            if ($providerMsgId === null || $providerMsgId === '') {
                continue;
            }

            $latest = CommunicationWebhookEvent::query()
                ->where('provider_message_id', $providerMsgId)
                ->where('channel', 'whatsapp')
                ->where('event_type', 'status')
                ->where('user_id', $message->user_id)
                ->orderByDesc('received_at')
                ->first();

            if (! $latest || ! isset($latest->payload['status'])) {
                continue;
            }

            $payload = is_array($latest->payload) ? $latest->payload : [];
            $status = $payload['status'] ?? null;
            if ($status === null) {
                continue;
            }
            $internalStatus = match (strtolower((string) $status)) {
                'sent' => 'sent',
                'delivered' => 'delivered',
                'read' => 'read',
                'failed' => 'failed',
                default => null,
            };
            if ($internalStatus === null) {
                continue;
            }

            if ($this->statusGuard->canTransition($message->status ?? 'sent', $internalStatus, 'whatsapp')) {
                $message->update(['status' => $internalStatus]);
            }
        }
    }

    private function reconcileSmsLogs(\DateTimeInterface $cutoff): void
    {
        $logs = SmsMessageLog::query()
            ->where('status', 'sent')
            ->where('created_at', '>=', $cutoff)
            ->whereNotNull('gateway_message_id')
            ->limit(200)
            ->get();

        foreach ($logs as $log) {
            $gatewayMsgId = $log->gateway_message_id;
            if ($gatewayMsgId === null || $gatewayMsgId === '') {
                continue;
            }

            $latest = CommunicationWebhookEvent::query()
                ->where('provider_message_id', $gatewayMsgId)
                ->where('channel', 'sms')
                ->where('event_type', 'delivery')
                ->orderByDesc('received_at')
                ->first();

            if (! $latest || ! isset($latest->payload['status'])) {
                continue;
            }

            $payload = is_array($latest->payload) ? $latest->payload : [];
            $status = strtolower(trim((string) ($payload['status'] ?? '')));
            $internalStatus = match ($status) {
                'delivered' => 'delivered',
                'failed', 'undelivered', 'rejected' => 'failed',
                default => null,
            };
            if ($internalStatus === null) {
                continue;
            }

            if ($this->statusGuard->canTransition($log->status ?? 'sent', $internalStatus, 'sms')) {
                $update = ['status' => $internalStatus];
                if ($internalStatus === 'delivered') {
                    $update['delivered_at'] = now();
                }
                $log->update($update);
            }
        }
    }
}
