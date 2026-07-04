<?php

namespace App\Console\Commands;

use App\Domain\CustomersHub\Services\CustomersHubPropertyRequestNotifier;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class NotifyCustomersHubDueReminders extends Command
{
    protected $signature = 'customers-hub:notify-due-reminders';

    protected $description = 'Create in-app notifications for due-soon and overdue property request reminders';

    public function handle(CustomersHubPropertyRequestNotifier $notifier): int
    {
        $now = Carbon::now();
        $dueSoonEnd = $now->copy()->addMinutes(15);

        $dueSoon = DB::table('property_request_reminders')
            ->where('status', 'pending')
            ->whereBetween('datetime', [$now, $dueSoonEnd])
            ->get(['id', 'user_id', 'property_request_id', 'title', 'datetime']);

        foreach ($dueSoon as $reminder) {
            $notifier->notifyReminderDue(
                (int) $reminder->user_id,
                (int) $reminder->property_request_id,
                (int) $reminder->id,
                (string) $reminder->title,
                Carbon::parse($reminder->datetime)->toDateTimeString(),
                false
            );
        }

        $overdue = DB::table('property_request_reminders')
            ->where('status', 'pending')
            ->where('datetime', '<', $now)
            ->get(['id', 'user_id', 'property_request_id', 'title', 'datetime']);

        foreach ($overdue as $reminder) {
            $notifier->notifyReminderDue(
                (int) $reminder->user_id,
                (int) $reminder->property_request_id,
                (int) $reminder->id,
                (string) $reminder->title,
                Carbon::parse($reminder->datetime)->toDateTimeString(),
                true
            );
        }

        $this->info(sprintf(
            'Processed %d due-soon and %d overdue property request reminders.',
            $dueSoon->count(),
            $overdue->count()
        ));

        return self::SUCCESS;
    }
}
