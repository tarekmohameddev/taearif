<?php

declare(strict_types=1);

namespace App\Domain\Reports\Services;

use App\Domain\Reports\DTOs\ReportFilters;
use App\Domain\Reports\Support\PropertyTransactionReportQuery;
use Illuminate\Support\Facades\DB;

final class PropertyTransactionValueReportService
{
    public function summary(int $userId, ReportFilters $filters): array
    {
        $scope = new PropertyTransactionReportQuery($userId, $filters);
        $sales = $this->sales($scope);
        $rentals = $this->rentals($scope);
        $payments = $this->payments($scope);
        $groups = [];
        foreach (array_merge($sales['rows']->all(), $rentals['rows']->all(), $payments['rows']->all()) as $row) $groups[$row->currency] = true;
        ksort($groups);
        $currencies = [];
        foreach (array_keys($groups) as $currency) {
            $currencies[] = ['currency' => $currency, 'completed_sales' => $this->metric($sales['rows'], $currency, 'transactions_count', 'completed_sale_value'), 'rentals' => array_merge($this->metric($rentals['rows'], $currency, 'contracts_count', 'contracted_rental_value'), $this->metric($payments['rows'], $currency, 'rent_payments_count', 'collected_rental_value'))];
        }
        $sold = $this->soldWithoutSale($scope);
        return ['currencies' => $currencies, 'data_quality' => array_merge($sold, ['completed_sales_without_currency_count' => $sales['unknown'], 'rental_contracts_without_currency_count' => $rentals['unknown'], 'rent_payments_without_currency_count' => $payments['unknown'], 'unknown_currency_count' => $sales['unknown'] + $rentals['unknown'] + $payments['unknown'], 'missing_sale_value_count' => $sales['invalid'], 'missing_rental_value_count' => $rentals['invalid'], 'invalid_rent_payment_value_count' => $payments['invalid'], 'duplicate_completed_sale_property_count' => $this->duplicateSales($userId), 'sale_property_tenant_mismatch_count' => 0, 'rental_unit_tenant_mismatch_count' => $rentals['mismatch'], 'rent_payment_without_matching_rental_count' => $payments['orphan'], 'rent_payment_tenant_mismatch_count' => $payments['mismatch']]), 'period' => ['from' => $scope->filtersStart(), 'to' => $scope->filtersEnd(), 'timezone' => 'Asia/Riyadh'], 'generated_at' => now('Asia/Riyadh')->toIso8601String()];
    }

    private function sales(PropertyTransactionReportQuery $s): array
    {
        $q = DB::table('sales as x')->joinSub($s->properties(), 'p', 'p.id', '=', 'x.property_id')->where('x.user_id', $s->userId())->where('x.status', 'completed')->whereBetween('x.sale_date', [$s->start(), $s->end()]);
        $rows = (clone $q)->whereNotNull('x.currency')->whereRaw("TRIM(x.currency) <> '' AND UPPER(TRIM(x.currency)) REGEXP '^[A-Z]{3}$'")->where('x.sale_price', '>', 0)->selectRaw('UPPER(TRIM(x.currency)) currency, COUNT(*) transactions_count, CAST(SUM(x.sale_price) AS DECIMAL(20,2)) completed_sale_value')->groupByRaw('UPPER(TRIM(x.currency))')->get();
        return ['rows' => $rows, 'unknown' => (int) (clone $q)->where(function ($q): void { $q->whereNull('x.currency')->orWhereRaw("TRIM(x.currency) = ''")->orWhereRaw("UPPER(TRIM(x.currency)) NOT REGEXP '^[A-Z]{3}$'"); })->count(), 'invalid' => (int) (clone $q)->where(function ($q): void { $q->whereNull('x.sale_price')->orWhere('x.sale_price', '<=', 0); })->count()];
    }

