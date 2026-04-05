<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Seeds demo real-estate projects for user kkkkk (user_id = 1430).
 * Inserts user_projects, user_project_contents, and user_project_types.
 *
 * Run with:
 *   php artisan db:seed --class=KsaProjectsForKkkkSeeder
 */
class KsaProjectsForKkkkSeeder extends Seeder
{
    private const USER_ID     = 1430;
    private const LANGUAGE_ID = 1983;

    /** City centers (from user_cities) + small offsets baked into each row */
    private array $projects = [];

    private array $images = [
        'https://images.unsplash.com/photo-1545324418-cc1a3fa10c00?w=800&q=80',
        'https://images.unsplash.com/photo-1486406146926-c627a92ad4ab?w=800&q=80',
        'https://images.unsplash.com/photo-1503387762-592deb58ef4e?w=800&q=80',
        'https://images.unsplash.com/photo-1512917774080-9991f1c4c750?w=800&q=80',
        'https://images.unsplash.com/photo-1600585154340-be6161a56a0c?w=800&q=80',
        'https://images.unsplash.com/photo-1600607687939-ce8a6c25118c?w=800&q=80',
        'https://images.unsplash.com/photo-1600566753190-17f0baa2a6c3?w=800&q=80',
        'https://images.unsplash.com/photo-1600047509807-ba8f99d2cdde?w=800&q=80',
        'https://images.unsplash.com/photo-1600585154526-990dced4db0d?w=800&q=80',
        'https://images.unsplash.com/photo-1600607687644-c7171b42498f?w=800&q=80',
    ];

    public function run(): void
    {
        $this->buildProjects();

        $existingTitles = DB::table('user_project_contents')
            ->where('user_id', self::USER_ID)
            ->pluck('title')
            ->map(fn($t) => trim((string) $t))
            ->all();

        $inserted = 0;
        $skipped  = 0;

        foreach ($this->projects as $idx => $p) {
            if (in_array(trim($p['title']), $existingTitles, true)) {
                $this->command->warn("⟳ skip (already exists): {$p['title']}");
                $skipped++;
                continue;
            }

            $image = $this->images[$idx % count($this->images)];

            $projectId = DB::table('user_projects')->insertGetId([
                'user_id'           => self::USER_ID,
                'created_by'        => self::USER_ID,
                'featured_image'    => $image,
                'video_url'         => null,
                'min_price'         => $p['min_price'],
                'max_price'         => $p['max_price'],
                'latitude'          => (string) $p['latitude'],
                'longitude'         => (string) $p['longitude'],
                'featured'          => $p['featured'],
                'complete_status'   => $p['complete_status'],
                'published'         => 1,
                'developer'         => $p['developer'],
                'completion_date'   => $p['completion_date'],
                'units'             => $p['units'],
                'amenities'         => json_encode($p['amenities'], JSON_UNESCAPED_UNICODE),
                'created_at'        => now(),
                'updated_at'        => now(),
            ]);

            $slug = $this->generateUniqueSlug($p['title']);

            DB::table('user_project_contents')->insert([
                'user_id'          => self::USER_ID,
                'project_id'       => $projectId,
                'language_id'      => self::LANGUAGE_ID,
                'title'            => $p['title'],
                'slug'             => $slug,
                'address'          => $p['address'],
                'description'      => $p['description'],
                'meta_keyword'     => null,
                'meta_description' => null,
                'created_at'       => now(),
                'updated_at'       => now(),
            ]);

            foreach ($p['types'] as $type) {
                DB::table('user_project_types')->insert([
                    'user_id'      => self::USER_ID,
                    'project_id'   => $projectId,
                    'language_id'  => self::LANGUAGE_ID,
                    'title'        => $type['title'],
                    'min_area'     => $type['min_area'],
                    'max_area'     => $type['max_area'],
                    'min_price'    => $type['min_price'],
                    'max_price'    => $type['max_price'],
                    'unit'         => $type['unit'],
                    'created_at'   => now(),
                    'updated_at'   => now(),
                ]);
            }

            $inserted++;
            $this->command->info("✓ #{$inserted} [{$p['city_label']}] {$p['title']}");
        }

        $this->command->info("\nDone. Inserted {$inserted}, skipped {$skipped} (already existed) — projects for user kkkkk (id=".self::USER_ID.").");
    }

