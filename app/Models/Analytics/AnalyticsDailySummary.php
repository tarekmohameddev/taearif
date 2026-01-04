<?php

namespace App\Models\Analytics;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class AnalyticsDailySummary extends Model
{
    use HasFactory;

    protected $table = 'analytics_daily_summary';

    protected $fillable = ['tenant_id', 'date', 'data'];
    
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
     * Get metric data for a specific tenant, date, and metric type
     * 
     * @param string $tenantId
     * @param Carbon $date
     * @param string $metricType One of: visitors, devices, traffic_sources, summary, top_pages
     * @return array|null
     */
    public static function getMetricData(string $tenantId, Carbon $date, string $metricType): ?array
    {
        $record = self::forTenant($tenantId)->forDate($date)->first();
        
        return $record?->data[$metricType] ?? null;
    }
    
    /**
     * Get all metrics for a specific tenant and date
     * 
     * @param string $tenantId
     * @param Carbon $date
     * @return array|null
     */
    public static function getAllMetrics(string $tenantId, Carbon $date): ?array
    {
        return self::forTenant($tenantId)->forDate($date)->value('data');
    }
    
    /**
     * Store a specific metric type for a tenant/date using atomic JSON_SET
     * This method ensures concurrent updates don't overwrite each other
     * 
     * @param string $tenantId
     * @param Carbon $date
     * @param string $metricType One of: visitors, devices, traffic_sources, summary, top_pages
     * @param array $metricData The data to store for this metric type
     * @return void
     */
    public static function storeMetric(string $tenantId, Carbon $date, string $metricType, array $metricData): void
    {
        $dateStr = $date->format('Y-m-d');
        $jsonPath = '$.' . $metricType;
        $jsonValue = json_encode($metricData);
        
        // Ensure row exists first (with empty JSON if new)
        DB::statement("
            INSERT INTO analytics_daily_summary (tenant_id, date, data, created_at, updated_at)
            VALUES (?, ?, '{}', NOW(), NOW())
            ON DUPLICATE KEY UPDATE updated_at = NOW()
        ", [$tenantId, $dateStr]);
        
        // Atomic partial update using JSON_SET - only updates the specified metric key
        // This prevents concurrent updates from overwriting each other
        // Use JSON_EXTRACT on a JSON string parameter to provide a proper JSON value
        DB::statement("
            UPDATE analytics_daily_summary 
            SET data = JSON_SET(COALESCE(data, '{}'), ?, JSON_EXTRACT(?, '$')), updated_at = NOW()
            WHERE tenant_id = ? AND date = ?
        ", [$jsonPath, $jsonValue, $tenantId, $dateStr]);
    }
    
    /**
     * Legacy method for backward compatibility
     * @deprecated Use storeMetric() instead
     */
    public static function storeData(string $tenantId, Carbon $date, string $metricType, array $data): void
    {
        self::storeMetric($tenantId, $date, $metricType, $data);
    }
    
    /**
     * Legacy method for backward compatibility
     * @deprecated Use getMetricData() instead
     */
    public static function getCachedData(string $tenantId, Carbon $date, string $metricType): ?array
    {
        return self::getMetricData($tenantId, $date, $metricType);
    }
}
