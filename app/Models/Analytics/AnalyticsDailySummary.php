<?php

namespace App\Models\Analytics;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AnalyticsDailySummary extends Model
{
    use HasFactory;

    protected $table = 'analytics_daily_summary';

    protected $fillable = ['tenant_id', 'date', 'metric_type', 'data'];
    
    protected $casts = [
        'date' => 'date',
        'data' => 'array', // Auto JSON encode/decode
    ];
    
    /**
     * Scope for filtering by tenant ID
     */
    public function scopeForTenant($query, string $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }
    
    /**
     * Scope for filtering by specific date
     */
    public function scopeForDate($query, Carbon $date)
    {
        return $query->where('date', $date->format('Y-m-d'));
    }
    
    /**
     * Scope for filtering by date range
     */
    public function scopeForDateRange($query, Carbon $start, Carbon $end)
    {
        return $query->whereBetween('date', [
            $start->format('Y-m-d'),
            $end->format('Y-m-d')
        ]);
    }
    
    /**
     * Scope for filtering by metric type
     */
    public function scopeForMetricType($query, string $type)
    {
        return $query->where('metric_type', $type);
    }
    
    /**
     * Get cached data for a specific tenant, date, and metric type
     */
    public static function getCachedData(string $tenantId, Carbon $date, string $metricType): ?array
    {
        return self::forTenant($tenantId)
            ->forDate($date)
            ->forMetricType($metricType)
            ->value('data');
    }
    
    /**
     * Store data for a specific tenant, date, and metric type
     */
    public static function storeData(string $tenantId, Carbon $date, string $metricType, array $data): void
    {
        self::updateOrCreate(
            [
                'tenant_id' => $tenantId,
                'date' => $date->format('Y-m-d'),
                'metric_type' => $metricType,
            ],
            ['data' => $data]
        );
    }
}
