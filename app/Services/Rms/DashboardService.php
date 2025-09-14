<?php

namespace App\Services\Rms;

use App\Models\Api\Rms\RmRental;
use App\Models\Api\Rms\RmContract;
use App\Models\Api\Rms\RmPaymentInstallment;
use App\Models\Api\Rms\RmMaintenanceTicket;
use App\Models\Api\Rms\RmReminder;
use App\Models\User\RealestateManagement\Property;
use Illuminate\Support\Carbon;

class DashboardService
{
    public function getDashboardData($userId, $range = 7, $perPage = 10, $page = 1)
    {
        $now = Carbon::now('Asia/Riyadh');
        $end = $now->copy()->addDays($range);

        return [
            'counts' => [
                'ongoing_rentals' => RmRental::where('user_id', $userId)->where('status', 'active')->count(),
                'expiring_contracts_next_30d' => RmContract::where('user_id', $userId)
                    ->where('status', 'active')
                    ->whereDate('end_date', '<=', $now->copy()->addDays(30))
                    ->count(),
                'payments_due_next_' . $range . 'd' => RmPaymentInstallment::where('user_id', $userId)
                    ->where('status', 'pending')
                    ->whereBetween('due_date', [$now, $end])
                    ->count(),
                'payments_overdue' => RmPaymentInstallment::where('user_id', $userId)
                    ->where('status', 'overdue')
                    ->count(),
                'maintenance_open' => RmMaintenanceTicket::where('user_id', $userId)->where('status', 'open')->count(),
                'maintenance_in_progress' => RmMaintenanceTicket::where('user_id', $userId)->where('status', 'in_progress')->count(),
            ],
            'rental_amounts' => $this->getRentalAmounts($userId),
            'ongoing_rentals' => $this->getOngoingRentals($userId, $perPage, $page),
            'reminders' => $this->getReminders($userId, $now, $end, $perPage, $page),
            'maintenance' => $this->getMaintenanceTickets($userId, $perPage, $page)
        ];
    }

    protected function getOngoingRentals($userId, $perPage = 10, $page = 1)
    {
        $rentals = RmRental::with(['property.contents', 'activeContract'])
            ->where('user_id', $userId)
            ->where('status', 'active')
            ->paginate($perPage, ['*'], 'page', $page);

        $formattedRentals = collect($rentals->items())->map(function ($rental) {
            $nextPayment = RmPaymentInstallment::where('rental_id', $rental->id)
                ->where('status', 'pending')
                ->orderBy('due_date')
                ->first();

            return [
                'id' => $rental->id,
                'tenant_name' => $rental->tenant_full_name,
                'tenant_phone' => $rental->tenant_phone,
                'property' => [
                    'id' => $rental->property_id,
                    'name' => optional($rental->property->firstContent)->title,
                    'unit_label' => $rental->unit_label,
                ],
                'contract' => [
                    'id' => optional($rental->activeContract)->id,
                    'end_date' => optional($rental->activeContract)->end_date,
                    'status' => optional($rental->activeContract)->status,
                ],
                'next_payment_due_on' => optional($nextPayment)->due_date,
                'next_payment_amount' => optional($nextPayment)->amount,
            ];
        });

        return [
            'data' => $formattedRentals,
            'pagination' => [
                'total' => $rentals->total(),
                'per_page' => $rentals->perPage(),
                'current_page' => $rentals->currentPage(),
                'last_page' => $rentals->lastPage(),
                'from' => $rentals->firstItem(),
                'to' => $rentals->lastItem(),
            ]
        ];
    }