    private function rentals(PropertyTransactionReportQuery $s): array
    {
        $q = DB::table('rm_rentals as x')->where('x.user_id', $s->userId())->whereNull('x.deleted_at')->whereIn('x.status', ['active', 'ended'])->whereBetween('x.move_in_date', [$s->startDate(), $s->endDate()]);
        if ($s->filtersPurpose() !== null || $s->filtersType() !== null) $q->joinSub($s->properties(), 'p', 'p.id', '=', 'x.unit_id');
        $rows = (clone $q)->whereNotNull('x.currency')->whereRaw("TRIM(x.currency) <> '' AND UPPER(TRIM(x.currency)) REGEXP '^[A-Z]{3}$'")->where('x.total_rental_amount', '>', 0)->selectRaw('UPPER(TRIM(x.currency)) currency, COUNT(*) contracts_count, CAST(SUM(x.total_rental_amount) AS DECIMAL(20,2)) contracted_rental_value')->groupByRaw('UPPER(TRIM(x.currency))')->get();
        return ['rows' => $rows, 'unknown' => (int) (clone $q)->where(function ($q): void { $q->whereNull('x.currency')->orWhereRaw("TRIM(x.currency) = ''")->orWhereRaw("UPPER(TRIM(x.currency)) NOT REGEXP '^[A-Z]{3}$'"); })->count(), 'invalid' => (int) (clone $q)->where(function ($q): void { $q->whereNull('x.total_rental_amount')->orWhere('x.total_rental_amount', '<=', 0); })->count(), 'mismatch' => (int) (clone $q)->whereNotNull('x.unit_id')->whereNotExists(function ($q) use ($s): void { $q->select(DB::raw(1))->from('user_properties')->whereColumn('user_properties.id', 'x.unit_id')->where('user_properties.user_id', $s->userId()); })->count()];
    }

    private function payments(PropertyTransactionReportQuery $s): array
    {
        $q = DB::table('rm_payments as x')->leftJoin('rm_rentals as r', 'r.id', '=', 'x.rental_id')->where('x.user_id', $s->userId())->whereNull('x.deleted_at')->where('x.payment_type', 'rent')->whereBetween('x.payment_date', [$s->startDate(), $s->endDate()]);
        if ($s->filtersPurpose() !== null || $s->filtersType() !== null) $q->joinSub($s->properties(), 'p', 'p.id', '=', 'r.unit_id');
        $rows = (clone $q)->whereNotNull('r.user_id')->where('r.user_id', $s->userId())->whereNotNull('r.currency')->whereRaw("TRIM(r.currency) <> '' AND UPPER(TRIM(r.currency)) REGEXP '^[A-Z]{3}$'")->whereNotNull('x.amount')->where('x.amount', '<>', 0)->selectRaw('UPPER(TRIM(r.currency)) currency, COUNT(x.id) rent_payments_count, CAST(SUM(x.amount) AS DECIMAL(20,2)) collected_rental_value')->groupByRaw('UPPER(TRIM(r.currency))')->get();
        return ['rows' => $rows, 'unknown' => (int) (clone $q)->whereNotNull('r.user_id')->where(function ($q): void { $q->whereNull('r.currency')->orWhereRaw("TRIM(r.currency) = ''")->orWhereRaw("UPPER(TRIM(r.currency)) NOT REGEXP '^[A-Z]{3}$'"); })->count(), 'invalid' => (int) (clone $q)->where(function ($q): void { $q->whereNull('x.amount')->orWhere('x.amount', '=', 0); })->count(), 'orphan' => (int) (clone $q)->whereNull('r.id')->count(), 'mismatch' => (int) (clone $q)->whereNotNull('r.id')->where('r.user_id', '<>', $s->userId())->count()];
    }

    private function metric($rows, string $currency, string $count, string $amount): array { $r = $rows->firstWhere('currency', $currency); return [$count => $r ? (int) $r->{$count} : 0, $amount => $r ? $this->decimal((string) $r->{$amount}) : '0.00']; }
    private function decimal(string $v): string { $v = trim($v); if (!preg_match('/^-?\d+(?:\.\d+)?$/', $v)) return '0.00'; [$a, $b] = array_pad(explode('.', $v, 2), 2, ''); return ($a === '' ? '0' : $a) . '.' . str_pad(substr($b, 0, 2), 2, '0'); }
    private function soldWithoutSale(PropertyTransactionReportQuery $s): array { return ['sold_without_completed_sale_count' => (int) $s->properties()->where('p.unit_status', 'sold')->whereNotExists(function ($q) use ($s): void { $q->select(DB::raw(1))->from('sales')->whereColumn('sales.property_id', 'p.id')->where('sales.user_id', $s->userId())->where('sales.status', 'completed'); })->distinct()->count('p.id')]; }
    private function duplicateSales(int $id): int { return (int) DB::table('sales')->where('user_id', $id)->where('status', 'completed')->select('property_id')->groupBy('property_id')->havingRaw('COUNT(*) > 1')->get()->count(); }
}
