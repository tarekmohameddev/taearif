<?php

namespace App\Services\Crm;

use App\Models\Reminder;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ReminderService
{
    /**
     * Auto-update overdue reminders status
     *
     * @param int|null $userId
     * @return int Number of updated reminders
     */
    public function updateOverdueReminders(?int $userId = null): int
    {
        $query = Reminder::where('status', 'pending')
            ->where('datetime', '<', Carbon::now());

        if ($userId) {
            $query->where('user_id', $userId);
        }

        return $query->update(['status' => 'overdue']);
    }

    /**
     * Check if reminder datetime is valid (future)
     *
     * @param string $datetime
     * @return bool
     */
    public function isValidFutureDatetime(string $datetime): bool
    {
        try {
            $parsed = Carbon::parse($datetime);
            return $parsed->isFuture();
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Auto-update status to overdue if datetime has passed
     *
     * @param Reminder $reminder
     * @return Reminder
     */
    public function updateReminderStatus(Reminder $reminder): Reminder
    {
        if ($reminder->status === 'pending' && $reminder->datetime && $reminder->datetime->isPast()) {
            $reminder->update(['status' => 'overdue']);
            $reminder->refresh();
        }

        return $reminder;
    }
}
