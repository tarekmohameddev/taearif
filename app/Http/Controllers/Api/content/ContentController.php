<?php

namespace App\Http\Controllers\Api\content;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ContentController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return response()->json([
            'status' => 'success',
            'data' => [
                'sections' => [
                    [
                        'id' => 'general',
                        'title' => 'الإعدادات العامة',
                        'description' => 'اسم الموقع، الشعار ومعلومات الاتصال',
                        'icon' => 'Settings2',
                        'path' => '/content/general',
                        'status' => 'active',
                        'lastUpdate' => '2023-06-15T14:30:00Z',
                        'lastUpdateFormatted' => 'آخر تحديث منذ يومين',
                        'info' => [
                            'email' => 'info@mycompany.com',
                            'website' => 'mycompany.com'
                        ],
                        'badge' => null
                    ],
                    [
                        'id' => 'banner',
                        'title' => 'البانر الرئيسي',
                        'description' => 'إدارة البانر الرئيسي للموقع',
                        'icon' => 'ImageIcon',
                        'path' => '/content/banner',
                        'status' => 'active',
                        'lastUpdate' => '2023-06-16T10:15:00Z',
                        'lastUpdateFormatted' => 'آخر تحديث منذ يوم واحد',
                        'badge' => [
                            'label' => 'قسم نشط',
                            'color' => 'bg-rose-100 text-rose-800'
                        ]
                    ],
                    [
                        'id' => 'footer',
                        'title' => 'تذييل الصفحة',
                        'description' => 'تخصيص تذييل الموقع ومعلومات الاتصال',
                        'icon' => 'LayoutFooter',
                        'path' => '/content/footer',
                        'status' => 'active',
                        'lastUpdate' => '2023-06-14T09:45:00Z',
                        'lastUpdateFormatted' => 'آخر تحديث منذ 3 أيام',
                        'badge' => [
                            'label' => 'قسم نشط',
                            'color' => 'bg-blue-100 text-blue-800'
                        ]
                    ],
                    [
                        'id' => 'services',
                        'title' => 'خدماتنا',
                        'description' => 'إدارة خدمات شركتك',
                        'icon' => 'Briefcase',
                        'path' => '/content/services',
                        'status' => 'active',
                        'count' => 2,
                        'lastUpdate' => '2023-06-15T16:20:00Z',
                        'lastUpdateFormatted' => 'آخر تحديث منذ يومين',
                        'badge' => [
                            'label' => 'الخدمات النشطة',
                            'color' => 'bg-purple-100 text-purple-800'
                        ]
                    ],
                    [
                        'id' => 'gallery',
                        'title' => 'معرض الصور',
                        'description' => 'عرض أعمالك بالصور',
                        'icon' => 'ImageIcon',
                        'path' => '/content/gallery',
                        'status' => 'inactive',
                        'count' => 4,
                        'lastUpdate' => '2023-06-12T11:30:00Z',
                        'lastUpdateFormatted' => 'آخر تحديث منذ 5 أيام',
                        'badge' => [
                            'label' => 'الصور في المعرض',
                            'color' => 'bg-green-100 text-green-800'
                        ]
                    ],
                    [
                        'id' => 'about',
                        'title' => 'عن الشركة',
                        'description' => 'معلومات عن شركتك ورسالتها',
                        'icon' => 'Building2',
                        'path' => '/content/about',
                        'status' => 'active',
                        'lastUpdate' => '2023-06-14T14:10:00Z',
                        'lastUpdateFormatted' => 'آخر تحديث منذ 3 أيام',
                        'badge' => [
                            'label' => 'قسم نشط',
                            'color' => 'bg-blue-100 text-blue-800'
                        ]
                    ],
                    [
                        'id' => 'testimonials',
                        'title' => 'آراء العملاء',
                        'description' => 'شهادات من عملائك',
                        'icon' => 'MessageSquare',
                        'path' => '/content/testimonials',
                        'status' => 'inactive',
                        'count' => 2,
                        'lastUpdate' => '2023-06-10T09:15:00Z',
                        'lastUpdateFormatted' => 'آخر تحديث منذ أسبوع',
                        'badge' => [
                            'label' => 'المراجعات النشطة',
                            'color' => 'bg-yellow-100 text-yellow-800'
                        ]
                    ],
                    [
                        'id' => 'skills',
                        'title' => 'قسم المهارات',
                        'description' => 'عرض مهارات الفريق والشركة',
                        'icon' => 'Award',
                        'path' => '/content/skills',
                        'status' => 'active',
                        'count' => 5,
                        'lastUpdate' => '2023-06-14T15:45:00Z',
                        'lastUpdateFormatted' => 'آخر تحديث منذ 3 أيام',
                        'badge' => [
                            'label' => 'المهارات النشطة',
                            'color' => 'bg-indigo-100 text-indigo-800'
                        ]
                    ],
                    [
                        'id' => 'portfolio',
                        'title' => 'معرض الأعمال',
                        'description' => 'عرض مشاريعك وأعمالك السابقة',
                        'icon' => 'FolderKanban',
                        'path' => '/content/portfolio',
                        'status' => 'active',
                        'count' => 8,
                        'lastUpdate' => '2023-06-10T16:30:00Z',
                        'lastUpdateFormatted' => 'آخر تحديث منذ أسبوع',
                        'badge' => [
                            'label' => 'المشاريع المعروضة',
                            'color' => 'bg-pink-100 text-pink-800'
                        ]
                    ],
                    [
                        'id' => 'categories',
                        'title' => 'التصنيفات',
                        'description' => 'إدارة تصنيفات المحتوى والمنتجات',
                        'icon' => 'Layers',
                        'path' => '/content/categories',
                        'status' => 'active',
                        'count' => 6,
                        'lastUpdate' => '2023-06-13T10:20:00Z',
                        'lastUpdateFormatted' => 'آخر تحديث منذ 4 أيام',
                        'badge' => [
                            'label' => 'التصنيفات النشطة',
                            'color' => 'bg-orange-100 text-orange-800'
                        ]
                    ],
                    [
                        'id' => 'why-choose-us',
                        'title' => 'لماذا تختارنا',
                        'description' => 'أسباب تميزنا عن المنافسين',
                        'icon' => 'ThumbsUp',
                        'path' => '/content/why-choose-us',
                        'status' => 'inactive',
                        'count' => 4,
                        'lastUpdate' => '2023-06-03T11:45:00Z',
                        'lastUpdateFormatted' => 'آخر تحديث منذ أسبوعين',
                        'badge' => [
                            'label' => 'الميزات المعروضة',
                            'color' => 'bg-teal-100 text-teal-800'
                        ]
                    ],
                    [
                        'id' => 'brands',
                        'title' => 'العلامات التجارية',
                        'description' => 'إدارة العلامات التجارية والشركاء',
                        'icon' => 'ShoppingBag',
                        'path' => '/content/brands',
                        'status' => 'active',
                        'count' => 7,
                        'lastUpdate' => '2023-06-12T14:30:00Z',
                        'lastUpdateFormatted' => 'آخر تحديث منذ 5 أيام',
                        'badge' => [
                            'label' => 'العلامات النشطة',
                            'color' => 'bg-cyan-100 text-cyan-800'
                        ]
                    ],
                    [
                        'id' => 'menu',
                        'title' => 'إدارة القائمة',
                        'description' => 'تخصيص قائمة الموقع وترتيبها',
                        'icon' => 'Menu',
                        'path' => '/content/menu',
                        'status' => 'active',
                        'count' => 8,
                        'lastUpdate' => '2023-06-16T09:10:00Z',
                        'lastUpdateFormatted' => 'آخر تحديث منذ يوم واحد',
                        'badge' => [
                            'label' => 'عناصر القائمة',
                            'color' => 'bg-violet-100 text-violet-800'
                        ]
                    ],
                    [
                        'id' => 'achievements',
                        'title' => 'الإنجازات',
                        'description' => 'عرض إنجازات وجوائز الشركة',
                        'icon' => 'Trophy',
                        'path' => '/content/achievements',
                        'status' => 'active',
                        'count' => 3,
                        'lastUpdate' => '2023-06-11T13:45:00Z',
                        'lastUpdateFormatted' => 'آخر تحديث منذ 6 أيام',
                        'badge' => [
                            'label' => 'الإنجازات المعروضة',
                            'color' => 'bg-amber-100 text-amber-800'
                        ]
                    ]
                ],
                'availableIcons' => [
                    'FileText',
                    'Briefcase',
                    'ImageIcon',
                    'Building2',
                    'MessageSquare',
                    'Award',
                    'Layers',
                    'ThumbsUp',
                    'FolderKanban',
                    'Lightbulb',
                    'ShoppingBag',
                    'Menu',
                    'Trophy',
                    'Star',
                    'LayoutFooter'
                ]
            ]
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
