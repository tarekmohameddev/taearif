<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Models\Membership;
use App\Services\MembershipService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AdminDashboardBusinessMetricsService
{
    public const BUSINESS_TIMEZONE = 'Asia/Riyadh';
    public const SAR_CURRENCY = 'SAR';

    public function snapshot(?CarbonImmutable $clock = null): array
    {
        $clock = ($clock ?? CarbonImmutable::now(self::BUSINESS_TIMEZONE))
            ->setTimezone(self::BUSINESS_TIMEZONE);

        return [
            'asOf' => $clock,
            'timezone' => self::BUSINESS_TIMEZONE,
            'executiveSummary' => array_merge(
                $this->cachedCounts($clock),
                $this->cachedDailyVisits($clock)
            ),
            'financialMetrics' => $this->cachedFinancialMetrics($clock),
        ];
    }

    private function cachedCounts(CarbonImmutable $clock): array
    {
        $key = sprintf(
            'admin_dashboard_business_metrics:counts:%s',
            $clock->format('YmdHi')
        );

        return $this->remember($key, 300, function () use ($clock): array {
            $today = $clock->toDateString();
            [$paidYearly, $paidMonthly] = $this->paidPackageIds();

            $counts = DB::table('users')
                ->selectRaw(
                    "SUM(CASE WHEN account_type = 'tenant' AND deleted_at IS NULL THEN 1 ELSE 0 END) AS registered_tenant_users"
                )
                ->first();

            $subscribers = Membership::query()
                ->join('users', 'users.id', '=', 'memberships.user_id')
                ->where('users.account_type', 'tenant')
                ->whereNull('users.deleted_at')
                ->where('memberships.status', 1)
                ->whereDate('memberships.start_date', '<=', $today)
                ->whereDate('memberships.expire_date', '>=', $today)
                ->whereIn('memberships.package_id', [$paidYearly, $paidMonthly])
                ->distinct()
                ->count('memberships.user_id');

            return [
                'activePaidSubscriberUsers' => $subscribers,
                'registeredTenantUsers' => (int) ($counts->registered_tenant_users ?? 0),
            ];
        });
    }

    private function cachedDailyVisits(CarbonImmutable $clock): array
    {
        $today = $clock->toDateString();
        $key = 'admin_dashboard_business_metrics:visits:' . $today;

        return $this->remember($key, 60, function () use ($today): array {
            $summary = DB::table('dashboard_daily_visits')
                ->where('visited_on', $today)
                ->selectRaw('COUNT(*) AS unique_dashboard_users_today, COUNT(DISTINCT tenant_owner_id) AS unique_tenants_opened_dashboard_today')
                ->first();

            return [
                'uniqueDashboardUsersToday' => (int) ($summary->unique_dashboard_users_today ?? 0),
                'uniqueTenantsOpenedDashboardToday' => (int) ($summary->unique_tenants_opened_dashboard_today ?? 0),
            ];
        });
    }

    private function cachedFinancialMetrics(CarbonImmutable $clock): array
    {
        $key = sprintf(
            'admin_dashboard_business_metrics:financial:%s',
            $clock->format('YmdHi')
        );

        return $this->remember($key, 300, function (): array {
            $inventorySummary = DB::table('user_properties')
                ->selectRaw("
                    CAST(COALESCE(SUM(CASE WHEN project_id IS NOT NULL AND price > 0 THEN price ELSE 0 END), 0) AS DECIMAL(20,2)) AS project_inventory_amount,
                    SUM(CASE WHEN project_id IS NOT NULL AND price > 0 THEN 1 ELSE 0 END) AS project_priced_records,
                    SUM(CASE WHEN project_id IS NOT NULL AND (price IS NULL OR price <= 0) THEN 1 ELSE 0 END) AS project_unpriced_records,

                    CAST(COALESCE(SUM(CASE WHEN listing_purpose = 'sale' AND unit_status = 'available' AND price > 0 THEN price ELSE 0 END), 0) AS DECIMAL(20,2)) AS sale_inventory_amount,
                    SUM(CASE WHEN listing_purpose = 'sale' AND unit_status = 'available' AND price > 0 THEN 1 ELSE 0 END) AS sale_priced_records,
                    SUM(CASE WHEN listing_purpose = 'sale' AND unit_status = 'available' AND (price IS NULL OR price <= 0) THEN 1 ELSE 0 END) AS sale_unpriced_records,

                    CAST(COALESCE(SUM(CASE WHEN listing_purpose = 'rent' AND unit_status = 'available' AND price > 0 THEN price ELSE 0 END), 0) AS DECIMAL(20,2)) AS rent_inventory_amount,
                    SUM(CASE WHEN listing_purpose = 'rent' AND unit_status = 'available' AND price > 0 THEN 1 ELSE 0 END) AS rent_priced_records,
                    SUM(CASE WHEN listing_purpose = 'rent' AND unit_status = 'available' AND (price IS NULL OR price <= 0) THEN 1 ELSE 0 END) AS rent_unpriced_records
                ")
                ->first();

            $completedSales = DB::table('sales')
                ->selectRaw("
                    CAST(COALESCE(SUM(CASE WHEN status = 'completed' AND sale_price > 0 THEN sale_price ELSE 0 END), 0) AS DECIMAL(20,2)) AS amount,
                    SUM(CASE WHEN status = 'completed' AND sale_price > 0 THEN 1 ELSE 0 END) AS priced_records,
                    SUM(CASE WHEN status = 'completed' AND (sale_price IS NULL OR sale_price <= 0) THEN 1 ELSE 0 END) AS unpriced_records
                ")
                ->first();

            $rentalRows = DB::table('rm_rentals')
                ->where('status', 'active')
                ->whereNull('deleted_at')
                ->whereNotNull('currency')
                ->whereNotNull('total_rental_amount')
                ->where('total_rental_amount', '>', 0)
                ->selectRaw('currency, COUNT(*) AS records, CAST(SUM(total_rental_amount) AS DECIMAL(20,2)) AS amount')
                ->groupBy('currency')
                ->get();

            $rentalUnpriced = DB::table('rm_rentals')
                ->where('status', 'active')
                ->whereNull('deleted_at')
                ->where(function ($query): void {
                    $query->whereNull('total_rental_amount')
                        ->orWhere('total_rental_amount', '<=', 0);
                })
                ->count();

            $sarRental = $rentalRows->firstWhere('currency', self::SAR_CURRENCY);
            $excludedRentalCurrencies = $rentalRows
                ->filter(fn ($row): bool => $row->currency !== self::SAR_CURRENCY)
                ->map(fn ($row): array => [
                    'currency' => (string) $row->currency,
                    'amount' => $this->decimalString($row->amount),
                    'records' => (int) $row->records,
                ])
                ->values()
                ->all();

            return [
                'projectInventoryValue' => $this->financialMetric(
                    $inventorySummary->project_inventory_amount ?? 0,
                    (int) ($inventorySummary->project_priced_records ?? 0),
                    (int) ($inventorySummary->project_unpriced_records ?? 0),
                    __('Sum of priced properties linked to projects')
                ),
                'forSaleInventoryValue' => $this->financialMetric(
                    $inventorySummary->sale_inventory_amount ?? 0,
                    (int) ($inventorySummary->sale_priced_records ?? 0),
                    (int) ($inventorySummary->sale_unpriced_records ?? 0),
                    __('Available sale listings with a positive asking price')
                ),
                'forRentInventoryValue' => $this->financialMetric(
                    $inventorySummary->rent_inventory_amount ?? 0,
                    (int) ($inventorySummary->rent_priced_records ?? 0),
                    (int) ($inventorySummary->rent_unpriced_records ?? 0),
                    __('Available rental listings with a positive asking price')
                ),
                'completedSalesValue' => $this->financialMetric(
                    $completedSales->amount ?? 0,
                    (int) ($completedSales->priced_records ?? 0),
                    (int) ($completedSales->unpriced_records ?? 0),
                    __('Completed sales based on transaction sale_price')
                ),
                'activeRentalContractValue' => [
                    'amount' => $this->decimalString($sarRental->amount ?? 0),
                    'currency' => self::SAR_CURRENCY,
                    'pricedRecords' => (int) ($sarRental->records ?? 0),
                    'unpricedRecords' => $rentalUnpriced,
                    'excludedNonSarRecords' => array_sum(array_column($excludedRentalCurrencies, 'records')),
                    'excludedByCurrency' => $excludedRentalCurrencies,
                    'tooltip' => __('Active rental contracts with SAR totals only; other currencies are excluded'),
                ],
            ];
        });
    }

    private function financialMetric(mixed $amount, int $pricedRecords, int $unpricedRecords, string $tooltip): array
    {
        return [
            'amount' => $this->decimalString($amount),
            'currency' => self::SAR_CURRENCY,
            'pricedRecords' => $pricedRecords,
            'unpricedRecords' => $unpricedRecords,
            'excludedNonSarRecords' => 0,
            'excludedByCurrency' => [],
            'tooltip' => $tooltip,
        ];
    }

    private function decimalString(mixed $value): string
    {
        return number_format((float) ($value ?? 0), 2, '.', '');
    }

    private function remember(string $key, int $seconds, callable $callback): array
    {
        if (app()->runningUnitTests()) {
            return $callback();
        }

        return Cache::remember($key, $seconds, $callback);
    }

    private function paidPackageIds(): array
    {
        return [
            MembershipService::PAID_YEARLY_PACKAGE_ID,
            MembershipService::PAID_MONTHLY_PACKAGE_ID,
        ];
    }
}
