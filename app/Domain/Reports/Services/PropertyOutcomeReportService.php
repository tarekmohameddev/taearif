<?php

declare(strict_types=1);

namespace App\Domain\Reports\Services;

use App\Domain\Reports\DTOs\ReportFilters;
use App\Domain\Reports\Support\PropertyTransactionReportQuery;
use Illuminate\Support\Facades\DB;

final class PropertyOutcomeReportService
{
    public function outcomes(int $userId, ReportFilters $filters): array
    {
        $scope = new PropertyTransactionReportQuery($userId, $filters);
        $properties = $scope->properties();
        $sold = (int) (clone $properties)->where('p.unit_status', 'sold')->distinct()->count('p.id');
        $rented = (int) (clone $properties)->where('p.unit_status', 'rented')->distinct()->count('p.id');
        $active = DB::table('rm_rentals as rr')->where('rr.user_id', $userId)->whereNull('rr.deleted_at')->where('rr.status', 'active');
        $activeCount = (int) (clone $active)->count();
        $activeWithoutUnit = (int) (clone $active)->leftJoin('user_properties as p', 'p.id', '=', 'rr.unit_id')->whereNull('p.id')->count();
        $activeWithoutRented = (int) (clone $active)->leftJoin('user_properties as p', function ($join) use ($userId): void {
            $join->on('p.id', '=', 'rr.unit_id')->where('p.user_id', '=', $userId);
        })->where(function ($q): void { $q->whereNull('p.id')->orWhere('p.unit_status', '<>', 'rented'); })->count();
        $dupes = DB::table('rm_rentals')->where('user_id', $userId)->whereNull('deleted_at')->where('status', 'active')->whereNotNull('unit_id')
            ->select('unit_id', DB::raw('COUNT(*) as c'))->groupBy('unit_id')->having('c', '>', 1)->get();
        $soldWithoutSale = (int) (clone $properties)->where('p.unit_status', 'sold')->whereNotExists(function ($q) use ($userId): void {
            $q->select(DB::raw(1))->from('sales as s')->whereColumn('s.property_id', 'p.id')->where('s.user_id', $userId)->where('s.status', 'completed');
        })->distinct()->count('p.id');
        $rentedWithoutActive = (int) (clone $properties)->where('p.unit_status', 'rented')->whereNotExists(function ($q) use ($userId): void {
            $q->select(DB::raw(1))->from('rm_rentals as rr')->whereColumn('rr.unit_id', 'p.id')->where('rr.user_id', $userId)->whereNull('rr.deleted_at')->where('rr.status', 'active');
        })->distinct()->count('p.id');
        $duplicateSales = (int) DB::table('sales')->where('user_id', $userId)->where('status', 'completed')->select('property_id')->groupBy('property_id')->havingRaw('COUNT(*) > 1')->get()->count();
        return [
            'sold' => ['units_count' => $sold], 'rented' => ['units_count' => $rented, 'active_contracts_count' => $activeCount],
            'total_outcome_units' => $sold + $rented,
            'data_quality' => ['sold_without_completed_sale_count' => $soldWithoutSale, 'rented_without_active_contract_count' => $rentedWithoutActive, 'active_contract_without_rented_unit_count' => $activeWithoutRented, 'active_contract_without_unit_count' => $activeWithoutUnit, 'units_with_multiple_active_contracts_count' => $dupes->count(), 'extra_active_contracts_count' => (int) $dupes->sum(fn ($r) => ((int) $r->c) - 1), 'duplicate_completed_sale_property_count' => $duplicateSales],
            'as_of' => now('Asia/Riyadh')->toIso8601String(), 'timezone' => 'Asia/Riyadh',
        ];
    }

    public function categories(int $userId, ReportFilters $filters): array
    {
        $scope = new PropertyTransactionReportQuery($userId, $filters);
        $total = (int) (clone $scope->properties())->distinct()->count('p.id');
        $rows = $scope->properties()->leftJoin('api_user_categories as c', 'c.id', '=', 'p.category_id')
            ->selectRaw('c.id as category_id, c.slug, c.name, COUNT(DISTINCT p.id) as units_count')
            ->groupBy('c.id', 'c.slug', 'c.name')->orderByDesc('units_count')->orderBy('c.name')->orderBy('c.slug')->get();
        $data = $rows->map(fn ($r) => ['category_id' => $r->category_id === null ? null : (int) $r->category_id, 'slug' => $r->slug ?: 'uncategorized', 'name' => $r->name ?: 'Uncategorized', 'units_count' => (int) $r->units_count, 'percentage' => $total ? round(((int) $r->units_count) / $total * 100, 2) : 0.0])->values()->all();
        return ['data' => $data, 'total_units' => $total, 'as_of' => now('Asia/Riyadh')->toIso8601String(), 'timezone' => 'Asia/Riyadh'];
    }
}
