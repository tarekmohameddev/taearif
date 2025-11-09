<?php

namespace App\Domain\Daily\Services;

use App\Domain\Shared\Services\BaseService;
use App\Models\Api\Rms\RmReminder;
use App\Models\Api\UserApiCustomerReminder;
use App\Models\Api\UserApiCustomerAppointment;
use App\Models\Membership;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Daily Service
 *
 * Aggregates all daily follow-up data across the platform
 * for admin dashboard
 */
class DailyService extends BaseService
{
    /**
     * Get unified daily dashboard
     *
     * @param array $filters
     * @return array
     */
    public function getDailyDashboard(array $filters = []): array
    {
        $date = $filters['date'] ?? now()->toDateString();

        return [
            'date' => $date,
            'statistics' => $this->getStatistics($date),
            'today_summary' => $this->getTodaysSummary($date),
            'overdue_count' => $this->getOverdueCount(),
            'upcoming' => $this->getUpcomingItems($date, 7), // Next 7 days
        ];
    }

    /**
     * Get all reminders with filters
     *
     * @param array $filters
     * @param int $perPage
     * @return array
     */
    public function getAllReminders(array $filters = [], int $perPage = 20): array
    {
        $query = UserApiCustomerReminder::with(['user', 'customer']);

        // Apply filters
        if (isset($filters['priority'])) {
            $query->where('priority', $filters['priority']);
        }

        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (isset($filters['from_date'])) {
            $query->whereDate('datetime', '>=', $filters['from_date']);
        }

        if (isset($filters['to_date'])) {
            $query->whereDate('datetime', '<=', $filters['to_date']);
        }

        if (isset($filters['search'])) {
            $query->where('title', 'like', '%' . $filters['search'] . '%');
        }

        $query->orderBy('datetime', 'asc');

        $paginated = $query->paginate($perPage);

        return [
            'data' => $paginated->items(),
            'pagination' => [
                'total' => $paginated->total(),
                'count' => $paginated->count(),
                'per_page' => $paginated->perPage(),
                'current_page' => $paginated->currentPage(),
                'total_pages' => $paginated->lastPage(),
            ],
        ];
    }

    /**
     * Get reminder by ID
     *
     * @param int $id
     * @return UserApiCustomerReminder|null
     */
    public function getReminderById(int $id): ?UserApiCustomerReminder
    {
        return UserApiCustomerReminder::with(['user', 'customer'])->find($id);
    }

    /**
     * Get all appointments with filters
     *
     * @param array $filters
     * @param int $perPage
     * @return array
     */
    public function getAllAppointments(array $filters = [], int $perPage = 20): array
    {
        $query = UserApiCustomerAppointment::with(['user', 'customer']);

        // Apply filters
        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (isset($filters['priority'])) {
            $query->where('priority', $filters['priority']);
        }

        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (isset($filters['from_date'])) {
            $query->whereDate('datetime', '>=', $filters['from_date']);
        }

        if (isset($filters['to_date'])) {
            $query->whereDate('datetime', '<=', $filters['to_date']);
        }

        if (isset($filters['search'])) {
            $query->where('title', 'like', '%' . $filters['search'] . '%');
        }

        $query->orderBy('datetime', 'asc');

        $paginated = $query->paginate($perPage);

        return [
            'data' => $paginated->items(),
            'pagination' => [
                'total' => $paginated->total(),
                'count' => $paginated->count(),
                'per_page' => $paginated->perPage(),
                'current_page' => $paginated->currentPage(),
                'total_pages' => $paginated->lastPage(),
            ],
        ];
    }

    /**
     * Get appointment by ID
     *
     * @param int $id
     * @return UserApiCustomerAppointment|null
     */
    public function getAppointmentById(int $id): ?UserApiCustomerAppointment
    {
        return UserApiCustomerAppointment::with(['user', 'customer'])->find($id);
    }

