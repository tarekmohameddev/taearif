<?php

namespace App\Console\Commands\Calling;

use App\Domain\Calling\Models\CallEvent;
use Illuminate\Console\Command;

class PruneCallEventsCommand extends Command
{
    protected $signature   = 'calling:prune-events';
    protected $description = 'Delete call_events older than the configured retention period.';

    public function handle(): int
    {
        $days    = config('calling.events_retention_days', 30);
        $cutoff  = now()->subDays($days);
        $deleted = CallEvent::where('created_at', '<', $cutoff)->delete();

        $this->info("Pruned {$deleted} call event(s) older than {$days} days.");

        return self::SUCCESS;
    }
}