    protected function getRentalAmounts($userId)
    {
        $now = Carbon::now('Asia/Riyadh');
        $currentMonthStart = $now->copy()->startOfMonth();
        $currentMonthEnd = $now->copy()->endOfMonth();
        $nextMonthStart = $now->copy()->addMonth()->startOfMonth();
        $nextMonthEnd = $now->copy()->addMonth()->endOfMonth();

        // Get total amount to be collected this month from rented properties
        $totalToCollect = RmPaymentInstallment::where('user_id', $userId)
            ->where('status', 'pending')
            ->whereBetween('due_date', [$currentMonthStart, $currentMonthEnd])
            ->sum('amount');

        // Get total amount already collected this month from rented properties
        $totalCollectedThisMonth = RmPaymentInstallment::where('user_id', $userId)
            ->where('status', 'paid')
            ->whereBetween('paid_at', [$currentMonthStart, $currentMonthEnd])
            ->sum('amount');

        // Get total amount to be collected next month from rented properties
        $totalToCollectNextMonth = RmPaymentInstallment::where('user_id', $userId)
            ->where('status', 'pending')
            ->whereBetween('due_date', [$nextMonthStart, $nextMonthEnd])
            ->sum('amount');

        // Get all due dates for next month's payments
        $nextMonthDueDates = RmPaymentInstallment::where('user_id', $userId)
            ->where('status', 'pending')
            ->whereBetween('due_date', [$nextMonthStart, $nextMonthEnd])
            ->orderBy('due_date')
            ->pluck('due_date')
            ->map(function ($date) {
                return $date->format('Y-m-d');
            })
            ->toArray();

        // Get earliest and latest due dates for next month
        $earliestDueDate = !empty($nextMonthDueDates) ? $nextMonthDueDates[0] : null;
        $latestDueDate = !empty($nextMonthDueDates) ? end($nextMonthDueDates) : null;

        // Get total rental amount from all active rentals
        $totalCollected = RmRental::where('user_id', $userId)
            ->where('status', 'active')
            ->sum('total_rental_amount');

        // Get count of properties with active rentals
        $rentedPropertiesCount = Property::where('user_id', $userId)
            ->whereHas('rentals', function ($query) {
                $query->where('status', 'active');
            })
            ->count();

        return [
            'total_to_collect_this_month' => (float) $totalToCollect,
            'total_collected_this_month' => (float) $totalCollectedThisMonth,
            'total_collected' => (float) $totalCollected,
            'total_to_collect_next_month' => (float) $totalToCollectNextMonth,
            'earliest_due_date_next_month' => $earliestDueDate,
            'latest_due_date_next_month' => $latestDueDate,
            'all_due_dates_next_month' => $nextMonthDueDates,
            'rented_properties_count' => $rentedPropertiesCount,
            'currency' => 'SAR', // Default currency, you might want to make this dynamic
        ];
    }

    protected function getReminders($userId, $now, $end, $perPage = 10, $page = 1)
    {
        $reminders = RmReminder::where('user_id', $userId)
            ->where('status', 'pending')
            ->whereBetween('due_on', [$now, $end])
            ->orderBy('due_on')
            ->paginate($perPage, ['*'], 'page', $page);

        return [
            'data' => $reminders->items(),
            'pagination' => [
                'total' => $reminders->total(),
                'per_page' => $reminders->perPage(),
                'current_page' => $reminders->currentPage(),
                'last_page' => $reminders->lastPage(),
                'from' => $reminders->firstItem(),
                'to' => $reminders->lastItem(),
            ]
        ];
    }

    protected function getMaintenanceTickets($userId, $perPage = 10, $page = 1)
    {
        $maintenance = RmMaintenanceTicket::where('user_id', $userId)
            ->whereIn('status', ['open', 'in_progress'])
            ->orderBy('scheduled_date')
            ->paginate($perPage, ['*'], 'page', $page);

        return [
            'data' => $maintenance->items(),
            'pagination' => [
                'total' => $maintenance->total(),
                'per_page' => $maintenance->perPage(),
                'current_page' => $maintenance->currentPage(),
                'last_page' => $maintenance->lastPage(),
                'from' => $maintenance->firstItem(),
                'to' => $maintenance->lastItem(),
            ]
        ];
    }
}
