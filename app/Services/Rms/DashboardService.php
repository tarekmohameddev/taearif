<?php

namespace App\Services\Rms;

use App\Models\Api\Rms\RmRental;
use App\Models\Api\Rms\RmContract;
use App\Models\Api\Rms\RmPaymentInstallment;
use App\Models\Api\Rms\RmMaintenanceTicket;
use App\Models\Api\Rms\RmReminder;
use App\Models\User\RealestateManagement\Property;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

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
            'property_stats' => $this->getPropertyStats($userId),
            'contract_stats' => $this->getContractStats($userId),
            'monthly_collections' => $this->getRentalAmounts($userId),
            'yearly_overview' => $this->getYearlyFinancialOverview($userId),
            'contracts_expiring' => $this->getContractsExpiring($userId),
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
        return RmRental::with(['property.contents', 'activeContract'])
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
                        'name' => optional($rental->property)->firstContent ? $rental->property->firstContent->title : null,
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
    }

    protected function getExpiringContracts($userId)
    {
        $now = Carbon::now('Asia/Riyadh');
        
        return RmContract::with(['rental.property.contents'])
            ->where('user_id', $userId)
            ->where('status', 'active')
            ->whereDate('end_date', '<=', $now->copy()->addDays(30))
            ->orderBy('end_date')
            ->get()
            ->map(function ($contract) {
                return [
                    'id' => $contract->id,
                    'start_date' => $contract->start_date,
                    'end_date' => $contract->end_date,
                    'status' => $contract->status,
                    'days_until_expiry' => Carbon::now('Asia/Riyadh')->diffInDays($contract->end_date, false),
                    'rental' => [
                        'id' => $contract->rental_id,
                        'tenant_name' => optional($contract->rental)->tenant_full_name,
                        'tenant_phone' => optional($contract->rental)->tenant_phone,
                        'property' => [
                            'id' => optional($contract->rental)->property_id,
                            'name' => optional($contract->rental->property)->firstContent ? $contract->rental->property->firstContent->title : null,
                            'unit_label' => optional($contract->rental)->unit_label,
                        ],
                    ],
                ];
            });
    }

    /**
     * Get property statistics (total, rented, available)
     */
    protected function getPropertyStats($userId)
    {
        $totalProperties = Property::where('user_id', $userId)->count();
        
        $rentedProperties = Property::where('user_id', $userId)
            ->whereHas('rentals', function ($query) {
                $query->where('status', 'active');
            })
            ->count();

        $availableProperties = $totalProperties - $rentedProperties;
        $occupancyRate = $totalProperties > 0 ? round(($rentedProperties / $totalProperties) * 100, 2) : 0;

        return [
            'total_properties' => $totalProperties,
            'rented_properties' => $rentedProperties,
            'available_properties' => $availableProperties,
            'occupancy_rate' => $occupancyRate,
        ];
    }

    /**
     * Get contract statistics and expiring contracts breakdown
     */
    protected function getContractStats($userId)
    {
        $now = Carbon::now('Asia/Riyadh');
        $currentMonthStart = $now->copy()->startOfMonth();
        $currentMonthEnd = $now->copy()->endOfMonth();
        $nextMonthStart = $now->copy()->addMonth()->startOfMonth();
        $nextMonthEnd = $now->copy()->addMonth()->endOfMonth();

        // Basic contract counts
        $totalContracts = RmContract::where('user_id', $userId)->count();
        $activeContracts = RmContract::where('user_id', $userId)->where('status', 'active')->count();
        $terminatedContracts = RmContract::where('user_id', $userId)->where('status', 'terminated')->count();
        $expiredContracts = RmContract::where('user_id', $userId)->where('status', 'expired')->count();

        // Average contract duration
        $avgDuration = RmContract::where('user_id', $userId)
            ->selectRaw('AVG(DATEDIFF(end_date, start_date)) as avg_days')
            ->value('avg_days');

        // Expiring contracts current month
        $expiringCurrentMonth = RmContract::where('user_id', $userId)
            ->where('status', 'active')
            ->whereBetween('end_date', [$currentMonthStart, $currentMonthEnd])
            ->get();

        $currentMonthStats = $this->calculateContractFinancials($expiringCurrentMonth);

        // Expiring contracts next month
        $expiringNextMonth = RmContract::where('user_id', $userId)
            ->where('status', 'active')
            ->whereBetween('end_date', [$nextMonthStart, $nextMonthEnd])
            ->get();

        $nextMonthStats = $this->calculateContractFinancials($expiringNextMonth);

        return [
            'total_contracts' => $totalContracts,
            'active_contracts' => $activeContracts,
            'terminated_contracts' => $terminatedContracts,
            'expired_contracts' => $expiredContracts,
            'average_contract_duration_days' => round($avgDuration ?? 0, 0),
            'expiring_current_month' => [
                'count' => $expiringCurrentMonth->count(),
                'total_amount' => $currentMonthStats['total_value'],
                'amount_collected' => $currentMonthStats['collected'],
                'amount_pending' => $currentMonthStats['pending'],
            ],
            'expiring_next_month' => [
                'count' => $expiringNextMonth->count(),
                'total_amount' => $nextMonthStats['total_value'],
            ],
        ];
    }

    /**
     * Calculate financial stats for a collection of contracts
     */
    protected function calculateContractFinancials($contracts)
    {
        $totalValue = 0;
        $collected = 0;
        $pending = 0;

        foreach ($contracts as $contract) {
            if ($contract->rental) {
                $totalValue += $contract->rental->total_rental_amount ?? 0;
                
                // Get payment stats for this rental
                $paidAmount = RmPaymentInstallment::where('rental_id', $contract->rental_id)
                    ->where('status', 'paid')
                    ->sum('amount');
                
                $pendingAmount = RmPaymentInstallment::where('rental_id', $contract->rental_id)
                    ->whereIn('status', ['pending', 'overdue'])
                    ->sum('amount');
                
                $collected += $paidAmount;
                $pending += $pendingAmount;
            }
        }

        return [
            'total_value' => (float) $totalValue,
            'collected' => (float) $collected,
            'pending' => (float) $pending,
        ];
    }

    /**
     * Get detailed contracts expiring by month
     */
    protected function getContractsExpiring($userId)
    {
        $now = Carbon::now('Asia/Riyadh');
        $currentMonthStart = $now->copy()->startOfMonth();
        $currentMonthEnd = $now->copy()->endOfMonth();
        $nextMonthStart = $now->copy()->addMonth()->startOfMonth();
        $nextMonthEnd = $now->copy()->addMonth()->endOfMonth();

        // Current month expiring contracts
        $currentMonthContracts = RmContract::with(['rental.property.contents'])
            ->where('user_id', $userId)
            ->where('status', 'active')
            ->whereBetween('end_date', [$currentMonthStart, $currentMonthEnd])
            ->orderBy('end_date')
            ->get()
            ->map(function ($contract) {
                $rental = $contract->rental;
                $paidAmount = 0;
                $pendingAmount = 0;

                if ($rental) {
                    $paidAmount = RmPaymentInstallment::where('rental_id', $rental->id)
                        ->where('status', 'paid')
                        ->sum('amount');
                    
                    $pendingAmount = RmPaymentInstallment::where('rental_id', $rental->id)
                        ->whereIn('status', ['pending', 'overdue'])
                        ->sum('amount');
                }

                return [
                    'id' => $contract->id,
                    'start_date' => $contract->start_date,
                    'end_date' => $contract->end_date,
                    'status' => $contract->status,
                    'days_until_expiry' => Carbon::now('Asia/Riyadh')->diffInDays($contract->end_date, false),
                    'total_amount' => optional($rental)->total_rental_amount ?? 0,
                    'amount_collected' => (float) $paidAmount,
                    'amount_pending' => (float) $pendingAmount,
                    'rental' => [
                        'id' => $contract->rental_id,
                        'tenant_name' => optional($rental)->tenant_full_name,
                        'tenant_phone' => optional($rental)->tenant_phone,
                        'property' => [
                            'id' => optional($rental)->property_id,
                            'name' => optional($rental->property)->firstContent ? $rental->property->firstContent->title : null,
                            'unit_label' => optional($rental)->unit_label,
                        ],
                    ],
                ];
            });

        // Next month expiring contracts
        $nextMonthContracts = RmContract::with(['rental.property.contents'])
            ->where('user_id', $userId)
            ->where('status', 'active')
            ->whereBetween('end_date', [$nextMonthStart, $nextMonthEnd])
            ->orderBy('end_date')
            ->get()
            ->map(function ($contract) {
                $rental = $contract->rental;

                return [
                    'id' => $contract->id,
                    'start_date' => $contract->start_date,
                    'end_date' => $contract->end_date,
                    'status' => $contract->status,
                    'days_until_expiry' => Carbon::now('Asia/Riyadh')->diffInDays($contract->end_date, false),
                    'total_amount' => optional($rental)->total_rental_amount ?? 0,
                    'rental' => [
                        'id' => $contract->rental_id,
                        'tenant_name' => optional($rental)->tenant_full_name,
                        'tenant_phone' => optional($rental)->tenant_phone,
                        'property' => [
                            'id' => optional($rental)->property_id,
                            'name' => optional($rental->property)->firstContent ? $rental->property->firstContent->title : null,
                            'unit_label' => optional($rental)->unit_label,
                        ],
                    ],
                ];
            });

        return [
            'current_month' => [
                'count' => $currentMonthContracts->count(),
                'month' => $currentMonthStart->format('F Y'),
                'contracts' => $currentMonthContracts,
            ],
            'next_month' => [
                'count' => $nextMonthContracts->count(),
                'month' => $nextMonthStart->format('F Y'),
                'contracts' => $nextMonthContracts,
            ],
        ];
    }

    protected function getRentalAmounts($userId)
    {
        $now = Carbon::now('Asia/Riyadh');
        $currentMonthStart = $now->copy()->startOfMonth();
        $currentMonthEnd = $now->copy()->endOfMonth();
        $nextMonthStart = $now->copy()->addMonth()->startOfMonth();
        $nextMonthEnd = $now->copy()->addMonth()->endOfMonth();

        // Current Month Calculations
        $currentMonthExpected = RmPaymentInstallment::where('user_id', $userId)
            ->whereBetween('due_date', [$currentMonthStart, $currentMonthEnd])
            ->sum('amount');

        $currentMonthCollected = RmPaymentInstallment::where('user_id', $userId)
            ->where('status', 'paid')
            ->whereBetween('paid_at', [$currentMonthStart, $currentMonthEnd])
            ->sum('amount');

        $currentMonthPending = RmPaymentInstallment::where('user_id', $userId)
            ->where('status', 'pending')
            ->whereBetween('due_date', [$currentMonthStart, $currentMonthEnd])
            ->where('due_date', '>=', $now)
            ->sum('amount');

        $currentMonthOverdue = RmPaymentInstallment::where('user_id', $userId)
            ->whereBetween('due_date', [$currentMonthStart, $currentMonthEnd])
            ->where(function ($query) use ($now) {
                $query->where('status', 'overdue')
                    ->orWhere(function ($q) use ($now) {
                        $q->where('status', 'pending')
                          ->where('due_date', '<', $now);
                    });
            })
            ->sum('amount');

        $currentMonthCollectionRate = $currentMonthExpected > 0 
            ? round(($currentMonthCollected / $currentMonthExpected) * 100, 2) 
            : 0;

        // Payment breakdown for current month
        $onTimePayments = RmPaymentInstallment::where('user_id', $userId)
            ->where('status', 'paid')
            ->whereBetween('paid_at', [$currentMonthStart, $currentMonthEnd])
            ->whereColumn('paid_at', '<=', 'due_date')
            ->sum('amount');

        $lateButPaidPayments = RmPaymentInstallment::where('user_id', $userId)
            ->where('status', 'paid')
            ->whereBetween('paid_at', [$currentMonthStart, $currentMonthEnd])
            ->whereColumn('paid_at', '>', 'due_date')
            ->sum('amount');

        // Next Month Calculations
        $nextMonthExpected = RmPaymentInstallment::where('user_id', $userId)
            ->whereBetween('due_date', [$nextMonthStart, $nextMonthEnd])
            ->sum('amount');

        // Check if any next month payments are already collected (paid in advance)
        $nextMonthCollected = RmPaymentInstallment::where('user_id', $userId)
            ->where('status', 'paid')
            ->whereBetween('due_date', [$nextMonthStart, $nextMonthEnd])
            ->sum('amount');

        $nextMonthPending = RmPaymentInstallment::where('user_id', $userId)
            ->where('status', 'pending')
            ->whereBetween('due_date', [$nextMonthStart, $nextMonthEnd])
            ->sum('amount');

        $nextMonthCollectionRate = $nextMonthExpected > 0 
            ? round(($nextMonthCollected / $nextMonthExpected) * 100, 2) 
            : 0;

        $nextMonthDueDates = RmPaymentInstallment::where('user_id', $userId)
            ->where('status', 'pending')
            ->whereBetween('due_date', [$nextMonthStart, $nextMonthEnd])
            ->orderBy('due_date')
            ->pluck('due_date')
            ->map(function ($date) {
                return $date->format('Y-m-d');
            })
            ->unique()
            ->values()
            ->toArray();

        $earliestDueDate = !empty($nextMonthDueDates) ? $nextMonthDueDates[0] : null;
        $latestDueDate = !empty($nextMonthDueDates) ? end($nextMonthDueDates) : null;

        // Get count of properties with active rentals
        $rentedPropertiesCount = Property::where('user_id', $userId)
            ->whereHas('rentals', function ($query) {
                $query->where('status', 'active');
            })
            ->count();

        return [
            'current_month' => [
                'month' => $currentMonthStart->format('Y-m'),
                'month_name' => $currentMonthStart->format('F Y'),
                'start_date' => $currentMonthStart->format('Y-m-d'),
                'end_date' => $currentMonthEnd->format('Y-m-d'),
                'total_expected' => (float) $currentMonthExpected,
                'total_collected' => (float) $currentMonthCollected,
                'total_pending' => (float) $currentMonthPending,
                'total_overdue' => (float) $currentMonthOverdue,
                'collection_rate' => $currentMonthCollectionRate,
                'payment_breakdown' => [
                    'on_time' => (float) $onTimePayments,
                    'late_but_paid' => (float) $lateButPaidPayments,
                    'pending_not_due' => (float) $currentMonthPending,
                    'overdue' => (float) $currentMonthOverdue,
                ],
            ],
            'next_month' => [
                'month' => $nextMonthStart->format('Y-m'),
                'month_name' => $nextMonthStart->format('F Y'),
                'start_date' => $nextMonthStart->format('Y-m-d'),
                'end_date' => $nextMonthEnd->format('Y-m-d'),
                'total_expected' => (float) $nextMonthExpected,
                'total_collected' => (float) $nextMonthCollected,
                'total_pending' => (float) $nextMonthPending,
                'collection_rate' => $nextMonthCollectionRate,
                'earliest_due_date' => $earliestDueDate,
                'latest_due_date' => $latestDueDate,
                'all_due_dates' => $nextMonthDueDates,
            ],
            'rented_properties_count' => $rentedPropertiesCount,
            'currency' => 'SAR',
        ];
    }

    /**
     * Get comprehensive yearly financial overview
     */
    protected function getYearlyFinancialOverview($userId)
    {
        $now = Carbon::now('Asia/Riyadh');
        $currentYear = $now->year;
        $yearStart = Carbon::create($currentYear, 1, 1, 0, 0, 0, 'Asia/Riyadh');
        $yearEnd = Carbon::create($currentYear, 12, 31, 23, 59, 59, 'Asia/Riyadh');

        // Contract counts for the year
        $totalContracts = RmContract::where('user_id', $userId)
            ->whereYear('created_at', $currentYear)
            ->count();

        $activeContracts = RmContract::where('user_id', $userId)
            ->where('status', 'active')
            ->whereYear('created_at', '<=', $currentYear)
            ->count();

        $terminatedContracts = RmContract::where('user_id', $userId)
            ->where('status', 'terminated')
            ->whereYear('updated_at', $currentYear)
            ->count();

        $expiredContracts = RmContract::where('user_id', $userId)
            ->where('status', 'expired')
            ->whereYear('end_date', $currentYear)
            ->count();

        // Financial summary for the year
        $totalExpected = RmPaymentInstallment::where('user_id', $userId)
            ->whereYear('due_date', $currentYear)
            ->sum('amount');

        $totalCollected = RmPaymentInstallment::where('user_id', $userId)
            ->where('status', 'paid')
            ->whereYear('paid_at', $currentYear)
            ->sum('amount');

        $totalPending = RmPaymentInstallment::where('user_id', $userId)
            ->where('status', 'pending')
            ->whereYear('due_date', $currentYear)
            ->where('due_date', '>=', $now)
            ->sum('amount');

        $totalOverdueLate = RmPaymentInstallment::where('user_id', $userId)
            ->whereYear('due_date', $currentYear)
            ->where(function ($query) use ($now) {
                $query->where('status', 'overdue')
                    ->orWhere(function ($q) use ($now) {
                        $q->where('status', 'pending')
                          ->where('due_date', '<', $now);
                    });
            })
            ->sum('amount');

        $collectionRate = $totalExpected > 0 
            ? round(($totalCollected / $totalExpected) * 100, 2) 
            : 0;

        // Get total contract value for active contracts
        $totalContractValue = RmRental::where('user_id', $userId)
            ->where('status', 'active')
            ->whereHas('contracts', function ($query) {
                $query->where('status', 'active');
            })
            ->sum('total_rental_amount');

        // Average monthly collection (only for completed months)
        $currentMonth = $now->month;
        $avgMonthlyCollection = $currentMonth > 0 
            ? round($totalCollected / $currentMonth, 2) 
            : 0;

        // Monthly breakdown
        $monthlyBreakdown = $this->getMonthlyBreakdown($userId, $currentYear, $now);

        // Quarterly breakdown
        $quarterlyBreakdown = $this->getQuarterlyBreakdown($monthlyBreakdown);

        // Late payments analysis
        $latePayments = $this->getLatePaymentsAnalysis($userId, $currentYear, $now);

        return [
            'year' => $currentYear,
            'summary' => [
                'total_contracts' => $totalContracts,
                'active_contracts' => $activeContracts,
                'terminated_contracts' => $terminatedContracts,
                'expired_contracts' => $expiredContracts,
                'total_contract_value' => (float) $totalContractValue,
                'total_expected' => (float) $totalExpected,
                'total_collected' => (float) $totalCollected,
                'total_pending' => (float) $totalPending,
                'total_overdue' => (float) $totalOverdueLate,
                'collection_rate' => $collectionRate,
                'average_monthly_collection' => $avgMonthlyCollection,
            ],
            'monthly_breakdown' => $monthlyBreakdown,
            'quarterly_breakdown' => $quarterlyBreakdown,
            'late_payments' => $latePayments,
        ];
    }

    /**
     * Get monthly breakdown for the year
     */
    protected function getMonthlyBreakdown($userId, $year, $currentDate)
    {
        $breakdown = [];
        $currentMonth = $currentDate->month;
        $monthNames = [
            1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
            5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
            9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
        ];

        // Only process up to current month
        for ($month = 1; $month <= $currentMonth; $month++) {
            $monthStart = Carbon::create($year, $month, 1, 0, 0, 0, 'Asia/Riyadh');
            $monthEnd = $monthStart->copy()->endOfMonth();

            $expected = RmPaymentInstallment::where('user_id', $userId)
                ->whereBetween('due_date', [$monthStart, $monthEnd])
                ->sum('amount');

            $collected = RmPaymentInstallment::where('user_id', $userId)
                ->where('status', 'paid')
                ->whereBetween('paid_at', [$monthStart, $monthEnd])
                ->sum('amount');

            $pending = RmPaymentInstallment::where('user_id', $userId)
                ->where('status', 'pending')
                ->whereBetween('due_date', [$monthStart, $monthEnd])
                ->where('due_date', '>=', $currentDate)
                ->sum('amount');

            $overdue = RmPaymentInstallment::where('user_id', $userId)
                ->whereBetween('due_date', [$monthStart, $monthEnd])
                ->where(function ($query) use ($currentDate) {
                    $query->where('status', 'overdue')
                        ->orWhere(function ($q) use ($currentDate) {
                            $q->where('status', 'pending')
                              ->where('due_date', '<', $currentDate);
                        });
                })
                ->sum('amount');

            $collectionRate = $expected > 0 
                ? round(($collected / $expected) * 100, 2) 
                : 0;

            $breakdown[] = [
                'month' => $month,
                'month_name' => $monthNames[$month],
                'expected' => (float) $expected,
                'collected' => (float) $collected,
                'pending' => (float) $pending,
                'overdue' => (float) $overdue,
                'collection_rate' => $collectionRate,
            ];
        }

        return $breakdown;
    }

    /**
     * Get quarterly breakdown from monthly data
     */
    protected function getQuarterlyBreakdown($monthlyBreakdown)
    {
        $quarters = [];
        $quarterNames = ['Q1', 'Q2', 'Q3', 'Q4'];
        $quarterMonths = [
            1 => [1, 2, 3],
            2 => [4, 5, 6],
            3 => [7, 8, 9],
            4 => [10, 11, 12]
        ];

        foreach ($quarterMonths as $quarterNum => $months) {
            $quarterData = array_filter($monthlyBreakdown, function ($item) use ($months) {
                return in_array($item['month'], $months);
            });

            if (!empty($quarterData)) {
                $expected = array_sum(array_column($quarterData, 'expected'));
                $collected = array_sum(array_column($quarterData, 'collected'));
                $pending = array_sum(array_column($quarterData, 'pending'));
                $overdue = array_sum(array_column($quarterData, 'overdue'));
                
                $collectionRate = $expected > 0 
                    ? round(($collected / $expected) * 100, 2) 
                    : 0;

                $quarters[] = [
                    'quarter' => $quarterNames[$quarterNum - 1] . ' ' . Carbon::now('Asia/Riyadh')->year,
                    'months' => $months,
                    'expected' => (float) $expected,
                    'collected' => (float) $collected,
                    'pending' => (float) $pending,
                    'overdue' => (float) $overdue,
                    'collection_rate' => $collectionRate,
                ];
            }
        }

        return $quarters;
    }

    /**
     * Get late payments analysis with severity breakdown
     */
    protected function getLatePaymentsAnalysis($userId, $year, $currentDate)
    {
        $yearStart = Carbon::create($year, 1, 1, 0, 0, 0, 'Asia/Riyadh');
        
        // Get all late payments (overdue or pending past due date)
        $latePayments = RmPaymentInstallment::where('user_id', $userId)
            ->whereYear('due_date', $year)
            ->where('due_date', '<', $currentDate)
            ->where(function ($query) {
                $query->where('status', 'overdue')
                    ->orWhere('status', 'pending');
            })
            ->get();

        $totalAmount = $latePayments->sum('amount');
        $count = $latePayments->count();

        // Calculate average days late
        $totalDaysLate = 0;
        $severity = [
            '1-7_days' => 0,
            '8-30_days' => 0,
            '31-90_days' => 0,
            '90+_days' => 0,
        ];

        foreach ($latePayments as $payment) {
            $daysLate = $currentDate->diffInDays($payment->due_date);
            $totalDaysLate += $daysLate;

            if ($daysLate >= 1 && $daysLate <= 7) {
                $severity['1-7_days'] += $payment->amount;
            } elseif ($daysLate >= 8 && $daysLate <= 30) {
                $severity['8-30_days'] += $payment->amount;
            } elseif ($daysLate >= 31 && $daysLate <= 90) {
                $severity['31-90_days'] += $payment->amount;
            } elseif ($daysLate > 90) {
                $severity['90+_days'] += $payment->amount;
            }
        }

        $averageDaysLate = $count > 0 ? round($totalDaysLate / $count, 0) : 0;

        return [
            'total_amount' => (float) $totalAmount,
            'count' => $count,
            'average_days_late' => $averageDaysLate,
            'by_severity' => [
                '1-7_days' => (float) $severity['1-7_days'],
                '8-30_days' => (float) $severity['8-30_days'],
                '31-90_days' => (float) $severity['31-90_days'],
                '90+_days' => (float) $severity['90+_days'],
            ],
        ];
    }
}
