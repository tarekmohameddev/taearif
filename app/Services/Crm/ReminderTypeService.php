<?php

namespace App\Services\Crm;

use App\Models\ReminderType;
use App\Models\Reminder;
use Illuminate\Support\Facades\DB;

class ReminderTypeService
{
    /**
     * Check if reminder type has active reminders
     *
     * @param int $reminderTypeId
     * @return bool
     */
    public function hasActiveReminders(int $reminderTypeId): bool
    {
        return Reminder::where('reminder_type_id', $reminderTypeId)
            ->where('status', 'pending')
            ->exists();
    }

    /**
     * Check if reminder type can be deleted
     *
     * @param int $reminderTypeId
     * @return array ['can_delete' => bool, 'reason' => string|null]
     */
    public function canDelete(int $reminderTypeId): array
    {
        $hasReminders = Reminder::where('reminder_type_id', $reminderTypeId)->exists();
        
        if ($hasReminders) {
            return [
                'can_delete' => false,
                'reason' => 'Cannot delete reminder type. There are active reminders using this type. Please delete or update those reminders first.',
                'reason_ar' => 'لا يمكن حذف نوع التذكير. هناك تذكيرات نشطة تستخدم هذا النوع. يرجى حذف أو تحديث تلك التذكيرات أولاً',
            ];
        }

        return ['can_delete' => true, 'reason' => null, 'reason_ar' => null];
    }

    /**
     * Check if reminder type can be deactivated
     *
     * @param int $reminderTypeId
     * @return array ['can_deactivate' => bool, 'reason' => string|null]
     */
    public function canDeactivate(int $reminderTypeId): array
    {
        if ($this->hasActiveReminders($reminderTypeId)) {
            return [
                'can_deactivate' => false,
                'reason' => 'Cannot deactivate reminder type. There are active reminders using this type.',
                'reason_ar' => 'لا يمكن تعطيل نوع التذكير. هناك تذكيرات نشطة تستخدم هذا النوع',
            ];
        }

        return ['can_deactivate' => true, 'reason' => null, 'reason_ar' => null];
    }

    /**
     * Get reminders count for a reminder type
     *
     * @param int $reminderTypeId
     * @return int
     */
    public function getRemindersCount(int $reminderTypeId): int
    {
        return Reminder::where('reminder_type_id', $reminderTypeId)->count();
    }
}
