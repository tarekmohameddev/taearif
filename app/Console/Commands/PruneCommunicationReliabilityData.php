<?php

namespace App\Console\Commands;

use App\Models\CommunicationDeliveryAttempt;
use App\Models\CommunicationWebhookEvent;
use Illuminate\Console\Command;

class PruneCommunicationReliabilityData extends Command
{
    protected $signature = 'communication:prune-reliability-data';

    protected $description = 'Prune communication reliability operational data older than retention_days.';

    public function handle(): int
    {
        if (! config('communication.reliability.enabled', false)) {
            return self::SUCCESS;
        }

        $retentionDays = (int) config('communication.reliability.retention_days', 30);
        $cutoff = now()->subDays($retentionDays);

        $deletedAttempts = CommunicationDeliveryAttempt::query()
            ->where('created_at', '<', $cutoff)
            ->delete();

        $deletedEvents = CommunicationWebhookEvent::query()
            ->where('created_at', '<', $cutoff)
            ->delete();

        if ($deletedAttempts > 0 || $deletedEvents > 0) {
            $this->info("Pruned {$deletedAttempts} delivery attempts and {$deletedEvents} webhook events.");
        }

        return self::SUCCESS;
    }
}