    /**
     * Get RMS reminders (rental management)
     *
     * @param array $filters
     * @param int $perPage
     * @return array
     */
    public function getRmsReminders(array $filters = [], int $perPage = 20): array
    {
        $query = RmReminder::with(['rental']);

        // Apply filters
        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (isset($filters['from_date'])) {
            $query->whereDate('due_on', '>=', $filters['from_date']);
        }

        if (isset($filters['to_date'])) {
            $query->whereDate('due_on', '<=', $filters['to_date']);
        }

        $query->orderBy('due_on', 'asc');

        $paginated = $query->paginate($perPage);

        return [
            'data' => $paginated->items(),
            'pagination' => [
                'total' => $paginated->total(),
                'count' => $paginated->count(),
                'per_page' => $paginated->perPage(),
                'current_page' => $paginated->currentPage(),
                'total_pages' => $paginated->lastPage(),
            ],
        ];
    }

    /**
     * Get statistics for a specific date
     *
     * @param string $date
     * @return array
     */
    public function getStatistics(string $date): array
    {
        $targetDate = Carbon::parse($date);

        return [
            'reminders' => [
                'total' => UserApiCustomerReminder::whereDate('datetime', $targetDate)->count(),
                'high_priority' => UserApiCustomerReminder::whereDate('datetime', $targetDate)->where('priority', 3)->count(),
                'medium_priority' => UserApiCustomerReminder::whereDate('datetime', $targetDate)->where('priority', 2)->count(),
                'low_priority' => UserApiCustomerReminder::whereDate('datetime', $targetDate)->where('priority', 1)->count(),
            ],
            'appointments' => [
                'total' => UserApiCustomerAppointment::whereDate('datetime', $targetDate)->count(),
                'high_priority' => UserApiCustomerAppointment::whereDate('datetime', $targetDate)->where('priority', 3)->count(),
                'by_type' => $this->getAppointmentsByType($targetDate),
            ],
            'rms_reminders' => [
                'total' => RmReminder::whereDate('due_on', $targetDate)->count(),
                'by_type' => $this->getRmsRemindersByType($targetDate),
                'by_status' => $this->getRmsRemindersByStatus($targetDate),
            ],
            'subscriptions' => [
                'expiring_soon' => $this->getSubscriptionsExpiringSoon(7), // Next 7 days
                'expired_today' => $this->getSubscriptionsExpiredToday($targetDate),
            ],
        ];
    }

    /**
     * Get overdue items
     *
     * @return array
     */
    public function getOverdueItems(): array
    {
        $now = now();

        return [
            'reminders' => UserApiCustomerReminder::with(['user', 'customer'])
                ->where('datetime', '<', $now)
                ->orderBy('datetime', 'desc')
                ->limit(50)
                ->get(),
            'appointments' => UserApiCustomerAppointment::with(['user', 'customer'])
                ->where('datetime', '<', $now)
                ->orderBy('datetime', 'desc')
                ->limit(50)
                ->get(),
            'rms_reminders' => RmReminder::with(['rental'])
                ->where('due_on', '<', $now->toDateString())
                ->where('status', '!=', 'dismissed')
                ->orderBy('due_on', 'desc')
                ->limit(50)
                ->get(),
        ];
    }

    /**
     * Get today's tasks
     *
     * @return array
     */
    public function getTodaysTasks(): array
    {
        $today = now()->toDateString();

        return [
            'date' => $today,
            'reminders' => UserApiCustomerReminder::with(['user', 'customer'])
                ->whereDate('datetime', $today)
                ->orderBy('priority', 'desc')
                ->orderBy('datetime', 'asc')
                ->get(),
            'appointments' => UserApiCustomerAppointment::with(['user', 'customer'])
                ->whereDate('datetime', $today)
                ->orderBy('priority', 'desc')
                ->orderBy('datetime', 'asc')
                ->get(),
            'rms_reminders' => RmReminder::with(['rental'])
                ->whereDate('due_on', $today)
                ->where('status', '!=', 'dismissed')
                ->orderBy('due_on', 'asc')
                ->get(),
        ];
    }

