<?php

namespace App\Console\Commands\Calling;

use App\Domain\Calling\Models\CallSimLine;
use App\Domain\Calling\Models\CallTrunk;
use App\Domain\Calling\Repositories\AsteriskRealtimeRepository;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncTrunksCommand extends Command
{
    protected $signature   = 'calling:sync-trunks';
    protected $description = 'Check Asterisk endpoint registration status and update call_trunks.status.';

    public function handle(AsteriskRealtimeRepository $asterisk): int
    {
        $trunks = CallTrunk::withoutTrashed()->with('simLines')->get();

        foreach ($trunks as $trunk) {
            $this->syncTrunk($trunk, $asterisk);
        }

        return self::SUCCESS;
    }

    private function syncTrunk(CallTrunk $trunk, AsteriskRealtimeRepository $asterisk): void
    {
        try {
            $anyRegistered = $trunk->simLines->contains(function (CallSimLine $line) use ($asterisk) {
                return $asterisk->isEndpointRegistered($line->asterisk_endpoint);
            });

            $newStatus = $anyRegistered ? 'registered' : 'unregistered';

            if ($trunk->status !== $newStatus) {
                $trunk->update([
                    'status'           => $newStatus,
                    'status_checked_at' => now(),
                ]);
            } else {
                $trunk->update(['status_checked_at' => now()]);
            }
        } catch (\Throwable $e) {
            Log::warning("[sync-trunks] Failed to check trunk {$trunk->id}: " . $e->getMessage());
            $trunk->update(['status' => 'error', 'status_checked_at' => now()]);
        }
    }
}
