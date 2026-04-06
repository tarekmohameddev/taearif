<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Seeds 60 demo real-estate projects for user kkkkk (user_id = 1430).
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

        $this->appendAdditionalProjects($amenitiesDefault);
    }

    /**
     * 40 more KSA projects (Riyadh, Jeddah, Khobar, Dammam, + selected cities).
     *
     * @param  list<string>  $amenitiesDefault
     */
    private function appendAdditionalProjects(array $amenitiesDefault): void
    {
        foreach ($this->additionalProjectBlueprints() as $b) {
            $amenities = array_merge($amenitiesDefault, $b['extra_amenities'] ?? []);
            $this->projects[] = $this->row(
                $b['city_label'],
                $b['lat'],
                $b['lng'],
                $b['title'],
                $b['address'],
                $b['description'],
                $b['developer'],
                $b['min_price'],
                $b['max_price'],
                $b['units'],
                $b['completion_date'],
                $b['featured'],
                $b['complete_status'],
                $amenities,
                $b['types'],
            );
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function additionalProjectBlueprints(): array
    {
        return [
            // —— الرياض (10) ——
            ['city_label' => 'الرياض', 'lat' => 24.745, 'lng' => 46.688, 'title' => 'برج الياسمين ريزيدنس', 'address' => 'حي الياسمين، الرياض، المملكة العربية السعودية', 'description' => 'أبراج سكنية راقية بحي الياسمين مع مرافق عائلية ومواقف واسعة.', 'developer' => 'الياسمين للتطوير', 'min_price' => 920000, 'max_price' => 2650000, 'units' => 108, 'completion_date' => '2026-08-15', 'featured' => 1, 'complete_status' => 0, 'extra_amenities' => ['صالة رياضية'], 'types' => [['title' => 'شقق 3 غرف', 'min_area' => 95, 'max_area' => 155, 'min_price' => 920000, 'max_price' => 1700000, 'unit' => 'م²'], ['title' => 'شقق 4 غرف', 'min_area' => 155, 'max_area' => 220, 'min_price' => 1700000, 'max_price' => 2650000, 'unit' => 'م²']]],
            ['city_label' => 'الرياض', 'lat' => 24.698, 'lng' => 46.758, 'title' => 'كمبوند حطين هيلز', 'address' => 'حي حطين، الرياض، المملكة العربية السعودية', 'description' => 'فيلل وشقق في كمبوند مغلق بخدمات متكاملة ومساحات خضراء.', 'developer' => 'حطين العقارية', 'min_price' => 1100000, 'max_price' => 3900000, 'units' => 142, 'completion_date' => '2027-01-20', 'featured' => 0, 'complete_status' => 1, 'extra_amenities' => ['ملعب أطفال'], 'types' => [['title' => 'فيلل', 'min_area' => 280, 'max_area' => 420, 'min_price' => 2400000, 'max_price' => 3900000, 'unit' => 'م²'], ['title' => 'شقق', 'min_area' => 110, 'max_area' => 190, 'min_price' => 1100000, 'max_price' => 2200000, 'unit' => 'م²']]],
            ['city_label' => 'الرياض', 'lat' => 24.721, 'lng' => 46.702, 'title' => 'مجمع قرطبة ليفينغ', 'address' => 'حي قرطبة، الرياض، المملكة العربية السعودية', 'description' => 'وحدات سكنية عائلية قرب المدارس والخدمات في شرق الرياض.', 'developer' => 'قرطبة للتطوير', 'min_price' => 780000, 'max_price' => 2100000, 'units' => 96, 'completion_date' => '2026-05-10', 'featured' => 1, 'complete_status' => 1, 'extra_amenities' => ['مسجد'], 'types' => [['title' => 'شقق', 'min_area' => 85, 'max_area' => 150, 'min_price' => 780000, 'max_price' => 2100000, 'unit' => 'م²']]],
            ['city_label' => 'الرياض', 'lat' => 24.688, 'lng' => 46.695, 'title' => 'أبراج العليا بلازا', 'address' => 'حي العليا، الرياض، المملكة العربية السعودية', 'description' => 'برج سكني–تجاري في قلب العليا مع وصول سريع للأعمال.', 'developer' => 'العليا بلازا', 'min_price' => 1250000, 'max_price' => 3400000, 'units' => 88, 'completion_date' => '2026-11-01', 'featured' => 0, 'complete_status' => 0, 'extra_amenities' => ['طابق تجاري'], 'types' => [['title' => 'شقق فاخرة', 'min_area' => 100, 'max_area' => 200, 'min_price' => 1250000, 'max_price' => 3400000, 'unit' => 'م²']]],
            ['city_label' => 'الرياض', 'lat' => 24.736, 'lng' => 46.642, 'title' => 'ذا جروف الرياض', 'address' => 'حي النرجس، الرياض، المملكة العربية السعودية', 'description' => 'كمبوند راقٍ بتصميم معاصر ومسارات مشي ومساحات مفتوحة.', 'developer' => 'جروف السعودية', 'min_price' => 1650000, 'max_price' => 5200000, 'units' => 64, 'completion_date' => '2028-04-01', 'featured' => 1, 'complete_status' => 0, 'extra_amenities' => ['نادي صحي', 'مسبح'], 'types' => [['title' => 'فيلل وتاون', 'min_area' => 240, 'max_area' => 400, 'min_price' => 2800000, 'max_price' => 5200000, 'unit' => 'م²'], ['title' => 'شقق', 'min_area' => 120, 'max_area' => 200, 'min_price' => 1650000, 'max_price' => 2900000, 'unit' => 'م²']]],
            ['city_label' => 'الرياض', 'lat' => 24.654, 'lng' => 46.711, 'title' => 'سكن الملز تاور', 'address' => 'حي الملز، الرياض، المملكة العربية السعودية', 'description' => 'برج سكني بإطلالة حضرية في موقع مركزي تاريخي.', 'developer' => 'الملز العقارية', 'min_price' => 690000, 'max_price' => 1850000, 'units' => 92, 'completion_date' => '2026-03-22', 'featured' => 0, 'complete_status' => 1, 'extra_amenities' => [], 'types' => [['title' => 'شقق', 'min_area' => 72, 'max_area' => 140, 'min_price' => 690000, 'max_price' => 1850000, 'unit' => 'م²']]],
            ['city_label' => 'الرياض', 'lat' => 24.709, 'lng' => 46.818, 'title' => 'برج السليمانية فيو', 'address' => 'حي السليمانية، الرياض، المملكة العربية السعودية', 'description' => 'شقق بمساحات متنوعة وتشطيبات جاهزة قرب المراكز التجارية.', 'developer' => 'السليمانية ديفلوبمنت', 'min_price' => 840000, 'max_price' => 2280000, 'units' => 118, 'completion_date' => '2027-07-15', 'featured' => 1, 'complete_status' => 1, 'extra_amenities' => ['موقف زوار'], 'types' => [['title' => 'شقق', 'min_area' => 88, 'max_area' => 165, 'min_price' => 840000, 'max_price' => 2280000, 'unit' => 'م²']]],
            ['city_label' => 'الرياض', 'lat' => 24.782, 'lng' => 46.705, 'title' => 'مجمع النخيل الشمالي', 'address' => 'شمال الرياض، المملكة العربية السعودية', 'description' => 'مجمع سكني هادئ مع حدائق مجتمعية وحراسة.', 'developer' => 'النخيل الشمالية', 'min_price' => 980000, 'max_price' => 2750000, 'units' => 134, 'completion_date' => '2026-09-30', 'featured' => 0, 'complete_status' => 0, 'extra_amenities' => ['حديقة مجتمعية'], 'types' => [['title' => 'شقق', 'min_area' => 92, 'max_area' => 175, 'min_price' => 980000, 'max_price' => 2750000, 'unit' => 'م²']]],
            ['city_label' => 'الرياض', 'lat' => 24.748, 'lng' => 46.769, 'title' => 'ريزيدنس الصحافة', 'address' => 'حي الصحافة، الرياض، المملكة العربية السعودية', 'description' => 'أبراج سكنية مناسبة للعائلات مع قرب من المدارس والحدائق.', 'developer' => 'صحافة ريزيدنس', 'min_price' => 860000, 'max_price' => 2400000, 'units' => 102, 'completion_date' => '2026-12-05', 'featured' => 1, 'complete_status' => 0, 'extra_amenities' => ['ملعب'], 'types' => [['title' => 'شقق', 'min_area' => 85, 'max_area' => 160, 'min_price' => 860000, 'max_price' => 2400000, 'unit' => 'م²']]],
            ['city_label' => 'الرياض', 'lat' => 24.766, 'lng' => 46.659, 'title' => 'كمبوند القيروان', 'address' => 'حي القيروان، الرياض، المملكة العربية السعودية', 'description' => 'فيلل وشقق في بيئة عائلية بمعايير بناء حديثة.', 'developer' => 'القيروان العقارية', 'min_price' => 1350000, 'max_price' => 4100000, 'units' => 76, 'completion_date' => '2028-02-14', 'featured' => 0, 'complete_status' => 1, 'extra_amenities' => ['مسبح أطفال'], 'types' => [['title' => 'فيلل', 'min_area' => 300, 'max_area' => 480, 'min_price' => 2600000, 'max_price' => 4100000, 'unit' => 'م²'], ['title' => 'شقق', 'min_area' => 115, 'max_area' => 195, 'min_price' => 1350000, 'max_price' => 2500000, 'unit' => 'م²']]],
            // —— جدة (10) ——
            ['city_label' => 'جدة', 'lat' => 21.551, 'lng' => 39.201, 'title' => 'برج الكورنيش جولد', 'address' => 'كورنيش جدة، المملكة العربية السعودية', 'description' => 'أبراج فاخرة على الكورنيش مع خدمات فندقية.', 'developer' => 'كورنيش جولد', 'min_price' => 1600000, 'max_price' => 6200000, 'units' => 96, 'completion_date' => '2027-05-01', 'featured' => 1, 'complete_status' => 0, 'extra_amenities' => ['مطاعم', 'سبا'], 'types' => [['title' => 'شقق بحرية', 'min_area' => 130, 'max_area' => 240, 'min_price' => 2800000, 'max_price' => 6200000, 'unit' => 'م²'], ['title' => 'شقق', 'min_area' => 95, 'max_area' => 180, 'min_price' => 1600000, 'max_price' => 3200000, 'unit' => 'م²']]],
            ['city_label' => 'جدة', 'lat' => 21.484, 'lng' => 39.187, 'title' => 'مجمع البلد التاريخي', 'address' => 'حي البلد، جدة، المملكة العربية السعودية', 'description' => 'وحدات سكنية بتصميم يعكس الطابع التاريخي مع راحة عصرية.', 'developer' => 'البلد للترميم والتطوير', 'min_price' => 720000, 'max_price' => 1950000, 'units' => 54, 'completion_date' => '2026-10-20', 'featured' => 0, 'complete_status' => 1, 'extra_amenities' => [], 'types' => [['title' => 'شقق', 'min_area' => 65, 'max_area' => 120, 'min_price' => 720000, 'max_price' => 1950000, 'unit' => 'م²']]],
            ['city_label' => 'جدة', 'lat' => 21.568, 'lng' => 39.145, 'title' => 'ذا بيتش هاوس جدة', 'address' => 'حي أبحر الشمالية، جدة، المملكة العربية السعودية', 'description' => 'شقق وفيلل ساحلية بإطلالة بحرية ومرافق ترفيهية.', 'developer' => 'بيتش هاوس جدة', 'min_price' => 1900000, 'max_price' => 7200000, 'units' => 72, 'completion_date' => '2028-01-10', 'featured' => 1, 'complete_status' => 0, 'extra_amenities' => ['شاطئ خاص'], 'types' => [['title' => 'فيلل', 'min_area' => 320, 'max_area' => 500, 'min_price' => 4500000, 'max_price' => 7200000, 'unit' => 'م²'], ['title' => 'شقق', 'min_area' => 110, 'max_area' => 200, 'min_price' => 1900000, 'max_price' => 3800000, 'unit' => 'م²']]],
            ['city_label' => 'جدة', 'lat' => 21.492, 'lng' => 39.211, 'title' => 'أبراج الحمراء سكاي', 'address' => 'حي الحمراء، جدة، المملكة العربية السعودية', 'description' => 'برج سكني عالٍ بإطلالة بانورامية على المدينة.', 'developer' => 'الحمراء سكاي', 'min_price' => 980000, 'max_price' => 3100000, 'units' => 124, 'completion_date' => '2026-07-08', 'featured' => 0, 'complete_status' => 1, 'extra_amenities' => ['سكاي لاونج'], 'types' => [['title' => 'شقق', 'min_area' => 82, 'max_area' => 175, 'min_price' => 980000, 'max_price' => 3100000, 'unit' => 'م²']]],
            ['city_label' => 'جدة', 'lat' => 21.519, 'lng' => 39.176, 'title' => 'كمبوند النهضة جاردنز', 'address' => 'حي النهضة، جدة، المملكة العربية السعودية', 'description' => 'كمبوند عائلي بمساحات خضراء ومدارس قريبة.', 'developer' => 'النهضة جاردنز', 'min_price' => 890000, 'max_price' => 2550000, 'units' => 110, 'completion_date' => '2027-03-25', 'featured' => 1, 'complete_status' => 1, 'extra_amenities' => ['ملعب'], 'types' => [['title' => 'شقق', 'min_area' => 88, 'max_area' => 165, 'min_price' => 890000, 'max_price' => 2550000, 'unit' => 'م²']]],
            ['city_label' => 'جدة', 'lat' => 21.505, 'lng' => 39.228, 'title' => 'برج الروابي بلس', 'address' => 'حي الروابي، جدة، المملكة العربية السعودية', 'description' => 'شقق بمساحات عملية وتشطيبات عالية الجودة.', 'developer' => 'الروابي بلس', 'min_price' => 650000, 'max_price' => 1780000, 'units' => 98, 'completion_date' => '2026-04-18', 'featured' => 0, 'complete_status' => 0, 'extra_amenities' => [], 'types' => [['title' => 'شقق', 'min_area' => 70, 'max_area' => 135, 'min_price' => 650000, 'max_price' => 1780000, 'unit' => 'م²']]],
            ['city_label' => 'جدة', 'lat' => 21.538, 'lng' => 39.159, 'title' => 'جدة سنترال بارك', 'address' => 'حي الزهراء، جدة، المملكة العربية السعودية', 'description' => 'مجمع سكني يطل على حديقة مجتمعية واسعة.', 'developer' => 'سنترال بارك جدة', 'min_price' => 1020000, 'max_price' => 2950000, 'units' => 88, 'completion_date' => '2027-08-12', 'featured' => 1, 'complete_status' => 0, 'extra_amenities' => ['حديقة'], 'types' => [['title' => 'شقق', 'min_area' => 95, 'max_area' => 185, 'min_price' => 1020000, 'max_price' => 2950000, 'unit' => 'م²']]],
            ['city_label' => 'جدة', 'lat' => 21.561, 'lng' => 39.131, 'title' => 'مجمع أبحر الشمالية', 'address' => 'أبحر الشمالية، جدة، المملكة العربية السعودية', 'description' => 'فيلل وشقق في موقع ساحلي راقٍ.', 'developer' => 'أبحر نورث', 'min_price' => 1450000, 'max_price' => 4800000, 'units' => 82, 'completion_date' => '2028-06-20', 'featured' => 0, 'complete_status' => 1, 'extra_amenities' => ['مسبح'], 'types' => [['title' => 'فيلل', 'min_area' => 260, 'max_area' => 400, 'min_price' => 2800000, 'max_price' => 4800000, 'unit' => 'م²'], ['title' => 'شقق', 'min_area' => 105, 'max_area' => 190, 'min_price' => 1450000, 'max_price' => 2900000, 'unit' => 'م²']]],
            ['city_label' => 'جدة', 'lat' => 21.476, 'lng' => 39.169, 'title' => 'ذا مارينا ريزيدنس جدة', 'address' => 'حي الشاطئ، جدة، المملكة العربية السعودية', 'description' => 'شقق فاخرة بإطلالة مارينا وخدمات ضيافة.', 'developer' => 'مارينا جدة', 'min_price' => 2100000, 'max_price' => 6800000, 'units' => 68, 'completion_date' => '2027-11-01', 'featured' => 1, 'complete_status' => 0, 'extra_amenities' => ['مقهى', 'مسبح'], 'types' => [['title' => 'شقق دوبلكس', 'min_area' => 160, 'max_area' => 280, 'min_price' => 3500000, 'max_price' => 6800000, 'unit' => 'م²'], ['title' => 'شقق', 'min_area' => 115, 'max_area' => 200, 'min_price' => 2100000, 'max_price' => 4200000, 'unit' => 'م²']]],
            ['city_label' => 'جدة', 'lat' => 21.514, 'lng' => 39.198, 'title' => 'برج السلامة لكس', 'address' => 'حي السلامة، جدة، المملكة العربية السعودية', 'description' => 'برج سكني في موقع حيوي قرب الخدمات والطرق السريعة.', 'developer' => 'السلامة لكس', 'min_price' => 760000, 'max_price' => 2150000, 'units' => 106, 'completion_date' => '2026-06-30', 'featured' => 0, 'complete_status' => 1, 'extra_amenities' => [], 'types' => [['title' => 'شقق', 'min_area' => 78, 'max_area' => 150, 'min_price' => 760000, 'max_price' => 2150000, 'unit' => 'م²']]],
            // —— الخبر (8) ——
            ['city_label' => 'الخبر', 'lat' => 26.322, 'lng' => 50.198, 'title' => 'برج الكورنيش الخبر', 'address' => 'كورنيش الخبر، المملكة العربية السعودية', 'description' => 'إطلالة بحرية مع شقق فاخرة ومرافق عصرية.', 'developer' => 'كورنيش الخبر', 'min_price' => 1180000, 'max_price' => 3600000, 'units' => 90, 'completion_date' => '2027-02-28', 'featured' => 1, 'complete_status' => 0, 'extra_amenities' => ['ممر مشاة'], 'types' => [['title' => 'شقق بحرية', 'min_area' => 105, 'max_area' => 200, 'min_price' => 1800000, 'max_price' => 3600000, 'unit' => 'م²'], ['title' => 'شقق', 'min_area' => 88, 'max_area' => 160, 'min_price' => 1180000, 'max_price' => 2200000, 'unit' => 'م²']]],
            ['city_label' => 'الخبر', 'lat' => 26.307, 'lng' => 50.225, 'title' => 'مجمع العقربية تاورز', 'address' => 'حي العقربية، الخبر، المملكة العربية السعودية', 'description' => 'مجمع أبراج سكنية بخدمات متكاملة.', 'developer' => 'العقربية تاورز', 'min_price' => 910000, 'max_price' => 2680000, 'units' => 112, 'completion_date' => '2026-09-15', 'featured' => 0, 'complete_status' => 1, 'extra_amenities' => ['صالة رياضية'], 'types' => [['title' => 'شقق', 'min_area' => 90, 'max_area' => 170, 'min_price' => 910000, 'max_price' => 2680000, 'unit' => 'م²']]],
            ['city_label' => 'الخبر', 'lat' => 26.291, 'lng' => 50.201, 'title' => 'ذا جيت ويست الخبر', 'address' => 'غرب الخبر، المملكة العربية السعودية', 'description' => 'كمبوند عائلي هادئ مع مساحات خضراء.', 'developer' => 'جيت ويست', 'min_price' => 1120000, 'max_price' => 3200000, 'units' => 98, 'completion_date' => '2027-06-01', 'featured' => 1, 'complete_status' => 1, 'extra_amenities' => ['ملعب'], 'types' => [['title' => 'شقق', 'min_area' => 98, 'max_area' => 185, 'min_price' => 1120000, 'max_price' => 3200000, 'unit' => 'م²']]],
            ['city_label' => 'الخبر', 'lat' => 26.328, 'lng' => 50.182, 'title' => 'الخبر بيزنس باي', 'address' => 'حي الخبر الشمالية، المملكة العربية السعودية', 'description' => 'أبراج سكنية–إدارية قرب الأعمال والخدمات.', 'developer' => 'بيزنس باي الشرقية', 'min_price' => 980000, 'max_price' => 2900000, 'units' => 86, 'completion_date' => '2026-12-01', 'featured' => 0, 'complete_status' => 0, 'extra_amenities' => ['طابق تجاري'], 'types' => [['title' => 'شقق', 'min_area' => 85, 'max_area' => 165, 'min_price' => 980000, 'max_price' => 2900000, 'unit' => 'م²']]],
            ['city_label' => 'الخبر', 'lat' => 26.315, 'lng' => 50.217, 'title' => 'مجمع ابن خلدون ريزيدنس', 'address' => 'حي ابن خلدون، الخبر، المملكة العربية السعودية', 'description' => 'شقق عائلية بمساحات واسعة وتشطيبات جاهزة.', 'developer' => 'ابن خلدون العقارية', 'min_price' => 870000, 'max_price' => 2480000, 'units' => 104, 'completion_date' => '2026-08-08', 'featured' => 1, 'complete_status' => 0, 'extra_amenities' => [], 'types' => [['title' => 'شقق', 'min_area' => 86, 'max_area' => 158, 'min_price' => 870000, 'max_price' => 2480000, 'unit' => 'م²']]],
            ['city_label' => 'الخبر', 'lat' => 26.299, 'lng' => 50.176, 'title' => 'برج الشاطئ الذهبي', 'address' => 'حي الشاطئ، الخبر، المملكة العربية السعودية', 'description' => 'موقع مميز قرب البحر مع وحدات متنوعة.', 'developer' => 'الشاطئ الذهبي', 'min_price' => 1050000, 'max_price' => 3150000, 'units' => 94, 'completion_date' => '2027-04-20', 'featured' => 0, 'complete_status' => 1, 'extra_amenities' => ['مسبح'], 'types' => [['title' => 'شقق', 'min_area' => 92, 'max_area' => 178, 'min_price' => 1050000, 'max_price' => 3150000, 'unit' => 'م²']]],
            ['city_label' => 'الخبر', 'lat' => 26.304, 'lng' => 50.209, 'title' => 'كمبوند الدانة الخبر', 'address' => 'حي الدانة، الخبر، المملكة العربية السعودية', 'description' => 'فيلل وشقق في كمبوند مغلق بأمن 24 ساعة.', 'developer' => 'الدانة للتطوير', 'min_price' => 1280000, 'max_price' => 3950000, 'units' => 72, 'completion_date' => '2028-03-15', 'featured' => 1, 'complete_status' => 0, 'extra_amenities' => ['نادي أطفال'], 'types' => [['title' => 'فيلل', 'min_area' => 270, 'max_area' => 420, 'min_price' => 2600000, 'max_price' => 3950000, 'unit' => 'م²'], ['title' => 'شقق', 'min_area' => 108, 'max_area' => 195, 'min_price' => 1280000, 'max_price' => 2400000, 'unit' => 'م²']]],
            ['city_label' => 'الخبر', 'lat' => 26.319, 'lng' => 50.193, 'title' => 'الخبر وان ريزيدنس', 'address' => 'حي الجامعة، الخبر، المملكة العربية السعودية', 'description' => 'برج سكني قرب الجامعة والخدمات الطلابية.', 'developer' => 'الخبر وان', 'min_price' => 620000, 'max_price' => 1650000, 'units' => 88, 'completion_date' => '2026-05-25', 'featured' => 0, 'complete_status' => 1, 'extra_amenities' => [], 'types' => [['title' => 'شقق', 'min_area' => 68, 'max_area' => 125, 'min_price' => 620000, 'max_price' => 1650000, 'unit' => 'م²']]],
            // —— الدمام (8) ——
            ['city_label' => 'الدمام', 'lat' => 26.441, 'lng' => 50.105, 'title' => 'برج الملك عبدالعزيز الدمام', 'address' => 'وسط الدمام، المملكة العربية السعودية', 'description' => 'برج سكني–تجاري بموقع استراتيجي في قلب المدينة.', 'developer' => 'أبراج المملكة الشرقية', 'min_price' => 810000, 'max_price' => 2350000, 'units' => 96, 'completion_date' => '2026-10-10', 'featured' => 1, 'complete_status' => 0, 'extra_amenities' => ['طابق تجاري'], 'types' => [['title' => 'شقق', 'min_area' => 80, 'max_area' => 160, 'min_price' => 810000, 'max_price' => 2350000, 'unit' => 'م²']]],
            ['city_label' => 'الدمام', 'lat' => 26.418, 'lng' => 50.128, 'title' => 'مجمع الفيصلية الشرقية', 'address' => 'حي الفيصلية، الدمام، المملكة العربية السعودية', 'description' => 'وحدات سكنية عائلية بمساحات متنوعة.', 'developer' => 'الفيصلية الشرقية', 'min_price' => 700000, 'max_price' => 1980000, 'units' => 102, 'completion_date' => '2026-04-12', 'featured' => 0, 'complete_status' => 1, 'extra_amenities' => ['حديقة'], 'types' => [['title' => 'شقق', 'min_area' => 75, 'max_area' => 145, 'min_price' => 700000, 'max_price' => 1980000, 'unit' => 'م²']]],
            ['city_label' => 'الدمام', 'lat' => 26.455, 'lng' => 50.091, 'title' => 'ذا ليك فيو الدمام', 'address' => 'حي الشاطئ الشرقي، الدمام، المملكة العربية السعودية', 'description' => 'إطلالة مائية مع ممشى ومرافق ترفيهية.', 'developer' => 'ليك فيو الدمام', 'min_price' => 1150000, 'max_price' => 3400000, 'units' => 84, 'completion_date' => '2027-09-01', 'featured' => 1, 'complete_status' => 1, 'extra_amenities' => ['مقهى'], 'types' => [['title' => 'شقق', 'min_area' => 98, 'max_area' => 190, 'min_price' => 1150000, 'max_price' => 3400000, 'unit' => 'م²']]],
            ['city_label' => 'الدمام', 'lat' => 26.467, 'lng' => 50.118, 'title' => 'كمبوند العدامة', 'address' => 'حي العدامة، الدمام، المملكة العربية السعودية', 'description' => 'كمبوند عائلي بفيلل وشقق ومساحات مفتوحة.', 'developer' => 'العدامة العقارية', 'min_price' => 990000, 'max_price' => 2850000, 'units' => 78, 'completion_date' => '2028-05-20', 'featured' => 0, 'complete_status' => 0, 'extra_amenities' => ['مسبح'], 'types' => [['title' => 'فيلل', 'min_area' => 240, 'max_area' => 380, 'min_price' => 1900000, 'max_price' => 2850000, 'unit' => 'م²'], ['title' => 'شقق', 'min_area' => 95, 'max_area' => 175, 'min_price' => 990000, 'max_price' => 2100000, 'unit' => 'م²']]],
            ['city_label' => 'الدمام', 'lat' => 26.432, 'lng' => 50.072, 'title' => 'برج المحمدية هايتس', 'address' => 'حي المحمدية، الدمام، المملكة العربية السعودية', 'description' => 'برج سكني حديث مع مواقف واسعة ومصاعد سريعة.', 'developer' => 'المحمدية هايتس', 'min_price' => 740000, 'max_price' => 2050000, 'units' => 100, 'completion_date' => '2026-07-22', 'featured' => 1, 'complete_status' => 0, 'extra_amenities' => [], 'types' => [['title' => 'شقق', 'min_area' => 78, 'max_area' => 152, 'min_price' => 740000, 'max_price' => 2050000, 'unit' => 'م²']]],
            ['city_label' => 'الدمام', 'lat' => 26.451, 'lng' => 50.134, 'title' => 'مجمع الروابي الدمام', 'address' => 'حي الروابي، الدمام، المملكة العربية السعودية', 'description' => 'شقق بأسعار تنافسية وتشطيبات جاهزة للسكن.', 'developer' => 'الروابي الشرقية', 'min_price' => 660000, 'max_price' => 1820000, 'units' => 94, 'completion_date' => '2026-03-01', 'featured' => 0, 'complete_status' => 1, 'extra_amenities' => [], 'types' => [['title' => 'شقق', 'min_area' => 70, 'max_area' => 138, 'min_price' => 660000, 'max_price' => 1820000, 'unit' => 'م²']]],
            ['city_label' => 'الدمام', 'lat' => 26.439, 'lng' => 50.101, 'title' => 'الدمام جرين فيلد', 'address' => 'حي النورس، الدمام، المملكة العربية السعودية', 'description' => 'مجمع سكني يطل على حدائق ومسطحات خضراء.', 'developer' => 'جرين فيلد الدمام', 'min_price' => 880000, 'max_price' => 2480000, 'units' => 88, 'completion_date' => '2027-02-14', 'featured' => 1, 'complete_status' => 1, 'extra_amenities' => ['حديقة مجتمعية'], 'types' => [['title' => 'شقق', 'min_area' => 88, 'max_area' => 168, 'min_price' => 880000, 'max_price' => 2480000, 'unit' => 'م²']]],
            ['city_label' => 'الدمام', 'lat' => 26.424, 'lng' => 50.089, 'title' => 'برج الكورنيش الشرقي', 'address' => 'كورنيش الدمام، المملكة العربية السعودية', 'description' => 'شقق بإطلالة بحرية وخدمات فندقية خفيفة.', 'developer' => 'الكورنيش الشرقي', 'min_price' => 1280000, 'max_price' => 3800000, 'units' => 76, 'completion_date' => '2028-08-01', 'featured' => 0, 'complete_status' => 0, 'extra_amenities' => ['مسبح', 'مقهى'], 'types' => [['title' => 'شقق بحرية', 'min_area' => 110, 'max_area' => 210, 'min_price' => 2200000, 'max_price' => 3800000, 'unit' => 'م²'], ['title' => 'شقق', 'min_area' => 95, 'max_area' => 175, 'min_price' => 1280000, 'max_price' => 2500000, 'unit' => 'م²']]],
            // —— مدن أخرى (4) ——
            ['city_label' => 'الطائف', 'lat' => 21.275, 'lng' => 40.406, 'title' => 'مجمع الهدا ريزيدنس', 'address' => 'حي الهدا، الطائف، المملكة العربية السعودية', 'description' => 'مساكن مرتفعة بمناخ معتدل وإطلالات جبلية.', 'developer' => 'الهدا للتطوير', 'min_price' => 580000, 'max_price' => 1650000, 'units' => 72, 'completion_date' => '2026-11-18', 'featured' => 1, 'complete_status' => 0, 'extra_amenities' => [], 'types' => [['title' => 'شقق', 'min_area' => 75, 'max_area' => 140, 'min_price' => 580000, 'max_price' => 1650000, 'unit' => 'م²']]],
            ['city_label' => 'مكة المكرمة', 'lat' => 21.422, 'lng' => 39.826, 'title' => 'برج مكة ليفينغ', 'address' => 'شرق مكة المكرمة، المملكة العربية السعودية', 'description' => 'وحدات سكنية بمواصفات عالية قرب الطرق الرئيسية.', 'developer' => 'مكة ليفينغ', 'min_price' => 890000, 'max_price' => 2600000, 'units' => 86, 'completion_date' => '2027-04-10', 'featured' => 0, 'complete_status' => 1, 'extra_amenities' => ['مسجد'], 'types' => [['title' => 'شقق', 'min_area' => 82, 'max_area' => 155, 'min_price' => 890000, 'max_price' => 2600000, 'unit' => 'م²']]],
            ['city_label' => 'المدينة المنورة', 'lat' => 24.498, 'lng' => 39.585, 'title' => 'مجمع قباء جاردنز', 'address' => 'حي قباء، المدينة المنورة، المملكة العربية السعودية', 'description' => 'كمبوند عائلي هادئ بجوار مناطق تاريخية.', 'developer' => 'قباء جاردنز', 'min_price' => 720000, 'max_price' => 2100000, 'units' => 68, 'completion_date' => '2026-09-05', 'featured' => 1, 'complete_status' => 1, 'extra_amenities' => ['حديقة'], 'types' => [['title' => 'شقق', 'min_area' => 78, 'max_area' => 148, 'min_price' => 720000, 'max_price' => 2100000, 'unit' => 'م²']]],
            ['city_label' => 'أبها', 'lat' => 18.232, 'lng' => 42.505, 'title' => 'أبراج السودة فيو', 'address' => 'أبها، منطقة عسير، المملكة العربية السعودية', 'description' => 'شقق بإطلالة جبلية ومناخ معتدل طوال العام.', 'developer' => 'السودة فيو', 'min_price' => 490000, 'max_price' => 1380000, 'units' => 64, 'completion_date' => '2027-01-30', 'featured' => 0, 'complete_status' => 0, 'extra_amenities' => [], 'types' => [['title' => 'شقق', 'min_area' => 70, 'max_area' => 130, 'min_price' => 490000, 'max_price' => 1380000, 'unit' => 'م²']]],
        ];
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
