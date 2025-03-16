<?php

namespace App\Http\Controllers\Api\dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function summary()
    {
        return response()->json([
            'visits' => 1245,
            'visits_change' => 12,
            'page_views' => 3721,
            'page_views_change' => 8,
            'average_time' => '2:14',
            'average_time_change' => 4,
            'bounce_rate' => 32.1,
            'bounce_rate_change' => 2,
        ]);
    }

    public function visitors(Request $request)
    {
        return response()->json([
            'visitor_data' => [
                ['date' => '1 يناير', 'visits' => 450, 'uniqueVisitors' => 320],
                ['date' => '5 يناير', 'visits' => 580, 'uniqueVisitors' => 420],
                ['date' => '10 يناير', 'visits' => 540, 'uniqueVisitors' => 380],
                ['date' => '15 يناير', 'visits' => 750, 'uniqueVisitors' => 560],
                ['date' => '20 يناير', 'visits' => 800, 'uniqueVisitors' => 600],
                ['date' => '25 يناير', 'visits' => 920, 'uniqueVisitors' => 680],
                ['date' => '30 يناير', 'visits' => 1150, 'uniqueVisitors' => 850],
            ],
            'total_visits' => 5190,
            'total_unique_visitors' => 3810,
        ]);
    }

    public function devices()
    {
        return response()->json([
            'devices' => [
                ['name' => 'الهاتف المحمول', 'value' => 55, 'color' => '#4285F4'],
                ['name' => 'الحاسوب', 'value' => 25, 'color' => '#34A853'],
                ['name' => 'الجهاز اللوحي', 'value' => 15, 'color' => '#A142F4'],
                ['name' => 'أخرى', 'value' => 5, 'color' => '#6B7280'],
            ]
        ]);
    }

    public function trafficSources()
    {
        return response()->json([
            'sources' => [
                ['name' => 'البحث العضوي', 'value' => 45, 'color' => '#4285F4'],
                ['name' => 'الروابط المباشرة', 'value' => 25, 'color' => '#34A853'],
                ['name' => 'وسائل التواصل', 'value' => 15, 'color' => '#A142F4'],
                ['name' => 'الإعلانات', 'value' => 10, 'color' => '#F4B400'],
                ['name' => 'أخرى', 'value' => 5, 'color' => '#6B7280'],
            ]
        ]);
    }

    public function mostVisitedPages()
    {
        return response()->json([
            'pages' => [
                ['path' => '/', 'views' => 4256, 'unique_visitors' => 3128, 'bounce_rate' => 32.4, 'avg_time' => '1:45', 'percentage' => 35],
                ['path' => '/المنتجات', 'views' => 2845, 'unique_visitors' => 2012, 'bounce_rate' => 28.7, 'avg_time' => '2:30', 'percentage' => 22],
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
                ['id' => 1, 'action' => 'تم تحديث المحتوى', 'section' => 'الصفحة الرئيسية', 'time' => 'منذ 2 ساعة', 'icon' => 'file-text', 'user_id' => 1, 'created_at' => '2023-01-01T10:00:00.000000Z'],
                ['id' => 2, 'action' => 'تم تغيير القالب', 'section' => 'الموقع بالكامل', 'time' => 'منذ 5 ساعات', 'icon' => 'layout-grid', 'user_id' => 1, 'created_at' => '2023-01-01T07:00:00.000000Z'],
                ['id' => 3, 'action' => 'تم نشر الموقع', 'section' => 'الموقع بالكامل', 'time' => 'منذ يومين', 'icon' => 'globe', 'user_id' => 1, 'created_at' => '2022-12-30T15:00:00.000000Z'],
                ['id' => 4, 'action' => 'تم تحديث الإعدادات', 'section' => 'إعدادات الموقع', 'time' => 'منذ 3 أيام', 'icon' => 'settings', 'user_id' => 1, 'created_at' => '2022-12-29T09:00:00.000000Z']
            ]
        ]);
    }
}
