<?php

namespace App\Console\Commands\Calling;

use App\Domain\Calling\Models\CallLog;
use Illuminate\Console\Command;

class ReconcileCallsCommand extends Command
{
    protected $signature   = 'calling:reconcile-calls';
    protected $description = 'Close all call_logs that are stuck in a non-terminal status.';

    public function handle(): int
    {
        $updated = CallLog::whereNotIn('status', CallLog::TERMINAL_STATUSES)
            ->update([
                'status'     => 'failed',
                'fail_reason'=> 'reconciled',
                'ended_at'   => now(),
            ]);

        $this->info("Reconciled {$updated} stuck call(s).");

        return self::SUCCESS;
    }
}
