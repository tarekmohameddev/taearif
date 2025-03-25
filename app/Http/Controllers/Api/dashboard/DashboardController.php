<?php

namespace App\Http\Controllers\Api\dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function summary()
    {
        return response()->json([
            'visits' => 0,
            'visits_change' => 0,
            'page_views' => 0,
            'page_views_change' => 0,
            'average_time' => '0',
            'average_time_change' => 0,
            'bounce_rate' => 0,
            'bounce_rate_change' => 0,
        ]);
    }

    public function visitors(Request $request)
    {
        return response()->json([
            'visitor_data' => [
            ],
            'total_visits' => 0,
            'total_unique_visitors' => 0,
        ]);
    }

    public function devices()
    {
        return response()->json([
            'devices' => [
            ]
        ]);
    }

    public function trafficSources()
    {
        return response()->json([
            'sources' => [
            ]
        ]);
    }

    public function mostVisitedPages()
    {
        return response()->json([
            'pages' => [
            ]
        ]);
    }

    public function setupProgress()
    {
        return response()->json([
            'progress_percentage' => 60,
            'completed_steps' => [
                ['id' => 1, 'name' => 'إنشاء الموقع', 'completed' => true],
                ['id' => 2, 'name' => 'اختيار القالب', 'completed' => true],
                ['id' => 3, 'name' => 'تخصيص الشعار', 'completed' => true],
                ['id' => 4, 'name' => 'إضافة المحتوى', 'completed' => false],
                ['id' => 5, 'name' => 'ربط المجال', 'completed' => false],
            ]
        ]);
    }

    public function getRecentActivity()
    {
        return response()->json([
            'activities' => [
            ]
        ]);
    }
}