    private function generateUniqueSlug(string $title): string
    {
        $slug = function_exists('make_slug') ? make_slug($title) : Str::slug($title);
        if ($slug === '' || $slug === '-') {
            $slug = 'project-' . Str::lower(Str::random(10));
        }
        $original = $slug;
        $counter    = 1;

        while (DB::table('user_project_contents')->where('slug', $slug)->exists()) {
            $slug = $original . '-' . $counter++;
        }

        return $slug;
    }

    private function buildProjects(): void
    {
        $amenitiesDefault = [
            'مواقف سيارات',
            'أمن وحراسة 24 ساعة',
            'مصاعد',
        ];

        // Riyadh (city center ~24.70, 46.73)
        $this->projects[] = $this->row(
            'الرياض',
            24.712, 46.675,
            'برج النخيل السكني',
            'حي النرجس، الرياض، المملكة العربية السعودية',
            'مشروع سكني راقٍ يضم شققاً بإطلالات بانورامية، واجهات زجاجية حديثة، ومرافق عائلية متكاملة.',
            'شركة نخيل العقارية',
            850000, 2800000,
            120,
            '2026-06-01',
            1, 1,
            array_merge($amenitiesDefault, ['صالة رياضية', 'مسبح']),
            [
                ['title' => 'شقق 3 غرف', 'min_area' => 110, 'max_area' => 160, 'min_price' => 850000, 'max_price' => 1400000, 'unit' => 'م²'],
                ['title' => 'شقق 4 غرف', 'min_area' => 160, 'max_area' => 220, 'min_price' => 1400000, 'max_price' => 2800000, 'unit' => 'م²'],
            ]
        );

        $this->projects[] = $this->row(
            'الرياض',
            24.758, 46.802,
            'مجمع الواحة ريزيدنس',
            'حي العارض، الرياض، المملكة العربية السعودية',
            'كمبوند مغلق بخدمات متكاملة ومساحات خضراء واسعة ومدارس قريبة.',
            'مجموعة الواحة للتطوير',
            1200000, 4200000,
            200,
            '2027-03-01',
            1, 0,
            array_merge($amenitiesDefault, ['ملاعب أطفال', 'مسجد']),
            [
                ['title' => 'فيلل', 'min_area' => 280, 'max_area' => 400, 'min_price' => 2200000, 'max_price' => 4200000, 'unit' => 'م²'],
            ]
        );

        $this->projects[] = $this->row(
            'الرياض',
            24.685, 46.712,
            'ريزيدنس الرياض بلازا',
            'حي الديرة، الرياض، المملكة العربية السعودية',
            'برج متعدد الاستخدامات بموقع استراتيجي قرب المراكز التجارية والمواصلات.',
            'بلازا العقارية',
            650000, 1900000,
            85,
            '2026-12-01',
            0, 0,
            array_merge($amenitiesDefault, ['محلات تجارية أرضية']),
            [
                ['title' => 'شقق استوديو وغرفة', 'min_area' => 55, 'max_area' => 95, 'min_price' => 650000, 'max_price' => 1100000, 'unit' => 'م²'],
                ['title' => 'شقق غرفتين', 'min_area' => 95, 'max_area' => 130, 'min_price' => 1100000, 'max_price' => 1900000, 'unit' => 'م²'],
            ]
        );

        $this->projects[] = $this->row(
            'الرياض',
            24.824, 46.698,
            'كمبوند الدرعية جاردنز',
            'الدرعية، الرياض، المملكة العربية السعودية',
            'فيلل وتاون هاوس بأسلوب معماري يعكس الطابع المحلي مع أحدث التشطيبات.',
            'درعية للتطوير العقاري',
            1800000, 6500000,
            95,
            '2028-01-01',
            1, 1,
            array_merge($amenitiesDefault, ['نادي صحي', 'ممرات مشاة']),
            [
                ['title' => 'تاون هاوس', 'min_area' => 200, 'max_area' => 280, 'min_price' => 1800000, 'max_price' => 3200000, 'unit' => 'م²'],
                ['title' => 'فيلل مستقلة', 'min_area' => 350, 'max_area' => 520, 'min_price' => 3500000, 'max_price' => 6500000, 'unit' => 'م²'],
            ]
        );

        $this->projects[] = $this->row(
            'الرياض',
            24.767, 46.728,
            'أبراج الملك فهد ليفينغ',
            'طريق الملك فهد، الرياض، المملكة العربية السعودية',
            'مجمع أبراج سكنية بإطلالة حضرية، مناسب للعائلات والمهنيين.',
            'شركة أبراج المملكة',
            950000, 3100000,
            240,
            '2026-09-15',
            1, 1,
            array_merge($amenitiesDefault, ['سكاي لاونج']),
            [
                ['title' => 'شقق دوبلكس', 'min_area' => 180, 'max_area' => 260, 'min_price' => 1900000, 'max_price' => 3100000, 'unit' => 'م²'],
                ['title' => 'شقق عادية', 'min_area' => 95, 'max_area' => 150, 'min_price' => 950000, 'max_price' => 1800000, 'unit' => 'م²'],
            ]
        );

        // Jeddah (~21.49, 39.18)
        $this->projects[] = $this->row(
            'جدة',
            21.543, 39.172,
            'ذا لين ووترفرونت',
            'كورنيش جدة، المملكة العربية السعودية',
            'أبراج فاخرة على البحر الأحمر مع ممشى ومرافق ضيافة عالمية.',
            'جدة ووترفرونت ديفلوبمنت',
            1400000, 5500000,
            180,
            '2027-06-01',
            1, 0,
            array_merge($amenitiesDefault, ['شاطئ خاص', 'مطاعم']),
            [
                ['title' => 'شقق بإطلالة بحر', 'min_area' => 120, 'max_area' => 200, 'min_price' => 2200000, 'max_price' => 5500000, 'unit' => 'م²'],
            ]
        );

        $this->projects[] = $this->row(
            'جدة',
            21.512, 39.219,
            'كورنيش جدة ريزيدنس',
            'حي الزمرد، جدة، المملكة العربية السعودية',
            'مساكن عصرية قرب الكورنيش مع إطلالات بحرية وخدمات فندقية.',
            'كورنيش جدة العقارية',
            1100000, 3800000,
            150,
            '2026-11-01',
            0, 1,
            array_merge($amenitiesDefault, ['مسبح', 'سبا']),
            [
                ['title' => 'شقق 2–3 غرف', 'min_area' => 95, 'max_area' => 180, 'min_price' => 1100000, 'max_price' => 3800000, 'unit' => 'م²'],
            ]
        );

        $this->projects[] = $this->row(
            'جدة',
            21.485, 39.195,
            'برج البحر الأحمر تاور',
            'حي الأمواج، جدة، المملكة العربية السعودية',
            'برج سكني بتصميم معاصر وواجهة بحرية، قريب من المرافق التعليمية.',
            'البحر الأحمر للتطوير',
            780000, 2400000,
            110,
            '2026-04-01',
            1, 0,
            $amenitiesDefault,
            [
                ['title' => 'شقق', 'min_area' => 75, 'max_area' => 140, 'min_price' => 780000, 'max_price' => 2400000, 'unit' => 'م²'],
            ]
        );

        $this->projects[] = $this->row(
            'جدة',
            21.498, 39.158,
            'مجمع الشرق الأوسط بلازا',
            'حي الصوارى، جدة، المملكة العربية السعودية',
            'وحدات سكنية وتجارية في مجمع متعدد الاستخدامات.',
            'شرق أوسط بلازا',
            520000, 1600000,
            90,
            '2025-12-01',
            0, 1,
            array_merge($amenitiesDefault, ['موقف زوار']),
            [
                ['title' => 'شقق', 'min_area' => 70, 'max_area' => 125, 'min_price' => 520000, 'max_price' => 1600000, 'unit' => 'م²'],
            ]
        );

        $this->projects[] = $this->row(
            'جدة',
            21.558, 39.188,
            'أمواج ليفينغ كومباوند',
            'حي الأمواج، جدة، المملكة العربية السعودية',
            'كمبوند عائلي بمساحات خضراء ومرافق ترفيهية للأطفال.',
            'أمواج العقارية',
            1350000, 4100000,
            160,
            '2027-01-15',
            1, 1,
            array_merge($amenitiesDefault, ['ملعب', 'نادي أطفال']),
            [
                ['title' => 'فيلل وشقق', 'min_area' => 140, 'max_area' => 320, 'min_price' => 1350000, 'max_price' => 4100000, 'unit' => 'م²'],
            ]
        );

        // Al Khobar (~26.31, 50.21)
        $this->projects[] = $this->row(
            'الخبر',
            26.318, 50.208,
            'برج الخبر التجاري لايف',
            'حي التحلية، الخبر، المملكة العربية السعودية',
            'برج سكني–تجاري في قلب الخبر، قريب من المطاعم والخدمات.',
            'الخبر لايف للتطوير',
            890000, 2700000,
            130,
            '2026-08-01',
            1, 0,
            array_merge($amenitiesDefault, ['طابق تجاري']),
            [
                ['title' => 'شقق', 'min_area' => 88, 'max_area' => 165, 'min_price' => 890000, 'max_price' => 2700000, 'unit' => 'م²'],
            ]
        );

        $this->projects[] = $this->row(
            'الخبر',
            26.305, 50.195,
            'ذا جيت الخبر',
            'حي الراكة، الخبر، المملكة العربية السعودية',
            'مجمع سكني حديث بتصميم أوروبي ومساحات مفتوحة.',
            'ذا جيت العقارية',
            1050000, 3300000,
            140,
            '2027-04-01',
            0, 1,
            array_merge($amenitiesDefault, ['حديقة سطحية']),
            [
                ['title' => 'شقق غرفتين وثلاث', 'min_area' => 100, 'max_area' => 190, 'min_price' => 1050000, 'max_price' => 3300000, 'unit' => 'م²'],
            ]
        );

        $this->projects[] = $this->row(
            'الخبر',
            26.298, 50.222,
            'مجمع النخيل الخبر',
            'حي الحزام الأخضر، الخبر، المملكة العربية السعودية',
            'فيلل وشقق في بيئة هادئة مع مساحات خضراء.',
            'نخيل الشرقية',
            1600000, 4800000,
            75,
            '2028-03-01',
            1, 1,
            array_merge($amenitiesDefault, ['مسبح أطفال']),
            [
                ['title' => 'فيلل', 'min_area' => 300, 'max_area' => 450, 'min_price' => 2600000, 'max_price' => 4800000, 'unit' => 'م²'],
                ['title' => 'شقق', 'min_area' => 120, 'max_area' => 200, 'min_price' => 1600000, 'max_price' => 2900000, 'unit' => 'م²'],
            ]
        );

        $this->projects[] = $this->row(
            'الخبر',
            26.312, 50.188,
            'الخبر جراند ريزيدنس',
            'حي الثقبة، الخبر، المملكة العربية السعودية',
            'أبراج سكنية بإطلالة على الخليج مع مرافق عصرية.',
            'جراند الشرقية',
            980000, 2900000,
            115,
            '2026-10-01',
            0, 0,
            $amenitiesDefault,
            [
                ['title' => 'شقق', 'min_area' => 92, 'max_area' => 175, 'min_price' => 980000, 'max_price' => 2900000, 'unit' => 'م²'],
            ]
        );

        // Dammam (~26.44, 50.11)
        $this->projects[] = $this->row(
            'الدمام',
            26.448, 50.098,
            'برج الدمام تاور',
            'حي الشاطئ الشرقي، الدمام، المملكة العربية السعودية',
            'برج سكني بموقع مميز قرب الكورنيش والخدمات.',
            'الدمام تاور العقارية',
            720000, 2100000,
            100,
            '2026-05-01',
            1, 0,
            $amenitiesDefault,
            [
                ['title' => 'شقق', 'min_area' => 72, 'max_area' => 150, 'min_price' => 720000, 'max_price' => 2100000, 'unit' => 'م²'],
            ]
        );

        $this->projects[] = $this->row(
            'الدمام',
            26.436, 50.118,
            'مجمع الفنار السكني',
            'حي الفنار، الدمام، المملكة العربية السعودية',
            'وحدات سكنية عائلية بمساحات متنوعة وتشطيبات جاهزة.',
            'الفنار للتطوير',
            680000, 1950000,
            88,
            '2026-07-01',
            0, 1,
            array_merge($amenitiesDefault, ['حديقة مجتمعية']),
            [
                ['title' => 'شقق', 'min_area' => 85, 'max_area' => 155, 'min_price' => 680000, 'max_price' => 1950000, 'unit' => 'م²'],
            ]
        );

        $this->projects[] = $this->row(
            'الدمام',
            26.462, 50.088,
            'ريزيدنس الخليج الدمام',
            'حي النخيل، الدمام، المملكة العربية السعودية',
            'مجمع سكني قرب الحدائق والمدارس مع أمن على مدار الساعة.',
            'خليج الدمام العقارية',
            750000, 2200000,
            105,
            '2027-02-01',
            1, 1,
            array_merge($amenitiesDefault, ['مسجد داخل المجمع']),
            [
                ['title' => 'شقق 2 غرف', 'min_area' => 80, 'max_area' => 120, 'min_price' => 750000, 'max_price' => 1400000, 'unit' => 'م²'],
                ['title' => 'شقق 3 غرف', 'min_area' => 120, 'max_area' => 175, 'min_price' => 1400000, 'max_price' => 2200000, 'unit' => 'م²'],
            ]
        );

        $this->projects[] = $this->row(
            'الدمام',
            26.429, 50.132,
            'الدمام مارينا فيو',
            'حي الشاطئ، الدمام، المملكة العربية السعودية',
            'إطلالة بحرية مع شقق فاخرة ومرافق ضيافة.',
            'مارينا الشرقية',
            1250000, 3900000,
            95,
            '2028-06-01',
            1, 0,
            array_merge($amenitiesDefault, ['ممر كورنيش', 'مقهى']),
            [
                ['title' => 'شقق بإطلالة بحر', 'min_area' => 110, 'max_area' => 220, 'min_price' => 1250000, 'max_price' => 3900000, 'unit' => 'م²'],
            ]
        );

        // Extra Riyadh + Jeddah to reach 20 total — we have: 5 Riyadh + 5 Jeddah + 4 Khobar + 4 Dammam = 18. Add 2 more.
        $this->projects[] = $this->row(
            'الرياض',
            24.731, 46.761,
            'سكاي لاين الرياض',
            'حي الملقا، الرياض، المملكة العربية السعودية',
            'أبراج سكنية راقية في شمال الرياض مع مرافق أعمال وضيافة.',
            'سكاي لاين السعودية',
            1500000, 4500000,
            175,
            '2027-09-01',
            1, 1,
            array_merge($amenitiesDefault, ['قاعة اجتماعات', 'مكتب مشترك']),
            [
                ['title' => 'بنتهاوس وشقق', 'min_area' => 140, 'max_area' => 350, 'min_price' => 2200000, 'max_price' => 4500000, 'unit' => 'م²'],
                ['title' => 'شقق', 'min_area' => 95, 'max_area' => 180, 'min_price' => 1500000, 'max_price' => 3200000, 'unit' => 'م²'],
            ]
        );

        $this->projects[] = $this->row(
            'جدة',
            21.527, 39.165,
            'جدة هايتس ريزيدنس',
            'حي الفيصلية، جدة، المملكة العربية السعودية',
            'شقق ودوبلكس في موقع حيوي قرب الطرق الرئيسية.',
            'جدة هايتس',
            880000, 2600000,
            125,
            '2026-02-15',
            0, 0,
            $amenitiesDefault,
            [
                ['title' => 'شقق ودوبلكس', 'min_area' => 90, 'max_area' => 200, 'min_price' => 880000, 'max_price' => 2600000, 'unit' => 'م²'],
            ]
        );
    }

    /**
     * @param  array<int, array<string, mixed>>  $types
     * @param  list<string>  $amenities
     */
    private function row(
        string $cityLabel,
        float $lat,
        float $lng,
        string $title,
        string $address,
        string $description,
        string $developer,
        float $minPrice,
        float $maxPrice,
        int $units,
        string $completionDate,
        int $featured,
        int $completeStatus,
        array $amenities,
        array $types
    ): array {
        return [
            'city_label'      => $cityLabel,
            'latitude'        => $lat,
            'longitude'       => $lng,
            'title'           => $title,
            'address'         => $address,
            'description'     => $description,
            'developer'       => $developer,
            'min_price'       => $minPrice,
            'max_price'       => $maxPrice,
            'units'           => $units,
            'completion_date' => $completionDate,
            'featured'        => $featured,
            'complete_status' => $completeStatus,
            'amenities'       => $amenities,
            'types'           => $types,
        ];
    }
}
