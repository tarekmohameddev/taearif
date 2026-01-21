<?php

namespace App\Services;

use App\Models\User\RealestateManagement\Property;
use App\Models\User\RealestateManagement\ApiUserCategory;
use App\Models\User\RealestateManagement\UserPropertyCharacteristic;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class PropertyFilterOptionsService
{
    /**
     * Generate filter options for properties
     * Extracted from PropertyController for reuse in commands and other contexts
     *
     * @param array $allowedUserIds
     * @return array
     */
    public static function generateFilterOptions(array $allowedUserIds): array
    {
        // OPTIMIZED: Combined price and area stats in single query
        $stats = Property::whereIn('user_id', $allowedUserIds)
            ->where(function($q) {
                $q->whereNotNull('price')->orWhereNotNull('area');
            })
            ->selectRaw('
                MIN(price) as min_price,
                MAX(price) as max_price,
                MIN(area) as min_area
            ')
            ->first();

        $priceRange = [
            'min' => $stats->min_price ?: 0,
            'max' => $stats->max_price ?: 0,
        ];

        $areaRange = [
            'min' => $stats->min_area ?: 0,
        ];

        // OPTIMIZED: Consolidate basic filter queries into single query with conditional aggregation
        // This reduces from 4 separate queries to 1 query while maintaining good index usage
        $basicFilters = DB::table('user_properties')
            ->whereIn('user_id', $allowedUserIds)
            ->selectRaw('
                GROUP_CONCAT(DISTINCT CASE WHEN purpose IS NOT NULL AND purpose != "" THEN purpose END) as purposes,
                GROUP_CONCAT(DISTINCT CASE WHEN type IS NOT NULL AND type != "" THEN type END) as types,
                GROUP_CONCAT(DISTINCT CASE WHEN beds IS NOT NULL THEN CAST(beds AS CHAR) END) as beds_list,
                GROUP_CONCAT(DISTINCT CASE WHEN bath IS NOT NULL THEN CAST(bath AS CHAR) END) as bath_list
            ')
            ->first();

        // Parse and process the results
        $availablePurposes = $basicFilters && $basicFilters->purposes 
            ? array_values(array_unique(explode(',', $basicFilters->purposes)))
            : [];

        $availableTypes = $basicFilters && $basicFilters->types
            ? array_values(array_unique(explode(',', $basicFilters->types)))
            : [];

        $availableBeds = $basicFilters && $basicFilters->beds_list
            ? array_values(array_unique(array_map(fn($v) => (int)$v, explode(',', $basicFilters->beds_list))))
            : [];
        sort($availableBeds, SORT_NUMERIC);

        $availableBath = $basicFilters && $basicFilters->bath_list
            ? array_values(array_unique(array_map(fn($v) => (int)$v, explode(',', $basicFilters->bath_list))))
            : [];
        sort($availableBath, SORT_NUMERIC);

        // OPTIMIZED: Extract unique features using MySQL JSON functions
        $isMysql80Plus = DatabaseVersionService::isMysql80Plus();
        
        if ($isMysql80Plus) {
            // MySQL 8.0+: Use JSON_TABLE for efficient extraction
            try {
                $featureResults = DB::select("
                    SELECT DISTINCT feature_value
                    FROM user_properties,
                    JSON_TABLE(
                        COALESCE(features, '[]'),
                        '$[*]' COLUMNS (feature_value VARCHAR(255) PATH '$')
                    ) AS jt
                    WHERE user_properties.user_id IN (" . implode(',', $allowedUserIds) . ")
                    AND features IS NOT NULL
                    AND feature_value IS NOT NULL
                    AND feature_value != ''
                    ORDER BY feature_value
                ");
                $availableFeatures = array_map(fn($row) => $row->feature_value, $featureResults);
            } catch (\Exception $e) {
                // Fallback to older method if JSON_TABLE fails
                Log::warning('JSON_TABLE extraction failed, using fallback method', ['error' => $e->getMessage()]);
                $availableFeatures = self::extractFeaturesFallback($allowedUserIds);
            }
        } else {
            // MySQL 5.7 or older: Use fallback method
            $availableFeatures = self::extractFeaturesFallback($allowedUserIds);
        }
        
        sort($availableFeatures);

        // OPTIMIZED: Get UserPropertyCharacteristic filter options using single query with UNION
        $propertyIds = Property::whereIn('user_id', $allowedUserIds)
            ->pluck('id');

        $characteristicFilterOptions = [];
        $characteristicFields = [
            'private_parking', 'elevator', 'annex', 'garden', 'balcony', 'basement',
            'majlis', 'storage_room', 'living_room', 'dining_room', 'maid_room',
            'driver_room', 'swimming_pool', 'kitchen', 'floor_number', 'floors',
            'bathrooms', 'rooms', 'building_age'
        ];

        // Build UNION query to get all distinct values in a single database round-trip
        $unionQueries = [];
        foreach ($characteristicFields as $field) {
            $unionQueries[] = DB::table('user_property_characteristics')
                ->whereIn('property_id', $propertyIds)
                ->whereNotNull($field)
                ->selectRaw("'{$field}' as field_name, CAST({$field} AS CHAR) as field_value")
                ->distinct();
        }

        // Execute all UNION queries at once
        if (!empty($unionQueries)) {
            $baseQuery = array_shift($unionQueries);
            foreach ($unionQueries as $query) {
                $baseQuery->union($query);
            }
            
            $results = $baseQuery->orderBy('field_name')->orderBy('field_value')->get();
            
            // Group results by field name
            foreach ($results as $result) {
                $fieldName = $result->field_name;
                $fieldValue = $result->field_value;
                
                // Convert numeric fields back to appropriate types
                if (in_array($fieldName, ['floor_number', 'floors', 'bathrooms', 'rooms', 'building_age'])) {
                    $fieldValue = is_numeric($fieldValue) ? (int)$fieldValue : $fieldValue;
                } elseif (in_array($fieldName, ['private_parking', 'elevator', 'annex', 'garden', 'balcony', 'basement',
                    'majlis', 'storage_room', 'living_room', 'dining_room', 'maid_room',
                    'driver_room', 'swimming_pool', 'kitchen'])) {
                    $fieldValue = (bool)$fieldValue;
                }
                
                if (!isset($characteristicFilterOptions[$fieldName])) {
                    $characteristicFilterOptions[$fieldName] = [];
                }
                $characteristicFilterOptions[$fieldName][] = $fieldValue;
            }
            
            // Sort and remove duplicates for each field
            foreach ($characteristicFilterOptions as $field => &$values) {
                $values = array_values(array_unique($values));
                if (in_array($field, ['floor_number', 'floors', 'bathrooms', 'rooms', 'building_age'])) {
                    sort($values, SORT_NUMERIC);
                } else {
                    sort($values);
                }
            }
        }

        // OPTIMIZED: Get employees who have created or own properties using single UNION query
        $employeeIds = DB::table('user_properties')
            ->whereIn('user_id', $allowedUserIds)
            ->select('user_id as employee_id')
            ->distinct()
            ->union(
                DB::table('user_properties')
                    ->whereIn('user_id', $allowedUserIds)
                    ->whereNotNull('created_by')
                    ->select('created_by as employee_id')
                    ->distinct()
            )
            ->pluck('employee_id')
            ->filter()
            ->unique()
            ->values()
            ->toArray();

        // Filter to only include employees that are in allowedUserIds
        $employeeIds = array_intersect($employeeIds, $allowedUserIds);

        $employees = User::whereIn('id', $employeeIds)
            ->select('id', 'first_name', 'last_name', 'email', 'username')
            ->get()
            ->map(function($emp) {
                return [
                    'id' => $emp->id,
                    'name' => trim(($emp->first_name ?? '') . ' ' . ($emp->last_name ?? '')) ?: ($emp->username ?? $emp->email),
                    'email' => $emp->email,
                ];
            })
            ->values()
            ->toArray();

        // Get categories used in properties
        $categoryIds = Property::whereIn('user_id', $allowedUserIds)
            ->whereNotNull('category_id')
            ->distinct()
            ->pluck('category_id');

        $categories = ApiUserCategory::whereIn('id', $categoryIds)
            ->select('id', 'name')
            ->get()
            ->map(function($cat) {
                return [
                    'id' => $cat->id,
                    'name' => $cat->name,
                ];
            })
            ->values()
            ->toArray();

        // Get distinct payment methods
        $paymentMethods = Property::whereIn('user_id', $allowedUserIds)
            ->whereNotNull('payment_method')
            ->where('payment_method', '!=', '')
            ->distinct()
            ->pluck('payment_method')
            ->filter()
            ->sort()
            ->values()
            ->toArray();

        // Get date range (min/max created_at)
        $dateStats = Property::whereIn('user_id', $allowedUserIds)
            ->whereNotNull('created_at')
            ->selectRaw('MIN(created_at) as min_date, MAX(created_at) as max_date')
            ->first();

        $dateRange = [
            'min' => $dateStats && $dateStats->min_date ? Carbon::parse($dateStats->min_date)->format('Y-m-d') : null,
            'max' => $dateStats && $dateStats->max_date ? Carbon::parse($dateStats->max_date)->format('Y-m-d') : null,
        ];

        return [
            'purposes' => $availablePurposes,
            'price_range' => $priceRange,
            'area_range' => $areaRange,
            'types' => $availableTypes,
            'beds' => $availableBeds,
            'bath' => $availableBath,
            'features' => $availableFeatures,
            'characteristics' => $characteristicFilterOptions,
            'employees' => $employees,
            'categories' => $categories,
            'payment_methods' => $paymentMethods,
            'date_range' => $dateRange,
        ];
    }

    /**
     * Fallback method for extracting unique features from JSON arrays
     * Used when JSON_TABLE is not available (MySQL < 8.0)
     *
     * @param array $allowedUserIds
     * @return array
     */
    private static function extractFeaturesFallback(array $allowedUserIds): array
    {
        $allFeatures = Property::whereIn('user_id', $allowedUserIds)
            ->whereNotNull('features')
            ->pluck('features')
            ->flatten()
            ->filter()
            ->unique()
            ->values()
            ->toArray();
        
        return array_values($allFeatures);
    }
}
