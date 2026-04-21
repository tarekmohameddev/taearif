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
    public function getDashboardData($userId, $range = 7, array $filters = [])
    {
        $now = Carbon::now('Asia/Riyadh');
        $end = $now->copy()->addDays($range);

        // Month boundaries for new count fields
        $currentMonthStart = $now->copy()->startOfMonth();
        $currentMonthEnd = $now->copy()->endOfMonth();
        $nextMonthStart = $now->copy()->addMonth()->startOfMonth();
        $nextMonthEnd = $now->copy()->addMonth()->endOfMonth();

        // Check if collections or payments due filters are applied
        $collectionsFilters = $filters['collections'] ?? [];
        $paymentsDueFilters = $filters['payments_due'] ?? [];

        $hasCollectionsFilter = $this->hasFilters($collectionsFilters);
        $hasPaymentsDueFilter = $this->hasFilters($paymentsDueFilters);

        // Build base response
        $response = [
            'counts' => [
                'ongoing_rentals' => RmRental::where('user_id', $userId)->where('status', 'active')->count(),
                'expiring_contracts_next_' . $range . 'd' => RmContract::where('user_id', $userId)
                    ->where('status', 'active')
                    ->whereDate('end_date', '<=', $end)
                    ->whereDate('end_date', '>=', $now)
                    ->count(),
                'expiring_contracts_current_month' => RmContract::where('user_id', $userId)
                    ->where('status', 'active')
                    ->whereBetween('end_date', [$currentMonthStart, $currentMonthEnd])
                    ->count(),
                'expiring_contracts_next_month' => RmContract::where('user_id', $userId)
                    ->where('status', 'active')
                    ->whereBetween('end_date', [$nextMonthStart, $nextMonthEnd])
                    ->count(),
                'payments_due_next_' . $range . 'd' => RmPaymentInstallment::where('user_id', $userId)
                    ->where('status', 'pending')
                    ->whereBetween('due_date', [$now, $end])
                    ->count(),
                'payments_due_current_month' => RmPaymentInstallment::where('user_id', $userId)
                    ->where('status', 'pending')
                    ->whereBetween('due_date', [$currentMonthStart, $currentMonthEnd])
                    ->count(),
                'payments_due_next_month' => RmPaymentInstallment::where('user_id', $userId)
                    ->where('status', 'pending')
                    ->whereBetween('due_date', [$nextMonthStart, $nextMonthEnd])
                    ->count(),
                'payments_overdue' => RmPaymentInstallment::where('user_id', $userId)
                    ->where('status', 'overdue')
                    ->count(),
                'maintenance_open' => RmMaintenanceTicket::where('user_id', $userId)->where('status', 'open')->count(),
                'maintenance_in_progress' => RmMaintenanceTicket::where('user_id', $userId)->where('status', 'in_progress')->count(),
            ],
            'property_stats' => $this->getPropertyStats($userId),
            'contract_stats' => $this->getContractStats($userId),
            'yearly_overview' => $this->getYearlyFinancialOverview($userId),
            'contracts_expiring' => $this->getContractsExpiring($userId),
            'ongoing_rentals' => $this->getOngoingRentals($userId),
            'expiring_contracts_current_month_details' => $this->getExpiringContractsCurrentMonthDetails($userId, $currentMonthStart, $currentMonthEnd),
            'expiring_contracts_next_month_details' => $this->getExpiringContractsNextMonthDetails($userId, $nextMonthStart, $nextMonthEnd),
            'overdue_payments_details' => $this->getOverduePaymentsDetails($userId, $now),
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

        // Handle collections - filtered or default
        if ($hasCollectionsFilter) {
            $response['collections_filtered'] = $this->getFilteredPaymentsCollections($userId, $collectionsFilters);
        } else {
            $response['monthly_collections'] = $this->getRentalAmounts($userId);
        }

        // Handle payments due - filtered or default
        if ($hasPaymentsDueFilter) {
            $response['payments_due_filtered'] = $this->getFilteredPaymentsDue($userId, $paymentsDueFilters);
        } else {
            $response['payments_due_next_month_details'] = $this->getPaymentsDueNextMonthDetails($userId, $nextMonthStart, $nextMonthEnd);
            $response['payments_due_current_month_details'] = $this->getPaymentsDueCurrentMonthDetails($userId, $currentMonthStart, $currentMonthEnd);
        }

        // Add applied filters metadata
        if ($hasCollectionsFilter || $hasPaymentsDueFilter) {
            $response['applied_filters'] = [
                'collections' => $hasCollectionsFilter ? $collectionsFilters : null,
                'payments_due' => $hasPaymentsDueFilter ? $paymentsDueFilters : null,
            ];
        }

        return $response;
    }

    /**
     * Calculate date range based on period and optional from/to dates
     * Quick filters set the base range, then from/to can narrow it
     *
     * @param string|null $period this_week, this_month, this_year, custom
     * @param string|null $fromDate
     * @param string|null $toDate
     * @return array ['start' => Carbon, 'end' => Carbon, 'label' => string]
     */
    public function calculateDateRange(?string $period, ?string $fromDate = null, ?string $toDate = null): array
    {
        $now = Carbon::now('Asia/Riyadh');
        $start = null;
        $end = null;
        $label = 'Custom Range';

        // First, determine base range from period
        switch ($period) {
            case 'this_week':
                $start = $now->copy()->startOfWeek(Carbon::SATURDAY); // Week starts Saturday for Saudi
                $end = $now->copy()->endOfWeek(Carbon::FRIDAY);
                $label = 'This Week (' . $start->format('M d') . ' - ' . $end->format('M d, Y') . ')';
                break;

            case 'this_month':
                $start = $now->copy()->startOfMonth();
                $end = $now->copy()->endOfMonth();
                $label = $now->format('F Y');
                break;

            case 'this_year':
                $start = $now->copy()->startOfYear();
                $end = $now->copy()->endOfYear();
                $label = 'Year ' . $now->year;
                break;

            case 'custom':
                // For custom period, from_date and to_date are required
                if ($fromDate && $toDate) {
                    $start = Carbon::parse($fromDate, 'Asia/Riyadh')->startOfDay();
                    $end = Carbon::parse($toDate, 'Asia/Riyadh')->endOfDay();
                    $label = $start->format('M d, Y') . ' - ' . $end->format('M d, Y');
                }
                break;

            default:
                // No period specified, use from/to dates if provided
                if ($fromDate && $toDate) {
                    $start = Carbon::parse($fromDate, 'Asia/Riyadh')->startOfDay();
                    $end = Carbon::parse($toDate, 'Asia/Riyadh')->endOfDay();
                    $label = $start->format('M d, Y') . ' - ' . $end->format('M d, Y');
                }
                break;
        }

        // If period is set AND from/to dates are provided, narrow the range
        if ($period && $period !== 'custom' && $start && $end) {
            if ($fromDate) {
                $customStart = Carbon::parse($fromDate, 'Asia/Riyadh')->startOfDay();
                // Clamp to within period range
                if ($customStart->gt($start)) {
                    $start = $customStart;
                }
            }
            if ($toDate) {
                $customEnd = Carbon::parse($toDate, 'Asia/Riyadh')->endOfDay();
                // Clamp to within period range
                if ($customEnd->lt($end)) {
                    $end = $customEnd;
                }
            }

            // Update label if narrowed
            if ($fromDate || $toDate) {
                $label .= ' (filtered: ' . $start->format('M d') . ' - ' . $end->format('M d') . ')';
            }
        }

        return [
            'start' => $start,
            'end' => $end,
            'label' => $label,
            'period' => $period,
            'from_date' => $fromDate,
            'to_date' => $toDate,
        ];
    }

    /**
     * Check if filters are applied
     */
    protected function hasFilters(?array $filters): bool
    {
        if (!$filters) {
            return false;
        }

        return !empty($filters['period']) || !empty($filters['from_date']) || !empty($filters['to_date']);
    }

    /**
     * Get filtered payments collections data
     * For use in dashboard with filters or separate endpoint
     */
    public function getFilteredPaymentsCollections($userId, array $filters): array
    {
        $dateRange = $this->calculateDateRange(
            $filters['period'] ?? null,
            $filters['from_date'] ?? null,
            $filters['to_date'] ?? null
        );

        $start = $dateRange['start'];
        $end = $dateRange['end'];

        if (!$start || !$end) {
            // Default to current month if no valid range
            $now = Carbon::now('Asia/Riyadh');
            $start = $now->copy()->startOfMonth();
            $end = $now->copy()->endOfMonth();
            $dateRange['label'] = $now->format('F Y') . ' (default)';
        }

        $now = Carbon::now('Asia/Riyadh');

        // Calculate collections for the filtered range
        $totalExpected = RmPaymentInstallment::where('user_id', $userId)
            ->whereBetween('due_date', [$start, $end])
            ->sum('amount');

        $totalCollected = RmPaymentInstallment::where('user_id', $userId)
            ->where('status', 'paid')
            ->whereBetween('paid_at', [$start, $end])
            ->sum('amount');

        $totalPending = RmPaymentInstallment::where('user_id', $userId)
            ->where('status', 'pending')
            ->whereBetween('due_date', [$start, $end])
            ->where('due_date', '>=', $now)
            ->sum('amount');

        $totalOverdue = RmPaymentInstallment::where('user_id', $userId)
            ->whereBetween('due_date', [$start, $end])
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

        // Payment breakdown
        $onTimePayments = RmPaymentInstallment::where('user_id', $userId)
            ->where('status', 'paid')
            ->whereBetween('paid_at', [$start, $end])
            ->whereColumn('paid_at', '<=', 'due_date')
            ->sum('amount');

        $lateButPaidPayments = RmPaymentInstallment::where('user_id', $userId)
            ->where('status', 'paid')
            ->whereBetween('paid_at', [$start, $end])
            ->whereColumn('paid_at', '>', 'due_date')
            ->sum('amount');

        // Get count of properties with active rentals
        $rentedPropertiesCount = Property::where('user_id', $userId)
            ->whereHas('rentals', function ($query) {
                $query->where('status', 'active');
            })
            ->count();

        return [
            'filter_applied' => $dateRange,
            'date_range' => [
                'start_date' => $start->format('Y-m-d'),
                'end_date' => $end->format('Y-m-d'),
                'label' => $dateRange['label'],
            ],
            'summary' => [
                'total_expected' => (float) $totalExpected,
                'total_collected' => (float) $totalCollected,
                'total_pending' => (float) $totalPending,
                'total_overdue' => (float) $totalOverdue,
                'collection_rate' => $collectionRate,
            ],
            'payment_breakdown' => [
                'on_time' => (float) $onTimePayments,
                'late_but_paid' => (float) $lateButPaidPayments,
                'pending_not_due' => (float) $totalPending,
                'overdue' => (float) $totalOverdue,
            ],
            'rented_properties_count' => $rentedPropertiesCount,
            'currency' => 'SAR',
        ];
    }

    /**
     * Get filtered payments due data
     * For use in dashboard with filters or separate endpoint
     */
    public function getFilteredPaymentsDue($userId, array $filters): array
    {
        $dateRange = $this->calculateDateRange(
            $filters['period'] ?? null,
            $filters['from_date'] ?? null,
            $filters['to_date'] ?? null
        );

        $start = $dateRange['start'];
        $end = $dateRange['end'];

        if (!$start || !$end) {
            // Default to current month if no valid range
            $now = Carbon::now('Asia/Riyadh');
            $start = $now->copy()->startOfMonth();
            $end = $now->copy()->endOfMonth();
            $dateRange['label'] = $now->format('F Y') . ' (default)';
        }

        $now = Carbon::now('Asia/Riyadh');

        // Get all payments due in the filtered range with relationships
        $payments = RmPaymentInstallment::with(['rental.property.contents', 'rental.activeContract'])
            ->where('user_id', $userId)
            ->whereBetween('due_date', [$start, $end])
            ->orderBy('due_date')
            ->get()
            ->map(function ($payment) use ($now) {
                $rental = $payment->rental;
                $property = optional($rental)->property;
                $contract = optional($rental)->activeContract;

                return [
                    'payment_id' => $payment->id,
                    'rental_id' => $payment->rental_id,
                    'tenant_name' => optional($rental)->tenant_full_name,
                    'tenant_phone' => optional($rental)->tenant_phone,
                    'tenant_email' => optional($rental)->tenant_email,
                    'property' => [
                        'id' => optional($property)->id,
                        'name' => optional($property)->firstContent ? $property->firstContent->title : null,
                        'unit_label' => optional($property)->unit_label,
                        'address' => optional($property)->address,
                    ],
                    'contract' => [
                        'id' => optional($contract)->id,
                        'start_date' => optional($contract)->start_date,
                        'end_date' => optional($contract)->end_date,
                        'status' => optional($contract)->status,
                    ],
                    'payment_details' => [
                        'amount' => (float) $payment->amount,
                        'due_date' => $payment->due_date->format('Y-m-d'),
                        'currency' => 'SAR',
                        'payment_type' => $payment->payment_type ?? 'monthly_rent',
                        'payment_status' => $payment->status,
                        'paid_date' => $payment->paid_at ? $payment->paid_at->format('Y-m-d') : null,
                        'payment_method' => $payment->payment_method,
                    ],
                    'days_remaining' => $now->diffInDays($payment->due_date, false),
                ];
            });

        // Calculate aggregates
        $totalAmount = $payments->sum('payment_details.amount');
        $paidPayments = $payments->filter(function ($payment) {
            return $payment['payment_details']['payment_status'] === 'paid';
        });
        $pendingPayments = $payments->filter(function ($payment) {
            return $payment['payment_details']['payment_status'] === 'pending';
        });
        $overduePayments = $payments->filter(function ($payment) {
            return $payment['payment_details']['payment_status'] === 'overdue';
        });

        return [
            'filter_applied' => $dateRange,
            'date_range' => [
                'start_date' => $start->format('Y-m-d'),
                'end_date' => $end->format('Y-m-d'),
                'label' => $dateRange['label'],
            ],
            'summary' => [
                'count' => $payments->count(),
                'total_amount' => (float) $totalAmount,
                'paid_count' => $paidPayments->count(),
                'paid_amount' => (float) $paidPayments->sum('payment_details.amount'),
                'pending_count' => $pendingPayments->count(),
                'pending_amount' => (float) $pendingPayments->sum('payment_details.amount'),
                'overdue_count' => $overduePayments->count(),
                'overdue_amount' => (float) $overduePayments->sum('payment_details.amount'),
            ],
            'payments' => $payments->values()->toArray(),
            'currency' => 'SAR',
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
        // Get only rental-purpose properties
        $totalRentalProperties = Property::where('user_id', $userId)
            ->where('purpose', 'rent')
            ->count();

        $rentedProperties = Property::where('user_id', $userId)
            ->where('purpose', 'rent')
            ->whereHas('rentals', function ($query) {
                $query->where('status', 'active');
            })
            ->count();

        $availableProperties = $totalRentalProperties - $rentedProperties;
        $occupancyRate = $totalRentalProperties > 0 ? round(($rentedProperties / $totalRentalProperties) * 100, 2) : 0;

        return [
            'total_properties' => $totalRentalProperties,
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

    /**
     * Get detailed payments due in next month for dialog popups
     */
    protected function getPaymentsDueNextMonthDetails($userId, $nextMonthStart, $nextMonthEnd)
    {
        $now = Carbon::now('Asia/Riyadh');

        // Get all payments due in next month with relationships
        $payments = RmPaymentInstallment::with(['rental.property.contents', 'rental.activeContract'])
            ->where('user_id', $userId)
            ->whereBetween('due_date', [$nextMonthStart, $nextMonthEnd])
            ->orderBy('due_date')
            ->get()
            ->map(function ($payment) use ($now) {
                $rental = $payment->rental;
                $property = optional($rental)->property;
                $contract = optional($rental)->activeContract;

                return [
                    'rental_id' => $payment->rental_id,
                    'tenant_name' => optional($rental)->tenant_full_name,
                    'tenant_phone' => optional($rental)->tenant_phone,
                    'property' => [
                        'id' => optional($property)->id,
                        'name' => optional($property)->firstContent ? $property->firstContent->title : null,
                        'unit_label' => optional($property)->unit_label,
                    ],
                    'contract' => [
                        'id' => optional($contract)->id,
                        'end_date' => optional($contract)->end_date,
                        'status' => optional($contract)->status,
                    ],
                    'payment_details' => [
                        'amount' => (float) $payment->amount,
                        'due_date' => $payment->due_date->format('Y-m-d'),
                        'currency' => 'SAR',
                        'payment_type' => $payment->payment_type ?? 'monthly_rent',
                        'payment_status' => $payment->status,
                        'paid_date' => $payment->paid_at ? $payment->paid_at->format('Y-m-d') : null,
                        'payment_method' => $payment->payment_method,
                    ],
                    'days_remaining' => $now->diffInDays($payment->due_date, false),
                ];
            });

        // Calculate aggregates
        $totalAmount = $payments->sum('payment_details.amount');
        $paidPayments = $payments->filter(function ($payment) {
            return $payment['payment_details']['payment_status'] === 'paid';
        });
        $unpaidPayments = $payments->filter(function ($payment) {
            return $payment['payment_details']['payment_status'] !== 'paid';
        });

        $paidCount = $paidPayments->count();
        $paidAmount = $paidPayments->sum('payment_details.amount');
        $unpaidCount = $unpaidPayments->count();
        $unpaidAmount = $unpaidPayments->sum('payment_details.amount');

        return [
            'month' => $nextMonthStart->format('F Y'),
            'count' => $payments->count(),
            'total_amount' => (float) $totalAmount,
            'paid_count' => $paidCount,
            'paid_amount' => (float) $paidAmount,
            'unpaid_count' => $unpaidCount,
            'unpaid_amount' => (float) $unpaidAmount,
            'payments' => $payments->values()->toArray(),
        ];
    }

    /**
     * Get detailed payments due in current month for dialog popups
     */
    protected function getPaymentsDueCurrentMonthDetails($userId, $currentMonthStart, $currentMonthEnd)
    {
        $now = Carbon::now('Asia/Riyadh');

        // Get all payments due in current month with relationships
        $payments = RmPaymentInstallment::with(['rental.property.contents', 'rental.activeContract'])
            ->where('user_id', $userId)
            ->whereBetween('due_date', [$currentMonthStart, $currentMonthEnd])
            ->orderBy('due_date')
            ->get()
            ->map(function ($payment) use ($now) {
                $rental = $payment->rental;
                $property = optional($rental)->property;
                $contract = optional($rental)->activeContract;

                return [
                    'rental_id' => $payment->rental_id,
                    'tenant_name' => optional($rental)->tenant_full_name,
                    'tenant_phone' => optional($rental)->tenant_phone,
                    'tenant_email' => optional($rental)->tenant_email,
                    'property' => [
                        'id' => optional($property)->id,
                        'name' => optional($property)->firstContent ? $property->firstContent->title : null,
                        'unit_label' => optional($property)->unit_label,
                        'address' => optional($property)->address,
                    ],
                    'contract' => [
                        'id' => optional($contract)->id,
                        'start_date' => optional($contract)->start_date,
                        'end_date' => optional($contract)->end_date,
                        'status' => optional($contract)->status,
                    ],
                    'payment_details' => [
                        'amount' => (float) $payment->amount,
                        'due_date' => $payment->due_date->format('Y-m-d'),
                        'currency' => 'SAR',
                        'payment_type' => $payment->payment_type ?? 'monthly_rent',
                        'payment_status' => $payment->status,
                        'paid_date' => $payment->paid_at ? $payment->paid_at->format('Y-m-d') : null,
                        'payment_method' => $payment->payment_method,
                    ],
                    'days_remaining' => $now->diffInDays($payment->due_date, false),
                ];
            });

        // Calculate aggregates
        $totalAmount = $payments->sum('payment_details.amount');
        $paidPayments = $payments->filter(function ($payment) {
            return $payment['payment_details']['payment_status'] === 'paid';
        });
        $unpaidPayments = $payments->filter(function ($payment) {
            return $payment['payment_details']['payment_status'] !== 'paid';
        });

        $paidCount = $paidPayments->count();
        $paidAmount = $paidPayments->sum('payment_details.amount');
        $unpaidCount = $unpaidPayments->count();
        $unpaidAmount = $unpaidPayments->sum('payment_details.amount');

        return [
            'month' => $currentMonthStart->format('F Y'),
            'count' => $payments->count(),
            'total_amount' => (float) $totalAmount,
            'paid_count' => $paidCount,
            'paid_amount' => (float) $paidAmount,
            'unpaid_count' => $unpaidCount,
            'unpaid_amount' => (float) $unpaidAmount,
            'payments' => $payments->values()->toArray(),
        ];
    }

    /**
     * Get detailed contracts expiring in current month for dialog popups
     */
    protected function getExpiringContractsCurrentMonthDetails($userId, $currentMonthStart, $currentMonthEnd)
    {
        $now = Carbon::now('Asia/Riyadh');

        // Get all contracts expiring in current month with relationships
        $contracts = RmContract::with(['rental.property.contents', 'rental'])
            ->where('user_id', $userId)
            ->where('status', 'active')
            ->whereBetween('end_date', [$currentMonthStart, $currentMonthEnd])
            ->orderBy('end_date')
            ->get()
            ->map(function ($contract) use ($now) {
                $rental = $contract->rental;
                $property = optional($rental)->property;

                // Get next payment due
                $nextPayment = RmPaymentInstallment::where('rental_id', $contract->rental_id)
                    ->where('status', 'pending')
                    ->orderBy('due_date')
                    ->first();

                // Calculate contract duration in months
                $startDate = Carbon::parse($contract->start_date);
                $endDate = Carbon::parse($contract->end_date);
                $durationMonths = $startDate->diffInMonths($endDate);

                return [
                    'rental_id' => $contract->rental_id,
                    'tenant_name' => optional($rental)->tenant_full_name,
                    'tenant_phone' => optional($rental)->tenant_phone,
                    'tenant_email' => optional($rental)->tenant_email,
                    'property' => [
                        'id' => optional($property)->id,
                        'name' => optional($property)->firstContent ? $property->firstContent->title : null,
                        'unit_label' => optional($property)->unit_label,
                        'address' => optional($property)->address,
                    ],
                    'contract' => [
                        'id' => $contract->id,
                        'start_date' => $contract->start_date,
                        'end_date' => $contract->end_date,
                        'status' => $contract->status,
                        'monthly_rent' => (float) optional($rental)->rental_amount ?? 0,
                        'security_deposit' => (float) $contract->security_deposit ?? 0,
                        'contract_duration_months' => $durationMonths,
                    ],
                    'rental_details' => [
                        'rental_amount' => (float) optional($rental)->rental_amount ?? 0,
                        'currency' => 'SAR',
                        'payment_frequency' => optional($rental)->payment_frequency ?? 'monthly',
                        'next_payment_due' => $nextPayment ? $nextPayment->due_date->format('Y-m-d') : null,
                    ],
                    'days_remaining' => $now->diffInDays($contract->end_date, false),
                    'renewal_status' => $contract->renewal_status ?? 'pending',
                ];
            });

        return [
            'month' => $currentMonthStart->format('F Y'),
            'count' => $contracts->count(),
            'contracts' => $contracts->values()->toArray(),
        ];
    }

    /**
     * Get detailed contracts expiring in next month for dialog popups
     */
    protected function getExpiringContractsNextMonthDetails($userId, $nextMonthStart, $nextMonthEnd)
    {
        $now = Carbon::now('Asia/Riyadh');

        // Get all contracts expiring in next month with relationships
        $contracts = RmContract::with(['rental.property.contents', 'rental'])
            ->where('user_id', $userId)
            ->where('status', 'active')
            ->whereBetween('end_date', [$nextMonthStart, $nextMonthEnd])
            ->orderBy('end_date')
            ->get()
            ->map(function ($contract) use ($now) {
                $rental = $contract->rental;
                $property = optional($rental)->property;

                // Get next payment due
                $nextPayment = RmPaymentInstallment::where('rental_id', $contract->rental_id)
                    ->where('status', 'pending')
                    ->orderBy('due_date')
                    ->first();

                // Calculate contract duration in months
                $startDate = Carbon::parse($contract->start_date);
                $endDate = Carbon::parse($contract->end_date);
                $durationMonths = $startDate->diffInMonths($endDate);

                return [
                    'rental_id' => $contract->rental_id,
                    'tenant_name' => optional($rental)->tenant_full_name,
                    'tenant_phone' => optional($rental)->tenant_phone,
                    'tenant_email' => optional($rental)->tenant_email,
                    'property' => [
                        'id' => optional($property)->id,
                        'name' => optional($property)->firstContent ? $property->firstContent->title : null,
                        'unit_label' => optional($property)->unit_label,
                        'address' => optional($property)->address,
                    ],
                    'contract' => [
                        'id' => $contract->id,
                        'start_date' => $contract->start_date,
                        'end_date' => $contract->end_date,
                        'status' => $contract->status,
                        'monthly_rent' => (float) optional($rental)->rental_amount ?? 0,
                        'security_deposit' => (float) $contract->security_deposit ?? 0,
                        'contract_duration_months' => $durationMonths,
                    ],
                    'rental_details' => [
                        'rental_amount' => (float) optional($rental)->rental_amount ?? 0,
                        'currency' => 'SAR',
                        'payment_frequency' => optional($rental)->payment_frequency ?? 'monthly',
                        'next_payment_due' => $nextPayment ? $nextPayment->due_date->format('Y-m-d') : null,
                    ],
                    'days_remaining' => $now->diffInDays($contract->end_date, false),
                    'renewal_status' => $contract->renewal_status ?? 'not_requested',
                ];
            });

        return [
            'month' => $nextMonthStart->format('F Y'),
            'count' => $contracts->count(),
            'contracts' => $contracts->values()->toArray(),
        ];
    }

    /**
     * Get detailed overdue payments organized by time periods
     */
    protected function getOverduePaymentsDetails($userId, $now)
    {
        // Define time periods
        $currentYear = $now->year;
        $yearStart = Carbon::create($currentYear, 1, 1, 0, 0, 0, 'Asia/Riyadh');
        $yearEnd = Carbon::create($currentYear, 12, 31, 23, 59, 59, 'Asia/Riyadh');

        $lastMonthStart = $now->copy()->subMonth()->startOfMonth();
        $lastMonthEnd = $now->copy()->subMonth()->endOfMonth();

        $currentMonthStart = $now->copy()->startOfMonth();
        $currentMonthEnd = $now->copy()->endOfMonth();

        // Helper function to map payment to detailed structure
        $mapPayment = function ($payment) use ($now) {
            $rental = $payment->rental;
            $property = optional($rental)->property;
            $contract = optional($rental)->activeContract;

            $daysOverdue = $now->diffInDays($payment->due_date, false);
            $daysOverdue = abs($daysOverdue); // Make it positive

            // Calculate overdue penalty (e.g., 5% of amount)
            // $overduePenalty = ($payment->amount * 0.05);

            return [
                'rental_id' => $payment->rental_id,
                'tenant_name' => optional($rental)->tenant_full_name,
                'tenant_phone' => optional($rental)->tenant_phone,
                'tenant_email' => optional($rental)->tenant_email,
                'property' => [
                    'id' => optional($property)->id,
                    'name' => optional($property)->firstContent ? $property->firstContent->title : null,
                    'unit_label' => optional($property)->unit_label,
                    'address' => optional($property)->address,
                ],
                'contract' => [
                    'id' => optional($contract)->id,
                    'start_date' => optional($contract)->start_date,
                    'end_date' => optional($contract)->end_date,
                    'status' => optional($contract)->status,
                    'monthly_rent' => (float) optional($rental)->rental_amount ?? 0,
                    'security_deposit' => (float) optional($contract)->security_deposit ?? 0,
                ],
                'payment_details' => [
                    'amount' => (float) $payment->amount,
                    'due_date' => $payment->due_date->format('Y-m-d'),
                    'currency' => 'SAR',
                    'payment_type' => $payment->payment_type ?? 'monthly_rent',
                    'payment_status' => $payment->status,
                    'days_overdue' => $daysOverdue,
                    // 'overdue_penalty' => (float) $overduePenalty,
                ],
                'rental_details' => [
                    'rental_amount' => (float) optional($rental)->rental_amount ?? 0,
                    'currency' => 'SAR',
                    'payment_frequency' => optional($rental)->payment_frequency ?? 'monthly',
                ],
            ];
        };

        // Get all overdue payments for the year
        $yearlyPayments = RmPaymentInstallment::with(['rental.property.contents', 'rental.activeContract'])
            ->where('user_id', $userId)
            ->where('status', 'overdue')
            ->whereYear('due_date', $currentYear)
            ->orderBy('due_date')
            ->get()
            ->map($mapPayment);

        $yearlyTotalAmount = $yearlyPayments->sum('payment_details.amount');

        // Get overdue payments from last month
        $lastMonthPayments = RmPaymentInstallment::with(['rental.property.contents', 'rental.activeContract'])
            ->where('user_id', $userId)
            ->where('status', 'overdue')
            ->whereBetween('due_date', [$lastMonthStart, $lastMonthEnd])
            ->orderBy('due_date')
            ->get()
            ->map($mapPayment);

        $lastMonthTotalAmount = $lastMonthPayments->sum('payment_details.amount');

        // Get overdue payments from current month
        $currentMonthPayments = RmPaymentInstallment::with(['rental.property.contents', 'rental.activeContract'])
            ->where('user_id', $userId)
            ->where('status', 'overdue')
            ->whereBetween('due_date', [$currentMonthStart, $currentMonthEnd])
            ->orderBy('due_date')
            ->get()
            ->map($mapPayment);

        $currentMonthTotalAmount = $currentMonthPayments->sum('payment_details.amount');

        // Calculate total overdue (all time, not just current year)
        $totalOverdueCount = RmPaymentInstallment::where('user_id', $userId)
            ->where('status', 'overdue')
            ->count();

        $totalOverdueAmount = RmPaymentInstallment::where('user_id', $userId)
            ->where('status', 'overdue')
            ->sum('amount');

        return [
            'total_overdue_count' => $totalOverdueCount,
            'total_overdue_amount' => (float) $totalOverdueAmount,
            'yearly_overview' => [
                'year' => $currentYear,
                'count' => $yearlyPayments->count(),
                'total_amount' => (float) $yearlyTotalAmount,
                'payments' => $yearlyPayments->values()->toArray(),
            ],
            'last_month' => [
                'month' => $lastMonthStart->format('F Y'),
                'count' => $lastMonthPayments->count(),
                'total_amount' => (float) $lastMonthTotalAmount,
                'payments' => $lastMonthPayments->values()->toArray(),
            ],
            'current_month' => [
                'month' => $currentMonthStart->format('F Y'),
                'count' => $currentMonthPayments->count(),
                'total_amount' => (float) $currentMonthTotalAmount,
                'payments' => $currentMonthPayments->values()->toArray(),
            ],
        ];
    }

    /**
     * Sales / portfolio stats for RMS dashboard cards (إجمالي المبيعات، للبيع، للإيجار، نسبة الإشغال).
     * Occupancy counts only active rentals whose end_date has not passed (guards stale status rows).
     */
    public function getSalesStats(int $userId): array
    {
        $today = Carbon::now('Asia/Riyadh')->toDateString();

        $complete = fn () => Property::where('user_id', $userId)->where('completion_status', 'complete');

        $totalSalesValue = (float) $complete()->sum('price');

        $forSaleCount = (int) $complete()->where('purpose', 'sale')->count();
        $forSaleValue = (float) $complete()->where('purpose', 'sale')->sum('price');

        $forRentCount = (int) $complete()->where('purpose', 'rent')->count();
        $forRentValue = (float) $complete()->where('purpose', 'rent')->sum('price');

        $soldCount = (int) $complete()->where('purpose', 'sold')->count();
        $soldValue = (float) $complete()->where('purpose', 'sold')->sum('price');

        $rentedCount = (int) $complete()->where('purpose', 'rented')->count();
        $rentedValue = (float) $complete()->where('purpose', 'rented')->sum('price');

        $forSalePercentage = $totalSalesValue > 0
            ? round(($forSaleValue / $totalSalesValue) * 100, 2)
            : 0.0;
        $forRentPercentage = $totalSalesValue > 0
            ? round(($forRentValue / $totalSalesValue) * 100, 2)
            : 0.0;

        $occupiedCount = (int) Property::where('user_id', $userId)
            ->where('purpose', 'rent')
            ->where('completion_status', 'complete')
            ->whereHas('rentals', function ($query) use ($today) {
                $query->where('status', 'active')
                    ->whereNotNull('end_date')
                    ->whereDate('end_date', '>=', $today);
            })
            ->count();

        $occupancyPercentage = $forRentCount > 0
            ? round(($occupiedCount / $forRentCount) * 100, 2)
            : 0.0;

        return [
            'total_sales' => [
                'value' => $totalSalesValue,
            ],
            'for_sale' => [
                'count' => $forSaleCount,
                'value' => $forSaleValue,
                'percentage' => $forSalePercentage,
            ],
            'for_rent' => [
                'count' => $forRentCount,
                'value' => $forRentValue,
                'percentage' => $forRentPercentage,
            ],
            'sold' => [
                'count' => $soldCount,
                'value' => $soldValue,
            ],
            'rented' => [
                'count' => $rentedCount,
                'value' => $rentedValue,
            ],
            'occupancy' => [
                'occupied_count' => $occupiedCount,
                'total_count' => $forRentCount,
                'percentage' => $occupancyPercentage,
            ],
        ];
    }
}
