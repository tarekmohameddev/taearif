<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class QueryMonitoringService
{
    /**
     * Enable query logging and return a callback to get statistics
     * 
     * @return callable Callback that returns array with query count and execution time
     */
    public static function startMonitoring(): callable
    {
        $startTime = microtime(true);
        $startMemory = memory_get_usage(true);
        
        // Enable query logging
        DB::enableQueryLog();
        
        // Return callback to get statistics
        return function () use ($startTime, $startMemory) {
            $endTime = microtime(true);
            $endMemory = memory_get_usage(true);
            $queries = DB::getQueryLog();
            
            $executionTime = ($endTime - $startTime) * 1000; // Convert to milliseconds
            $memoryUsed = $endMemory - $startMemory;
            $queryCount = count($queries);
            
            // Calculate total query time
            $totalQueryTime = 0;
            foreach ($queries as $query) {
                $totalQueryTime += $query['time'] ?? 0;
            }
            
            // Find slow queries (> 100ms)
            $slowQueries = array_filter($queries, function ($query) {
                return ($query['time'] ?? 0) > 100;
            });
            
            return [
                'execution_time_ms' => round($executionTime, 2),
                'query_count' => $queryCount,
                'total_query_time_ms' => round($totalQueryTime, 2),
                'memory_used_bytes' => $memoryUsed,
                'memory_used_mb' => round($memoryUsed / 1024 / 1024, 2),
                'slow_queries' => count($slowQueries),
                'queries' => $queries,
                'slow_query_details' => array_map(function ($query) {
                    return [
                        'query' => $query['query'],
                        'bindings' => $query['bindings'],
                        'time_ms' => $query['time'] ?? 0,
                    ];
                }, $slowQueries),
            ];
        };
    }
    
    /**
     * Log query statistics if in development/staging or if explicitly enabled
     * 
     * @param string $context Context identifier (e.g., 'PropertyController::index')
     * @param array $statistics Statistics from monitoring callback
     * @param int $slowQueryThreshold Threshold in milliseconds for slow query warning
     * @return void
     */
    public static function logStatistics(string $context, array $statistics, int $slowQueryThreshold = 100): void
    {
        $isEnabled = config('app.debug') || env('ENABLE_QUERY_MONITORING', false);
        
        if (!$isEnabled) {
            return;
        }
        
        $logLevel = 'info';
        $message = "Query monitoring [{$context}]: {$statistics['query_count']} queries in {$statistics['execution_time_ms']}ms";
        
        // Log slow queries as warning
        if ($statistics['slow_queries'] > 0 || $statistics['execution_time_ms'] > $slowQueryThreshold) {
            $logLevel = 'warning';
            $message .= " - SLOW: {$statistics['slow_queries']} slow queries detected";
            
            // Log slow query details
            foreach ($statistics['slow_query_details'] as $slowQuery) {
                Log::warning("Slow query detected [{$context}]", [
                    'query' => $slowQuery['query'],
                    'bindings' => $slowQuery['bindings'],
                    'time_ms' => $slowQuery['time_ms'],
                ]);
            }
        }
        
        Log::{$logLevel}($message, [
            'context' => $context,
            'query_count' => $statistics['query_count'],
            'execution_time_ms' => $statistics['execution_time_ms'],
            'total_query_time_ms' => $statistics['total_query_time_ms'],
            'memory_used_mb' => $statistics['memory_used_mb'],
            'slow_queries' => $statistics['slow_queries'],
        ]);
    }
    
    /**
     * Explain a query to analyze index usage
     * 
     * @param \Illuminate\Database\Query\Builder|\Illuminate\Database\Eloquent\Builder $query
     * @return array
     */
    public static function explainQuery($query): array
    {
        $sql = $query->toSql();
        $bindings = $query->getBindings();
        
        // Replace bindings in SQL for explanation
        $explainSql = str_replace('?', "'%s'", $sql);
        $explainSql = vsprintf($explainSql, array_map(function ($binding) {
            return is_string($binding) ? addslashes($binding) : $binding;
        }, $bindings));
        
        try {
            $results = DB::select("EXPLAIN {$explainSql}");
            return array_map(function ($result) {
                return (array) $result;
            }, $results);
        } catch (\Exception $e) {
            Log::error('Failed to explain query', [
                'error' => $e->getMessage(),
                'sql' => $sql,
            ]);
            return [];
        }
    }
    
    /**
     * Get query statistics without logging
     * Useful for API responses or testing
     * 
     * @param callable $callback Monitoring callback from startMonitoring()
     * @return array Simplified statistics array
     */
    public static function getStatistics(callable $callback): array
    {
        $fullStats = $callback();
        
        return [
            'query_count' => $fullStats['query_count'],
            'execution_time_ms' => $fullStats['execution_time_ms'],
            'total_query_time_ms' => $fullStats['total_query_time_ms'],
            'memory_used_mb' => $fullStats['memory_used_mb'],
            'slow_queries' => $fullStats['slow_queries'],
        ];
    }
}
