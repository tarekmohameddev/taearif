<?php

namespace App\Domain\Daily\Services;

use App\Domain\Shared\Services\BaseService;
use App\Models\Api\Rms\RmReminder;
use App\Models\Api\UserApiCustomerReminder;
use App\Models\Api\UserApiCustomerAppointment;
use App\Models\Membership;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Query\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

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

        // If user_id filter is provided, include both that user's reminders AND default reminders
        if (isset($filters['user_id'])) {
            $query->where(function($q) use ($filters) {
                $q->whereNull('user_id')  // Default reminders
                  ->orWhere('user_id', $filters['user_id']);  // User's own reminders
            });
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

        /** @var LengthAwarePaginator $paginated */
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

        /** @var LengthAwarePaginator $paginated */
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

        /** @var LengthAwarePaginator $paginated */
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

    /**
     * Build the daily follow-up payload (cards + tables).
     */
    public function getFollowUpOverview(int $windowDays = 30, int $tableLimit = 50): array
    {
        $windowDays = max(1, min(90, $windowDays));
        $tableLimit = max(1, min(100, $tableLimit));

        $context = $this->makeFollowUpContext($windowDays);

        $expiringSoonQuery = $this->baseTenantQuery($context)
            ->whereNotNull('m.id')
            ->where('m.status', 1)
            ->whereBetween('m.expire_date', [
                $context['today']->toDateString(),
                $context['future_boundary']->toDateString(),
            ]);

        $inTrialQuery = $this->baseTenantQuery($context)
            ->whereNotNull('m.id')
            ->where('m.is_trial', 1)
            ->whereDate('m.expire_date', '>=', $context['today']->toDateString())
            ->whereNotExists(function ($sub) {
                $sub->select(DB::raw(1))
                    ->from('memberships as m2')
                    ->whereColumn('m2.user_id', 'u.id')
                    ->where('m2.is_trial', 0)
                    ->where('m2.status', 1);
            });

        $expiredNotRenewedQuery = $this->baseTenantQuery($context)
            ->whereNotNull('m.id')
            ->whereDate('m.expire_date', '<', $context['today']->toDateString())
            ->whereDate('m.expire_date', '>=', $context['look_back_boundary']->toDateString())
            ->whereNotExists(function ($sub) use ($context) {
                $sub->select(DB::raw(1))
                    ->from('memberships as m3')
                    ->whereColumn('m3.user_id', 'u.id')
                    ->where('m3.status', 1)
                    ->whereDate('m3.expire_date', '>=', $context['today']->toDateString());
            });

        $noContentQuery = $this->baseTenantQuery($context)
            ->whereNotExists(function ($sub) {
                $sub->select(DB::raw(1))
                    ->from('user_properties as up')
                    ->whereColumn('up.user_id', 'u.id');
            })
            ->selectSub(function ($sub) {
                $sub->from('user_properties as up')
                    ->selectRaw('COUNT(*)')
                    ->whereColumn('up.user_id', 'u.id');
            }, 'properties_count')
            ->selectSub(function ($sub) {
                $sub->from('user_projects as upj')
                    ->selectRaw('COUNT(*)')
                    ->whereColumn('upj.user_id', 'u.id');
            }, 'projects_count');

        return [
            'follow_up' => [
                'cards' => $this->buildFollowUpCards(
                    $expiringSoonQuery,
                    $inTrialQuery,
                    $expiredNotRenewedQuery,
                    $noContentQuery,
                    $windowDays
                ),
                'tables' => [
                    'expiring_soon' => $this->buildTenantTable($expiringSoonQuery, $context, $tableLimit),
                    'in_trial_not_subscribed' => $this->buildTenantTable($inTrialQuery, $context, $tableLimit),
                    'expired_not_renewed' => $this->buildTenantTable(
                        $expiredNotRenewedQuery,
                        $context,
                        $tableLimit,
                        'm.expire_date',
                        'desc'
                    ),
                    'no_content' => $this->buildTenantTable(
                        $noContentQuery,
                        $context,
                        $tableLimit,
                        'u.created_at',
                        'desc',
                        function (array $payload, object $row) {
                            $payload['metrics'] = [
                                'properties' => (int) ($row->properties_count ?? 0),
                                'projects' => (int) ($row->projects_count ?? 0),
                            ];

                            return $payload;
                        }
                    ),
                    'reminders' => $this->buildReminderTable($context, $windowDays, $tableLimit),
                ],
            ],
        ];
    }

    /**
     * Prepare shared context (dates + reusable subqueries).
     */
    protected function makeFollowUpContext(int $windowDays): array
    {
        $today = Carbon::today();

        return [
            'today' => $today,
            'future_boundary' => $today->copy()->addDays($windowDays)->endOfDay(),
            'look_back_boundary' => $today->copy()->subDays($windowDays)->startOfDay(),
            'latest_membership_sub' => DB::table('memberships as m1')
                ->select('m1.user_id', DB::raw('MAX(m1.id) as latest_id'))
                ->groupBy('m1.user_id'),
            'latest_contact_sub' => DB::table('lead_activities as la')
                ->join('leads as l', 'l.id', '=', 'la.lead_id')
                ->whereNotNull('l.converted_user_id')
                ->selectRaw('l.converted_user_id as user_id')
                ->selectRaw('MAX(COALESCE(la.completed_at, la.scheduled_at)) as last_contact_at')
                ->groupBy('l.converted_user_id'),
        ];
    }

    /**
     * Base tenant query used by all follow-up segments.
     */
    protected function baseTenantQuery(array $context): Builder
    {
        return DB::table('users as u')
            ->leftJoinSub($context['latest_membership_sub'], 'lm', 'lm.user_id', '=', 'u.id')
            ->leftJoin('memberships as m', 'm.id', '=', 'lm.latest_id')
            ->leftJoin('packages as p', 'p.id', '=', 'm.package_id')
            ->leftJoinSub($context['latest_contact_sub'], 'lc', 'lc.user_id', '=', 'u.id')
            ->where('u.account_type', 'tenant')
            ->whereNull('u.deleted_at')
            ->select([
                'u.id as user_id',
                'u.first_name',
                'u.last_name',
                'u.email',
                'u.phone',
                'u.created_at as registered_at',
                'm.id as membership_id',
                'm.status as membership_status',
                'm.is_trial',
                'm.start_date',
                'm.expire_date',
                'p.title as package_title',
                'p.price as package_price',
                'lc.last_contact_at',
            ]);
    }

    /**
     * Build the summary cards.
     */
    protected function buildFollowUpCards(
        Builder $expiringSoon,
        Builder $inTrial,
        Builder $expired,
        Builder $noContent,
        int $windowDays
    ): array {
        return [
            'expiring_soon' => [
                'total' => $this->countBuilder($expiringSoon),
                'window_days' => $windowDays,
                'description' => "الاشتراكات التي تنتهي خلال {$windowDays} يوم",
            ],
            'in_trial_not_subscribed' => [
                'total' => $this->countBuilder($inTrial),
                'window_days' => $windowDays,
                'description' => 'مستخدمو التجربة المجانية الذين لم يشتركوا بعد',
            ],
            'expired_not_renewed' => [
                'total' => $this->countBuilder($expired),
                'window_days' => $windowDays,
                'description' => "اشتراكات انتهت خلال آخر {$windowDays} يوم",
            ],
            'no_content' => [
                'total' => $this->countBuilder($noContent),
                'description' => 'مستخدمون بدون عقارات أو مشاريع مضافة',
            ],
        ];
    }

    /**
     * Build a tenant table (shared mapper + optional row mutator).
     */
    protected function buildTenantTable(
        Builder $query,
        array $context,
        int $limit,
        string $orderColumn = 'm.expire_date',
        string $direction = 'asc',
        ?callable $rowMutator = null
    ): array {
        $baseQuery = clone $query;

        return [
            'total' => $this->countBuilder($baseQuery),
            'items' => $query
                ->orderBy($orderColumn, $direction)
                ->limit($limit)
                ->get()
                ->map(function ($row) use ($context, $rowMutator) {
                    $payload = $this->formatTenantRow($row, $context['today']);

                    return $rowMutator ? $rowMutator($payload, $row) : $payload;
                })
                ->values()
                ->toArray(),
        ];
    }

    /**
     * Normalize tenant rows for UI consumption.
     */
    protected function formatTenantRow(object $row, Carbon $today): array
    {
        $expireDate = $row->expire_date ? Carbon::parse($row->expire_date) : null;
        return [
            'user' => [
                'id' => (int) $row->user_id,
                'name' => trim(($row->first_name ?? '') . ' ' . ($row->last_name ?? '')) ?: $row->email,
                'email' => $row->email,
                'phone' => $row->phone,
            ],
            'package' => [
                'title' => $row->package_title,
                'price' => $row->package_price !== null ? (float) $row->package_price : null,
            ],
            'membership' => [
                'start_date' => $row->start_date,
                'expire_date' => $expireDate?->toDateString(),
                'days_remaining' => $expireDate ? $today->diffInDays($expireDate, false) : null,
                'days_overdue' => ($expireDate && $expireDate->lt($today))
                    ? $expireDate->diffInDays($today)
                    : null,
                'is_trial' => (bool) $row->is_trial,
            ],
        ];
    }

    /**
     * Build the reminders table (lead activities).
     */
    protected function buildReminderTable(array $context, int $windowDays, int $limit): array
    {
        $query = DB::table('lead_activities as la')
            ->join('leads as l', 'l.id', '=', 'la.lead_id')
            ->leftJoin('admin_crm_cards as s', 's.id', '=', 'l.stage_id')
            ->leftJoin('admins as a', 'a.id', '=', 'la.admin_id')
            ->whereIn('la.type', ['call', 'email', 'meeting', 'note', 'other'])
            ->whereNull('la.completed_at')
            ->whereNotNull('la.scheduled_at')
            ->whereBetween('la.scheduled_at', [
                $context['today']->copy()->subDays($windowDays)->startOfDay(),
                $context['future_boundary'],
            ])
            ->select([
                'la.id',
                'la.type',
                'la.description',
                'la.scheduled_at',
                'l.id as lead_id',
                'l.name as lead_name',
                'l.email as lead_email',
                'l.phone as lead_phone',
                's.name as stage_name',
                's.slug as stage_slug',
                's.color as stage_color',
                'a.id as admin_id',
                DB::raw("CONCAT(a.first_name, ' ', a.last_name) as admin_name"),
            ]);

        $base = clone $query;

        return [
            'total' => $base->count(),
            'items' => $query
                ->orderBy('la.scheduled_at')
                ->limit($limit)
                ->get()
                ->map(fn ($row) => $this->formatReminderRow($row, $context['today']))
                ->values()
                ->toArray(),
        ];
    }

    /**
     * Normalize reminder rows.
     */
    protected function formatReminderRow(object $row, Carbon $today): array
    {
        $scheduledAt = Carbon::parse($row->scheduled_at);

        $labelMap = [
            'meeting' => 'اجتماع',
            'call' => 'مكالمة',
            'email' => 'بريد إلكتروني',
            'note' => 'ملاحظة',
            'other' => 'أخرى',
        ];

        $priority = match ($row->type) {
            'meeting' => ['code' => 'high', 'label' => 'عالي', 'badge' => 'danger'],
            'call' => ['code' => 'medium', 'label' => 'متوسط', 'badge' => 'warning'],
            default => ['code' => 'low', 'label' => 'منخفض', 'badge' => 'info'],
        };

        return [
            'id' => (int) $row->id,
            'lead' => [
                'id' => (int) $row->lead_id,
                'name' => $row->lead_name,
                'email' => $row->lead_email,
                'phone' => $row->lead_phone,
                'stage' => [
                    'name' => $row->stage_name,
                    'slug' => $row->stage_slug,
                    'color' => $row->stage_color,
                ],
            ],
            'owner' => [
                'id' => $row->admin_id ? (int) $row->admin_id : null,
                'name' => $row->admin_name,
            ],
            'title' => $row->description,
            'type' => [
                'code' => $row->type,
                'label' => $labelMap[$row->type] ?? ucfirst($row->type),
            ],
            'priority' => $priority,
            'scheduled_at' => [
                'date' => $scheduledAt->toDateString(),
                'time' => $scheduledAt->format('H:i'),
                'iso' => $scheduledAt->toIso8601String(),
            ],
            'status' => [
                'code' => $scheduledAt->isPast() ? 'overdue' : 'pending',
                'label' => $scheduledAt->isPast() ? 'متأخر' : 'قيد الانتظار',
                'badge' => $scheduledAt->isPast() ? 'danger' : 'warning',
            ],
        ];
    }

    /**
     * Clone and count a query builder safely for static analysis.
     */
    protected function countBuilder(Builder $builder): int
    {
        $clone = clone $builder;

        return $clone->count();
    }
}

