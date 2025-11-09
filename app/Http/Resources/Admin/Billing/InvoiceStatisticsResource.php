<?php

namespace App\Http\Resources\Admin\Billing;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Invoice Statistics Resource
 *
 * Transforms invoice statistics data into JSON response
 */
class InvoiceStatisticsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        $data = $this->resource;

        return [
            'invoices' => [
                'total' => $data['invoices']['total'] ?? 0,
                'paid' => $data['invoices']['paid'] ?? 0,
                'pending' => $data['invoices']['pending'] ?? 0,
                'rejected' => $data['invoices']['rejected'] ?? 0,
                'trial' => $data['invoices']['trial'] ?? 0,
            ],
            'revenue' => [
                'total' => (float) ($data['revenue']['total'] ?? 0),
                'today' => (float) ($data['revenue']['today'] ?? 0),
                'this_month' => (float) ($data['revenue']['this_month'] ?? 0),
                'last_month' => (float) ($data['revenue']['last_month'] ?? 0),
                'formatted' => [
                    'total' => '$' . number_format($data['revenue']['total'] ?? 0, 2),
                    'today' => '$' . number_format($data['revenue']['today'] ?? 0, 2),
                    'this_month' => '$' . number_format($data['revenue']['this_month'] ?? 0, 2),
                    'last_month' => '$' . number_format($data['revenue']['last_month'] ?? 0, 2),
                ],
            ],
            'trends' => [
                'month_over_month_growth' => $this->calculateGrowthPercentage(
                    $data['revenue']['this_month'] ?? 0,
                    $data['revenue']['last_month'] ?? 0
                ),
            ],
        ];
    }

    /**
     * Calculate growth percentage
     *
     * @param float $current
     * @param float $previous
     * @return float|null
     */
    protected function calculateGrowthPercentage(float $current, float $previous): ?float
    {
        if ($previous == 0) {
            return $current > 0 ? 100.0 : 0.0;
        }

        return round((($current - $previous) / $previous) * 100, 2);
    }
}

