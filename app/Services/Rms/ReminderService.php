<?php

namespace App\Services\Rms;

use Illuminate\Support\Carbon;
use App\Models\Api\Rms\RmReminder;

class ReminderService
{
    public function list($userId, array $filters = [])
    {
        $query = RmReminder::where('user_id', $userId);

        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['from'])) {
            $query->whereDate('due_on', '>=', $filters['from']);
        }

        if (!empty($filters['to'])) {
            $query->whereDate('due_on', '<=', $filters['to']);
        }

        return $query->orderBy('due_on', 'asc')->get();
    }

    public function dismiss($id, $userId)
    {
        $reminder = RmReminder::where('id', $id)->where('user_id', $userId)->firstOrFail();
        $reminder->update(['status' => 'dismissed']);
        return $reminder;
    }

    public function snooze($id, $date, $userId)
    {
        $reminder = RmReminder::where('id', $id)->where('user_id', $userId)->firstOrFail();
        $reminder->update([
            'status' => 'snoozed',
            'snooze_until' => Carbon::parse($date),
        ]);
        return $reminder;
    }

    // Future enhancement: scheduled engine to generate reminders
    public function generateDailyReminders()
    {
        // Stub for cron job:
        // 1. Find contracts expiring in X days
        // 2. Find unpaid/overdue installments
        // 3. Create unique reminders per rental
    }
}