    /**
     * Get today's summary
     *
     * @param string $date
     * @return array
     */
    protected function getTodaysSummary(string $date): array
    {
        $targetDate = Carbon::parse($date);

        $remindersCount = UserApiCustomerReminder::whereDate('datetime', $targetDate)->count();
        $appointmentsCount = UserApiCustomerAppointment::whereDate('datetime', $targetDate)->count();
        $rmsRemindersCount = RmReminder::whereDate('due_on', $targetDate)->where('status', '!=', 'dismissed')->count();

        return [
            'total_tasks' => $remindersCount + $appointmentsCount + $rmsRemindersCount,
            'reminders' => $remindersCount,
            'appointments' => $appointmentsCount,
            'rms_reminders' => $rmsRemindersCount,
            'high_priority_count' => $this->getHighPriorityCount($targetDate),
        ];
    }

    /**
     * Get upcoming items for next N days
     *
     * @param string $startDate
     * @param int $days
     * @return array
     */
    protected function getUpcomingItems(string $startDate, int $days = 7): array
    {
        $start = Carbon::parse($startDate);
        $end = $start->copy()->addDays($days);

        return [
            'reminders' => UserApiCustomerReminder::whereBetween('datetime', [$start, $end])
                ->count(),
            'appointments' => UserApiCustomerAppointment::whereBetween('datetime', [$start, $end])
                ->count(),
            'rms_reminders' => RmReminder::whereBetween('due_on', [$start->toDateString(), $end->toDateString()])
                ->where('status', '!=', 'dismissed')
                ->count(),
        ];
    }

    /**
     * Get overdue count
     *
     * @return int
     */
    protected function getOverdueCount(): int
    {
        $now = now();

        $reminders = UserApiCustomerReminder::where('datetime', '<', $now)->count();
        $appointments = UserApiCustomerAppointment::where('datetime', '<', $now)->count();
        $rmsReminders = RmReminder::where('due_on', '<', $now->toDateString())
            ->where('status', '!=', 'dismissed')
            ->count();

        return $reminders + $appointments + $rmsReminders;
    }

    /**
     * Get high priority count for date
     *
     * @param Carbon $date
     * @return int
     */
    protected function getHighPriorityCount(Carbon $date): int
    {
        $reminders = UserApiCustomerReminder::whereDate('datetime', $date)->where('priority', 3)->count();
        $appointments = UserApiCustomerAppointment::whereDate('datetime', $date)->where('priority', 3)->count();

        return $reminders + $appointments;
    }

    /**
     * Get appointments by type for date
     *
     * @param Carbon $date
     * @return array
     */
    protected function getAppointmentsByType(Carbon $date): array
    {
        return UserApiCustomerAppointment::whereDate('datetime', $date)
            ->selectRaw('type, COUNT(*) as count')
            ->groupBy('type')
            ->pluck('count', 'type')
            ->toArray();
    }

    /**
     * Get RMS reminders by type for date
     *
     * @param Carbon $date
     * @return array
     */
    protected function getRmsRemindersByType(Carbon $date): array
    {
        return RmReminder::whereDate('due_on', $date)
            ->selectRaw('type, COUNT(*) as count')
            ->groupBy('type')
            ->pluck('count', 'type')
            ->toArray();
    }

    /**
     * Get RMS reminders by status for date
     *
     * @param Carbon $date
     * @return array
     */
    protected function getRmsRemindersByStatus(Carbon $date): array
    {
        return RmReminder::whereDate('due_on', $date)
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();
    }

    /**
     * Get subscriptions expiring soon
     *
     * @param int $days
     * @return int
     */
    protected function getSubscriptionsExpiringSoon(int $days = 7): int
    {
        $start = now()->toDateString();
        $end = now()->addDays($days)->toDateString();

        return Membership::where('status', 1)
            ->whereBetween('expire_date', [$start, $end])
            ->count();
    }

    /**
     * Get subscriptions expired today
     *
     * @param Carbon $date
     * @return int
     */
    protected function getSubscriptionsExpiredToday(Carbon $date): int
    {
        return Membership::whereDate('expire_date', $date->toDateString())
            ->where('status', 1)
            ->count();
    }
}

