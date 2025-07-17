<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ApiContentSectionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        // Clear the existing data in the table
        DB::table('api_content_sections')->truncate();
        // Define the sections to be inserted
        $sections = [
            [
                'section_id' => 'general',
                'title' => 'الإعدادات العامة',
                'description' => 'اسم الموقع، الشعار ومعلومات الاتصال',
                'icon' => 'Settings2',
                'path' => '/content/general',
                'status' => 'active',
                'info' => json_encode([
                    'email' => 'info@mycompany.com',
                    'website' => 'mycompany.com',
                ]),
                'lastUpdate' => '2023-06-15T14:30:00Z',
                'lastUpdateFormatted' => 'آخر تحديث منذ يومين',
                'badge' => json_encode([
                    'label' => 'قسم نشط',
                    'color' => 'bg-gray-100 text-gray-800'
                ]),
                'count' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'section_id' => 'banner',
                'title' => 'البانر الرئيسي',
                'description' => 'إدارة البانر الرئيسي للموقع',
                'icon' => 'ImageIcon',
                'path' => '/content/banner',
                'status' => 'active',
                'info' => json_encode([]),
                'lastUpdate' => '2023-06-16T10:15:00Z',
                'lastUpdateFormatted' => 'آخر تحديث منذ يوم واحد',
                'badge' => json_encode([
                    'label' => 'قسم نشط',
                    'color' => 'bg-rose-100 text-rose-800'
                ]),
                'count' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'section_id' => 'footer',
                'title' => 'تذييل الصفحة',
                'description' => 'تخصيص تذييل الموقع ومعلومات الاتصال',
                'icon' => 'LayoutFooter',
                'path' => '/content/footer',
                'status' => 'active',
                'info' => json_encode([]),
                'lastUpdate' => '2023-06-14T09:45:00Z',
                'lastUpdateFormatted' => 'آخر تحديث منذ 3 أيام',
                'badge' => json_encode([
                    'label' => 'قسم نشط',
                    'color' => 'bg-blue-100 text-blue-800'
                ]),
                'count' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'section_id' => 'services',
                'title' => 'خدماتنا',
                'description' => 'إدارة خدمات شركتك',
                'icon' => 'Briefcase',
                'path' => '/content/services',
                'status' => 'active',
                'info' => json_encode([]),
                'lastUpdate' => '2023-06-15T16:20:00Z',
                'lastUpdateFormatted' => 'آخر تحديث منذ يومين',
                'badge' => json_encode([
                    'label' => 'الخدمات النشطة',
                    'color' => 'bg-purple-100 text-purple-800'
                ]),
                'count' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'section_id' => 'gallery',
                'title' => 'معرض الصور',
                'description' => 'عرض أعمالك بالصور',
                'icon' => 'ImageIcon',
                'path' => '/content/gallery',
                'status' => 'inactive',
                'info' => json_encode([]),
                'lastUpdate' => '2023-06-12T11:30:00Z',
                'lastUpdateFormatted' => 'آخر تحديث منذ 5 أيام',
                'badge' => json_encode([
                    'label' => 'الصور في المعرض',
                    'color' => 'bg-green-100 text-green-800'
                ]),
                'count' => 4,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'section_id' => 'about',
                'title' => 'عن الشركة',
                'description' => 'معلومات عن شركتك ورسالتها',
                'icon' => 'Building2',
                'path' => '/content/about',
                'status' => 'active',
                'info' => json_encode([]),
                'lastUpdate' => '2023-06-14T14:10:00Z',
                'lastUpdateFormatted' => 'آخر تحديث منذ 3 أيام',
                'badge' => json_encode([
                    'label' => 'قسم نشط',
                    'color' => 'bg-blue-100 text-blue-800'
                ]),
                'count' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'section_id' => 'testimonials',
                'title' => 'آراء العملاء',
                'description' => 'شهادات من عملائك',
                'icon' => 'MessageSquare',
                'path' => '/content/testimonials',
                'status' => 'inactive',
                'info' => json_encode([]),
                'lastUpdate' => '2023-06-10T09:15:00Z',
                'lastUpdateFormatted' => 'آخر تحديث منذ أسبوع',
                'badge' => json_encode([
                    'label' => 'المراجعات النشطة',
                    'color' => 'bg-yellow-100 text-yellow-800'
                ]),
                'count' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'section_id' => 'skills',
                'title' => 'قسم المهارات',
                'description' => 'عرض مهارات الفريق والشركة',
                'icon' => 'Award',
                'path' => '/content/skills',
                'status' => 'active',
                'info' => json_encode([]),
                'lastUpdate' => '2023-06-14T15:45:00Z',
                'lastUpdateFormatted' => 'آخر تحديث منذ 3 أيام',
                'badge' => json_encode([
                    'label' => 'المهارات النشطة',
                    'color' => 'bg-indigo-100 text-indigo-800'
                ]),
                'count' => 5,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'section_id' => 'portfolio',
                'title' => 'معرض الأعمال',
                'description' => 'عرض مشاريعك وأعمالك السابقة',
                'icon' => 'FolderKanban',
                'path' => '/content/portfolio',
                'status' => 'active',
                'info' => json_encode([]),
                'lastUpdate' => null,
                'count' => 8,
                'lastUpdate' => '2023-06-10T16:30:00Z',
                'lastUpdateFormatted' => 'آخر تحديث منذ أسبوع',
                'badge' => json_encode([
                    'label' => 'المشاريع المعروضة',
                    'color' => 'bg-pink-100 text-pink-800'
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'section_id' => 'categories',
                'title' => 'التصنيفات',
                'description' => 'إدارة تصنيفات المحتوى والمنتجات',
                'icon' => 'Layers',
                'path' => '/content/categories',
                'status' => 'active',
                'info' => json_encode([]),
                'count' => 6,
                'lastUpdate' => '2023-06-13T10:20:00Z',
                'lastUpdateFormatted' => 'آخر تحديث منذ 4 أيام',
                'badge' => json_encode([
                    'label' => 'التصنيفات النشطة',
                    'color' => 'bg-orange-100 text-orange-800'
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'section_id' => 'why-choose-us',
                'title' => 'لماذا تختارنا',
                'description' => 'أسباب تميزنا عن المنافسين',
                'icon' => 'ThumbsUp',
                'path' => '/content/why-choose-us',
                'status' => 'inactive',
                'info' => json_encode([]),
                'count' => 4,
                'lastUpdate' => '2023-06-03T11:45:00Z',
                'lastUpdateFormatted' => 'آخر تحديث منذ أسبوعين',
                'badge' => json_encode([
                    'label' => 'الميزات المعروضة',
                    'color' => 'bg-teal-100 text-teal-800'
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'section_id' => 'brands',
                'title' => 'العلامات التجارية',
                'description' => 'إدارة العلامات التجارية والشركاء',
                'icon' => 'ShoppingBag',
                'path' => '/content/brands',
                'status' => 'active',
                'info' => json_encode([]),
                'count' => 7,
                'lastUpdate' => '2023-06-12T14:30:00Z',
                'lastUpdateFormatted' => 'آخر تحديث منذ 5 أيام',
                'badge' => json_encode([
                    'label' => 'العلامات النشطة',
                    'color' => 'bg-cyan-100 text-cyan-800'
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'section_id' => 'menu',
                'title' => 'إدارة القائمة',
                'description' => 'تخصيص قائمة الموقع وترتيبها',
                'icon' => 'Menu',
                'path' => '/content/menu',
                'status' => 'active',
                'info' => json_encode([]),
                'lastUpdate' => '2023-06-16T09:10:00Z',
                'lastUpdateFormatted' => 'آخر تحديث منذ يوم واحد',
                'badge' => json_encode([
                    'label' => 'عناصر القائمة',
                    'color' => 'bg-violet-100 text-violet-800'
                ]),
                'count' => 8,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'section_id' => 'achievements',
                'title' => 'الإنجازات',
                'description' => 'عرض إنجازات وجوائز الشركة',
                'icon' => 'Trophy',
                'path' => '/content/achievements',
                'status' => 'active',
                'info' => json_encode([]),
                'count' => 3,
                'lastUpdate' => '2023-06-11T13:45:00Z',
                'lastUpdateFormatted' => 'آخر تحديث منذ 6 أيام',
                'badge' => json_encode([
                    'label' => 'الإنجازات المعروضة',
                    'color' => 'bg-amber-100 text-amber-800'
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ]

        ];

        foreach ($sections as $section) {
            DB::table('api_content_sections')->updateOrInsert(
                ['section_id' => $section['section_id']],
                array_merge($section, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }
    }
}
