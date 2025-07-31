<?php

namespace App\Services\Rms;

use App\Models\Api\Rms\RmRental;
use App\Models\Api\Rms\RmContract;
use App\Models\Api\Rms\RmPaymentInstallment;
use App\Models\Api\Rms\RmMaintenanceTicket;
use App\Models\Api\Rms\RmReminder;
use Illuminate\Support\Carbon;

class DashboardService
{
    public function getDashboardData($userId, $range = 7)
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
            'ongoing_rentals' => $this->getOngoingRentals($userId),
            'reminders' => RmReminder::where('user_id', $userId)
                ->where('status', 'pending')
                ->whereBetween('due_on', [$now, $end])
                ->orderBy('due_on')
                ->take(5)
                ->get(),
            'maintenance' => RmMaintenanceTicket::where('user_id', $userId)
                ->whereIn('status', ['open', 'in_progress'])
                ->orderBy('scheduled_date')
                ->take(5)
                ->get()
        ];
    }

    protected function getOngoingRentals($userId)
    {
        return RmRental::with(['property:id,name', 'contracts' => function ($q) {
                $q->where('status', 'active');
            }])
            ->where('user_id', $userId)
            ->where('status', 'active')
            ->get()
            ->map(function ($rental) {
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
                        'name' => optional($rental->property)->name,
                        'unit_label' => $rental->unit_label,
                    ],
                    'contract' => [
                        'id' => optional($rental->contracts->first())->id,
                        'end_date' => optional($rental->contracts->first())->end_date,
                        'status' => optional($rental->contracts->first())->status,
                    ],
                    'next_payment_due_on' => optional($nextPayment)->due_date,
                    'next_payment_amount' => optional($nextPayment)->amount,
                ];
            });
    }
}
