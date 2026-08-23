<?php

namespace App\Console\Commands\Calling;

use App\Domain\Calling\Models\CallLog;
use Illuminate\Console\Command;

class ReconcileCallsCommand extends Command
{
    protected $signature   = 'calling:reconcile-calls';
    protected $description = 'Close call_logs that are stuck in a non-terminal status beyond the configured timeout.';

    public function handle(): int
    {
        $minutes = config('calling.reconcile_after_minutes', 60);
        $cutoff  = now()->subMinutes($minutes);

        $updated = CallLog::whereNotIn('status', CallLog::TERMINAL_STATUSES)
            ->where('created_at', '<', $cutoff)
            ->update([
                'status'     => 'failed',
                'fail_reason'=> 'reconciled',
                'ended_at'   => now(),
            ]);

        $this->info("Reconciled {$updated} stuck call(s).");

        return self::SUCCESS;
    }
}
