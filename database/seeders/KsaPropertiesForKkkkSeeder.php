<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Seeds 30 realistic KSA real-estate properties for user kkkkk (user_id = 1430).
 *
 * Run with:
 *   php artisan db:seed --class=KsaPropertiesForKkkkSeeder
 */
class KsaPropertiesForKkkkSeeder extends Seeder
{
    private const USER_ID      = 1430;
    private const LANGUAGE_ID  = 1983;

    /**
     * Category IDs that already exist in api_user_categories (type=property).
     * 1=فيلا  2=شقة في برج  3=شقة في عمارة  4=أرض  5=قصر
     * 7=استراحة  8=محل تجاري  9=مكتب  13=دور في فيلا  14=آخرى
     * 15=عمارة  17=شقة في فيلا  18=شقة
     */
    private array $properties = [];

    /**
     * Royalty-free Unsplash images for KSA real-estate listings.
     */
    private array $images = [
        'https://images.unsplash.com/photo-1600596542815-ffad4c1539a9?w=800&q=80',
        'https://images.unsplash.com/photo-1613490493576-7fde63acd811?w=800&q=80',
        'https://images.unsplash.com/photo-1512917774080-9991f1c4c750?w=800&q=80',
        'https://images.unsplash.com/photo-1565402170291-8491f14678db?w=800&q=80',
        'https://images.unsplash.com/photo-1560448204-e02f11c3d0e2?w=800&q=80',
        'https://images.unsplash.com/photo-1574362848149-11496d93a7c7?w=800&q=80',
        'https://images.unsplash.com/photo-1555652736-e92021d28a10?w=800&q=80',
        'https://images.unsplash.com/photo-1570129477492-45c003edd2be?w=800&q=80',
        'https://images.unsplash.com/photo-1416331108676-a22ccb276e35?w=800&q=80',
        'https://images.unsplash.com/photo-1628744448840-55bdb2497bd4?w=800&q=80',
        'https://images.unsplash.com/photo-1497366216548-37526070297c?w=800&q=80',
        'https://images.unsplash.com/photo-1497366754035-f200586c4bd4?w=800&q=80',
        'https://images.unsplash.com/photo-1500382017468-9049fed747ef?w=800&q=80',
        'https://images.unsplash.com/photo-1501854140801-50d01698950b?w=800&q=80',
        'https://images.unsplash.com/photo-1560185893-a55cbc8c57e8?w=800&q=80',
    ];

    public function run(): void
    {
        $this->buildProperties();

        // Guard: collect all titles we want to seed; skip any that already exist.
        $existingTitles = DB::table('user_property_contents')
            ->where('user_id', self::USER_ID)
            ->pluck('title')
            ->map(fn($t) => trim($t))
            ->all();

        $inserted = 0;
        $skipped  = 0;

        foreach ($this->properties as $prop) {
            if (in_array(trim($prop['title']), $existingTitles, true)) {
                $this->command->warn("⟳ skip (already exists): {$prop['title']}");
                $skipped++;
                continue;
            }
            // Insert into user_properties
            $propertyId = DB::table('user_properties')->insertGetId([
                'user_id'           => self::USER_ID,
                'created_by'        => self::USER_ID,
                'category_id'       => $prop['category_id'],
                'featured_image'    => $prop['image'],
                'price'             => $prop['price'],
                'pricePerMeter'     => $prop['price_per_meter'],
                'purpose'           => $prop['purpose'],
                'type'              => $prop['type'],
                'beds'              => $prop['beds'],
                'bath'              => $prop['bath'],
                'area'              => $prop['area'],
                'status'            => 1,
                'featured'          => 0,
                'features'          => json_encode($prop['features'] ?? []),
                'latitude'          => $prop['latitude'],
                'longitude'         => $prop['longitude'],
                'completion_status' => 'complete',
                'show_reservations' => 1,
                'reorder'           => 0,
                'reorder_featured'  => 0,
                'created_at'        => now(),
                'updated_at'        => now(),
            ]);

            // Insert into user_property_contents
            $slug = $this->generateUniqueSlug($prop['title']);

            DB::table('user_property_contents')->insert([
                'user_id'          => self::USER_ID,
                'property_id'      => $propertyId,
                'language_id'      => self::LANGUAGE_ID,
                'category_id'      => $prop['category_id'],
                'country_id'       => null,
                'state_id'         => $prop['district_id'],
                'city_id'          => $prop['city_id'],
                'title'            => $prop['title'],
                'slug'             => $slug,
                'address'          => $prop['address'],
                'description'      => $prop['description'],
                'meta_keyword'     => null,
                'meta_description' => null,
                'created_at'       => now(),
                'updated_at'       => now(),
            ]);

            $inserted++;
            $this->command->info("✓ #{$inserted} [{$prop['city_name']}] {$prop['title']}");
        }

        $this->command->info("\nDone. Inserted {$inserted}, skipped {$skipped} (already existed) — user kkkkk (id=".self::USER_ID.").");
    }

    private function generateUniqueSlug(string $title): string
    {
        $slug = Str::slug($title);
        $original = $slug;
        $counter  = 1;

        while (DB::table('user_property_contents')->where('slug', $slug)->exists()) {
            $slug = $original . '-' . $counter++;
        }

        return $slug ?: Str::random(12);
    }

    private function buildProperties(): void
    {
        // ─── RIYADH (city_id=3) — 12 listings ────────────────────────────────────
        $this->add([
            'city_id'      => 3,
            'city_name'    => 'الرياض',
            'district_id'  => 10100003148, // حي العارض
            'category_id'  => 3,           // شقة في عمارة
            'type'         => 'residential',
            'purpose'      => 'rent',
            'beds'         => 4,
            'bath'         => 3,
            'area'         => 165,
            'price'        => 32000,
            'latitude'     => 24.867516,
            'longitude'    => 46.628668,
            'title'        => 'شقة للإيجار في حي العارض، الرياض',
            'address'      => 'حي العارض، الرياض 13212، المملكة العربية السعودية',
            'description'  => 'شقة مميزة للإيجار في حي العارض بالرياض، تتميز بتشطيبات عالية الجودة وموقع استراتيجي قريب من الخدمات والمدارس. تحتوي على 4 غرف نوم و3 حمامات ومطبخ مجهز وصالة واسعة.',
            'features'     => ['مكيف', 'موقف سيارة', 'حارس أمن'],
            'image_index'  => 0,
        ]);

        $this->add([
            'city_id'      => 3,
            'city_name'    => 'الرياض',
            'district_id'  => 10100003150, // حي النرجس
            'category_id'  => 1,           // فيلا
            'type'         => 'residential',
            'purpose'      => 'sale',
            'beds'         => 5,
            'bath'         => 4,
            'area'         => 420,
            'price'        => 2500000,
            'latitude'     => 24.812345,
            'longitude'    => 46.690123,
            'title'        => 'فيلا للبيع في حي النرجس، الرياض',
            'address'      => 'حي النرجس، الرياض 13321، المملكة العربية السعودية',
            'description'  => 'فيلا فاخرة للبيع في حي النرجس الراقي بشمال الرياض. تتكون من دورين مع ملحق وحوش واسع ومسبح. تشطيبات إيطالية فاخرة وموقع هادئ ومتميز.',
            'features'     => ['مسبح', 'حديقة', 'غرفة خادمة', 'موقف سيارات'],
            'image_index'  => 1,
        ]);

        $this->add([
            'city_id'      => 3,
            'city_name'    => 'الرياض',
            'district_id'  => 10100003018, // حي السويدي
            'category_id'  => 18,          // شقة
            'type'         => 'residential',
            'purpose'      => 'sale',
            'beds'         => 3,
            'bath'         => 2,
            'area'         => 148,
            'price'        => 780000,
            'latitude'     => 24.766317,
            'longitude'    => 46.735797,
            'title'        => 'شقة للبيع في حي السويدي، الرياض',
            'address'      => 'حي السويدي، الرياض 12781، المملكة العربية السعودية',
            'description'  => 'شقة للبيع في موقع مميز بحي السويدي، تتكون من 3 غرف نوم و2 حمام ومطبخ وغرفة معيشة. مناسبة للسكن العائلي أو الاستثمار.',
            'features'     => ['مكيف', 'موقف سيارة'],
            'image_index'  => 4,
        ]);

        $this->add([
            'city_id'      => 3,
            'city_name'    => 'الرياض',
            'district_id'  => 10100003004, // حي الصناعية
            'category_id'  => 4,           // أرض
            'type'         => 'commercial',
            'purpose'      => 'sale',
            'beds'         => null,
            'bath'         => null,
            'area'         => 600,
            'price'        => 540000,
            'latitude'     => 24.698000,
            'longitude'    => 46.720000,
            'title'        => 'أرض تجارية للبيع في الرياض، حي الصناعية',
            'address'      => 'حي الصناعية، الرياض، المملكة العربية السعودية',
            'description'  => 'قطعة أرض تجارية رائعة في المنطقة الصناعية بالرياض. مساحة 600 متر مربع، واجهة على شارع رئيسي 30 متر. صالحة للبناء التجاري والمستودعات.',
            'features'     => ['واجهة شارع رئيسي', 'صك مفرز'],
            'image_index'  => 12,
        ]);

        $this->add([
            'city_id'      => 3,
            'city_name'    => 'الرياض',
            'district_id'  => 10100003001, // حي العمل
            'category_id'  => 9,           // مكتب
            'type'         => 'commercial',
            'purpose'      => 'rent',
            'beds'         => null,
            'bath'         => 2,
            'area'         => 180,
            'price'        => 82000,
            'latitude'     => 24.685000,
            'longitude'    => 46.750000,
            'title'        => 'مكتب للإيجار في حي العمل، الرياض',
            'address'      => 'حي العمل، الرياض، المملكة العربية السعودية',
            'description'  => 'مكتب تجاري فاخر للإيجار في حي العمل التجاري، بمساحة 180 متر مربع. مجهز بالكامل بالتكييف المركزي والإضاءة والشبكات. مناسب للشركات والمكاتب الاستشارية.',
            'features'     => ['تكييف مركزي', 'موقف سيارات مشترك', 'صالة استقبال'],
            'image_index'  => 10,
        ]);

        $this->add([
            'city_id'      => 3,
            'city_name'    => 'الرياض',
            'district_id'  => 10100003010, // حي الهدا
            'category_id'  => 1,           // فيلا
            'type'         => 'residential',
            'purpose'      => 'rent',
            'beds'         => 4,
            'bath'         => 4,
            'area'         => 380,
            'price'        => 125000,
            'latitude'     => 24.740000,
            'longitude'    => 46.660000,
            'title'        => 'فيلا للإيجار في حي الهدا، الرياض',
            'address'      => 'حي الهدا، الرياض، المملكة العربية السعودية',
            'description'  => 'فيلا سكنية فسيحة للإيجار السنوي في حي الهدا. تتكون من 4 غرف نوم مع دورات مياه خاصة، صالة جلوس كبيرة، مطبخ مجهز، وحديقة داخلية.',
            'features'     => ['حديقة', 'موقف سيارتين', 'غرفة خادمة'],
            'image_index'  => 2,
        ]);

        $this->add([
            'city_id'      => 3,
            'city_name'    => 'الرياض',
            'district_id'  => 10100003009, // حي الشرفية
            'category_id'  => 2,           // شقة في برج
            'type'         => 'residential',
            'purpose'      => 'sale',
            'beds'         => 2,
            'bath'         => 2,
            'area'         => 130,
            'price'        => 670000,
            'latitude'     => 24.725000,
            'longitude'    => 46.705000,
            'title'        => 'شقة في برج للبيع في حي الشرفية، الرياض',
            'address'      => 'حي الشرفية، الرياض 12244، المملكة العربية السعودية',
            'description'  => 'شقة مودرن في برج راقٍ بحي الشرفية. الطابق 12 بإطلالة بانورامية على المدينة. غرفتا نوم و2 حمام، مطبخ مفتوح، صالة مضاءة بشكل طبيعي ممتاز.',
            'features'     => ['بواب', 'مصعد', 'صالة لياقة', 'موقف سيارة'],
            'image_index'  => 9,
        ]);

        $this->add([
            'city_id'      => 3,
            'city_name'    => 'الرياض',
            'district_id'  => 10100003003, // حي الجرادية
            'category_id'  => 7,           // استراحة
            'type'         => 'residential',
            'purpose'      => 'rent',
            'beds'         => 3,
            'bath'         => 2,
            'area'         => 800,
            'price'        => 18000,
            'latitude'     => 24.671000,
            'longitude'    => 46.830000,
            'title'        => 'استراحة للإيجار في حي الجرادية، الرياض',
            'address'      => 'حي الجرادية، الرياض، المملكة العربية السعودية',
            'description'  => 'استراحة كبيرة وجميلة للإيجار اليومي والأسبوعي في حي الجرادية. تضم مسبحاً وملعب رياضياً وغرف مكيفة وشواء. مناسبة للمناسبات والأسر الكبيرة.',
            'features'     => ['مسبح', 'شواء', 'ملعب رياضي', 'كاميرات أمن'],
            'image_index'  => 13,
        ]);

        $this->add([
            'city_id'      => 3,
            'city_name'    => 'الرياض',
            'district_id'  => 10100003002, // حي النموذجية
            'category_id'  => 5,           // قصر
            'type'         => 'residential',
            'purpose'      => 'sale',
            'beds'         => 7,
            'bath'         => 6,
            'area'         => 1200,
            'price'        => 8500000,
            'latitude'     => 24.680000,
            'longitude'    => 46.770000,
            'title'        => 'قصر فاخر للبيع في حي النموذجية، الرياض',
            'address'      => 'حي النموذجية، الرياض، المملكة العربية السعودية',
            'description'  => 'قصر ملكي استثنائي في حي النموذجية الراقي. 7 غرف نوم بدورات مياه خاصة، قاعة سينما، مجلس كبير، مسبح ونافورة خارجية، حديقة محيطية. الأفخم في موقعه.',
            'features'     => ['مسبح', 'سينما خاصة', 'حديقة', 'غرف حراسة', 'نافورة'],
            'image_index'  => 3,
        ]);

        $this->add([
            'city_id'      => 3,
            'city_name'    => 'الرياض',
            'district_id'  => 10100003006, // حي الفاخرية
            'category_id'  => 3,           // شقة في عمارة
            'type'         => 'residential',
            'purpose'      => 'rent',
            'beds'         => 1,
            'bath'         => 1,
            'area'         => 80,
            'price'        => 23000,
            'latitude'     => 24.715000,
            'longitude'    => 46.740000,
            'title'        => 'شقة للإيجار في حي الفاخرية، الرياض',
            'address'      => 'حي الفاخرية، الرياض، المملكة العربية السعودية',
            'description'  => 'شقة عزاب مريحة للإيجار في حي الفاخرية. مساحة 80 م٢، غرفة نوم مع دورة مياه، مطبخ صغير وصالة. قريبة من المواصلات والخدمات اليومية.',
            'features'     => ['مكيف', 'موقف سيارة'],
            'image_index'  => 5,
        ]);

        $this->add([
            'city_id'      => 3,
            'city_name'    => 'الرياض',
            'district_id'  => 10100003007, // حي الديرة
            'category_id'  => 8,           // محل تجاري
            'type'         => 'commercial',
            'purpose'      => 'rent',
            'beds'         => null,
            'bath'         => 1,
            'area'         => 95,
            'price'        => 65000,
            'latitude'     => 24.692000,
            'longitude'    => 46.712000,
            'title'        => 'محل تجاري للإيجار في حي الديرة، الرياض',
            'address'      => 'حي الديرة، الرياض، المملكة العربية السعودية',
            'description'  => 'محل تجاري في موقع تجاري متميز بحي الديرة. واجهة زجاجية، ارتفاع سقف 4 متر، مع مستودع خلفي. يصلح للعروض التجارية والمحلات المتنوعة.',
            'features'     => ['واجهة زجاجية', 'موقع مركزي', 'مستودع خلفي'],
            'image_index'  => 11,
        ]);

        $this->add([
            'city_id'      => 3,
            'city_name'    => 'الرياض',
            'district_id'  => 10100003008, // حي أم الحمام الشرقي
            'category_id'  => 13,          // دور في فيلا
            'type'         => 'residential',
            'purpose'      => 'sale',
            'beds'         => 4,
            'bath'         => 3,
            'area'         => 250,
            'price'        => 1250000,
            'latitude'     => 24.753000,
            'longitude'    => 46.685000,
            'title'        => 'دور في فيلا للبيع في حي أم الحمام، الرياض',
            'address'      => 'حي أم الحمام الشرقي، الرياض، المملكة العربية السعودية',
            'description'  => 'دور أول في فيلا مستقلة بحي أم الحمام الشرقي. يشمل 4 غرف نوم و3 حمامات، صالة رئيسية، مطبخ مجهز وتراس خاص. مدخل مستقل.',
            'features'     => ['مدخل مستقل', 'تراس', 'موقف سيارتين'],
            'image_index'  => 6,
        ]);

        // ─── JEDDAH (city_id=18) — 8 listings ────────────────────────────────────
        $this->add([
            'city_id'      => 18,
            'city_name'    => 'جدة',
            'district_id'  => 10200018001, // حي الزمرد
            'category_id'  => 3,           // شقة في عمارة
            'type'         => 'residential',
            'purpose'      => 'rent',
            'beds'         => 3,
            'bath'         => 2,
            'area'         => 145,
            'price'        => 48000,
            'latitude'     => 21.565000,
            'longitude'    => 39.190000,
            'title'        => 'شقة للإيجار في حي الزمرد، جدة',
            'address'      => 'حي الزمرد، جدة 23456، المملكة العربية السعودية',
            'description'  => 'شقة عائلية مريحة للإيجار في حي الزمرد الراقي بجدة. 3 غرف نوم، 2 حمام، مطبخ مجهز وصالة جلوس فسيحة. قريبة من الكورنيش والمراكز التجارية.',
            'features'     => ['مكيف', 'موقف سيارة', 'أمن على مدار الساعة'],
            'image_index'  => 4,
        ]);

        $this->add([
            'city_id'      => 18,
            'city_name'    => 'جدة',
            'district_id'  => 10200018007, // حي الفردوس
            'category_id'  => 1,           // فيلا
            'type'         => 'residential',
            'purpose'      => 'sale',
            'beds'         => 5,
            'bath'         => 5,
            'area'         => 480,
            'price'        => 3200000,
            'latitude'     => 21.490000,
            'longitude'    => 39.210000,
            'title'        => 'فيلا فاخرة للبيع في حي الفردوس، جدة',
            'address'      => 'حي الفردوس، جدة، المملكة العربية السعودية',
            'description'  => 'فيلا حديثة فاخرة في حي الفردوس بجدة. دورين مع روف تراس، 5 غرف نوم، مسبح خاص وحديقة منسقة. تصميم معماري عصري مع تشطيبات أوروبية.',
            'features'     => ['مسبح', 'روف تراس', 'حديقة', 'غرفة خادمة'],
            'image_index'  => 1,
        ]);

        $this->add([
            'city_id'      => 18,
            'city_name'    => 'جدة',
            'district_id'  => 10200018005, // حي الأمواج
            'category_id'  => 2,           // شقة في برج
            'type'         => 'residential',
            'purpose'      => 'sale',
            'beds'         => 2,
            'bath'         => 2,
            'area'         => 135,
            'price'        => 950000,
            'latitude'     => 21.572000,
            'longitude'    => 39.174000,
            'title'        => 'شقة في برج للبيع بحي الأمواج، جدة',
            'address'      => 'حي الأمواج، جدة 23534، المملكة العربية السعودية',
            'description'  => 'شقة فاخرة في برج سكني حديث في منطقة الأمواج على البحر الأحمر. إطلالة بحرية ساحرة من الطابق 18. غرفتا نوم مع حمامين، مطبخ أمريكي، شرفة كبيرة.',
            'features'     => ['إطلالة بحرية', 'شرفة', 'صالة لياقة', 'موقف سيارة'],
            'image_index'  => 9,
        ]);

        $this->add([
            'city_id'      => 18,
            'city_name'    => 'جدة',
            'district_id'  => 10200018002, // حي اللؤلؤ
            'category_id'  => 6,           // مزرعة
            'type'         => 'commercial',
            'purpose'      => 'sale',
            'beds'         => null,
            'bath'         => null,
            'area'         => 5000,
            'price'        => 420000,
            'latitude'     => 21.510000,
            'longitude'    => 39.240000,
            'title'        => 'مزرعة للبيع في حي اللؤلؤ، جدة',
            'address'      => 'حي اللؤلؤ، جدة، المملكة العربية السعودية',
            'description'  => 'مزرعة استثمارية للبيع بمساحة 5000 م٢ في حي اللؤلؤ. بها بئر ارتوازية وخزان مياه، مجهزة بنظام ري حديث. مناسبة للزراعة والاستثمار الزراعي.',
            'features'     => ['بئر ارتوازية', 'نظام ري', 'سور محيطي'],
            'image_index'  => 12,
        ]);

        $this->add([
            'city_id'      => 18,
            'city_name'    => 'جدة',
            'district_id'  => 10200018006, // حي الشراع
            'category_id'  => 9,           // مكتب
            'type'         => 'commercial',
            'purpose'      => 'rent',
            'beds'         => null,
            'bath'         => 2,
            'area'         => 220,
            'price'        => 95000,
            'latitude'     => 21.555000,
            'longitude'    => 39.185000,
            'title'        => 'مكتب تجاري للإيجار في حي الشراع، جدة',
            'address'      => 'حي الشراع، جدة، المملكة العربية السعودية',
            'description'  => 'مكتب تجاري واسع ومجهز في حي الشراع التجاري بجدة. مساحة 220 م٢ مقسمة على غرف عمل وقاعة اجتماعات. مناسب للشركات الكبيرة ومكاتب المهن الحرة.',
            'features'     => ['قاعة اجتماعات', 'تكييف مركزي', 'خادمات'],
            'image_index'  => 10,
        ]);

        $this->add([
            'city_id'      => 18,
            'city_name'    => 'جدة',
            'district_id'  => 10200018066, // حي الفيصلية
            'category_id'  => 15,          // عمارة
            'type'         => 'residential',
            'purpose'      => 'sale',
            'beds'         => null,
            'bath'         => null,
            'area'         => 550,
            'price'        => 7200000,
            'latitude'     => 21.488000,
            'longitude'    => 39.195000,
            'title'        => 'عمارة سكنية للبيع في حي الفيصلية، جدة',
            'address'      => 'حي الفيصلية، جدة، المملكة العربية السعودية',
            'description'  => 'عمارة سكنية استثمارية من 5 طوابق في حي الفيصلية. تضم 10 شقق سكنية بتشطيبات جيدة. دخل إيجاري ثابت ومميز. موقع مركزي قريب من الخدمات.',
            'features'     => ['مصعد', 'موقف سيارات', 'صك موحد'],
            'image_index'  => 14,
        ]);

        $this->add([
            'city_id'      => 18,
            'city_name'    => 'جدة',
            'district_id'  => 10200018004, // حي الصوارى
            'category_id'  => 18,          // شقة
            'type'         => 'residential',
            'purpose'      => 'rent',
            'beds'         => 2,
            'bath'         => 1,
            'area'         => 110,
            'price'        => 39000,
            'latitude'     => 21.502000,
            'longitude'    => 39.178000,
            'title'        => 'شقة للإيجار في حي الصوارى، جدة',
            'address'      => 'حي الصوارى، جدة، المملكة العربية السعودية',
            'description'  => 'شقة مريحة للإيجار في حي الصوارى. غرفتا نوم، حمام، مطبخ مجهز وصالة جلوس. قريبة من المدارس والأسواق. تصلح للأسر الصغيرة.',
            'features'     => ['مكيف', 'خزان ماء'],
            'image_index'  => 6,
        ]);

        $this->add([
            'city_id'      => 18,
            'city_name'    => 'جدة',
            'district_id'  => 10200018003, // حي الياقوت
            'category_id'  => 14,          // آخرى
            'type'         => 'commercial',
            'purpose'      => 'rent',
            'beds'         => null,
            'bath'         => 2,
            'area'         => 300,
            'price'        => 130000,
            'latitude'     => 21.543000,
            'longitude'    => 39.183000,
            'title'        => 'معرض تجاري للإيجار في حي الياقوت، جدة',
            'address'      => 'حي الياقوت، جدة، المملكة العربية السعودية',
            'description'  => 'معرض تجاري في موقع تجاري استراتيجي بحي الياقوت. واجهة 20 متر على شارع رئيسي، ارتفاع سقف 5 أمتار. يصلح لمعارض السيارات والأثاث والتجارة الكبرى.',
            'features'     => ['واجهة 20م', 'سقف عالٍ', 'موقع استراتيجي'],
            'image_index'  => 11,
        ]);

        // ─── AL KHOBAR (city_id=31) — 5 listings ─────────────────────────────────
        $this->add([
            'city_id'      => 31,
            'city_name'    => 'الخبر',
            'district_id'  => 10500031001, // حي التحلية
            'category_id'  => 3,           // شقة في عمارة
            'type'         => 'residential',
            'purpose'      => 'sale',
            'beds'         => 3,
            'bath'         => 3,
            'area'         => 160,
            'price'        => 870000,
            'latitude'     => 26.317000,
            'longitude'    => 50.207000,
            'title'        => 'شقة للبيع في حي التحلية، الخبر',
            'address'      => 'حي التحلية، الخبر 34451، المملكة العربية السعودية',
            'description'  => 'شقة عصرية للبيع في أرقى أحياء الخبر. طابق 6، 3 غرف نوم كل منها بحمام خاص، مطبخ أمريكي مجهز وصالة مفتوحة. قريبة من شارع التحلية التجاري.',
            'features'     => ['مكيف مركزي', 'موقف سيارة', 'مصعد'],
            'image_index'  => 5,
        ]);

        $this->add([
            'city_id'      => 31,
            'city_name'    => 'الخبر',
            'district_id'  => 10500031003, // حي الحزام الأخضر
            'category_id'  => 1,           // فيلا
            'type'         => 'residential',
            'purpose'      => 'sale',
            'beds'         => 4,
            'bath'         => 4,
            'area'         => 400,
            'price'        => 2900000,
            'latitude'     => 26.302000,
            'longitude'    => 50.218000,
            'title'        => 'فيلا للبيع في حي الحزام الأخضر، الخبر',
            'address'      => 'حي الحزام الأخضر، الخبر، المملكة العربية السعودية',
            'description'  => 'فيلا راقية في حي الحزام الأخضر الهادئ. 4 غرف نوم مع غرفة خادمة، صالة واسعة، مطبخ كبير، وحوش مزروع وموقف مسقوف. قريبة من المدارس الدولية.',
            'features'     => ['حديقة', 'موقف مسقوف', 'غرفة خادمة', 'مطبخ خارجي'],
            'image_index'  => 3,
        ]);

        $this->add([
            'city_id'      => 31,
            'city_name'    => 'الخبر',
            'district_id'  => 10500031002, // حي ابن سيناء
            'category_id'  => 18,          // شقة
            'type'         => 'residential',
            'purpose'      => 'rent',
            'beds'         => 2,
            'bath'         => 2,
            'area'         => 120,
            'price'        => 42000,
            'latitude'     => 26.325000,
            'longitude'    => 50.200000,
            'title'        => 'شقة للإيجار في حي ابن سيناء، الخبر',
            'address'      => 'حي ابن سيناء، الخبر، المملكة العربية السعودية',
            'description'  => 'شقة حديثة للإيجار في حي ابن سيناء بالخبر. غرفتا نوم مع حمامين، مطبخ مجهز بالأجهزة، وصالة جلوس هادئة. تشطيبات نظيفة ومريحة.',
            'features'     => ['مكيف', 'موقف سيارة'],
            'image_index'  => 7,
        ]);

        $this->add([
            'city_id'      => 31,
            'city_name'    => 'الخبر',
            'district_id'  => 10500031005, // حي التعاون
            'category_id'  => 9,           // مكتب
            'type'         => 'commercial',
            'purpose'      => 'rent',
            'beds'         => null,
            'bath'         => 2,
            'area'         => 150,
            'price'        => 78000,
            'latitude'     => 26.311000,
            'longitude'    => 50.213000,
            'title'        => 'مكتب تجاري للإيجار في حي التعاون، الخبر',
            'address'      => 'حي التعاون، الخبر، المملكة العربية السعودية',
            'description'  => 'مكتب تجاري في برج مكتبي حديث بحي التعاون. مساحة 150 م٢ مقسمة على غرف مكيفة مع إطلالة على الشارع الرئيسي. مناسب لشركات الخدمات والاستشارات.',
            'features'     => ['تكييف مركزي', 'إنترنت فيبر', 'موقف مشترك'],
            'image_index'  => 10,
        ]);

        $this->add([
            'city_id'      => 31,
            'city_name'    => 'الخبر',
            'district_id'  => 10500031006, // حي الراكة الجنوبية
            'category_id'  => 2,           // شقة في برج
            'type'         => 'residential',
            'purpose'      => 'sale',
            'beds'         => 1,
            'bath'         => 1,
            'area'         => 90,
            'price'        => 460000,
            'latitude'     => 26.299000,
            'longitude'    => 50.225000,
            'title'        => 'شقة في برج للبيع في حي الراكة الجنوبية، الخبر',
            'address'      => 'حي الراكة الجنوبية، الخبر، المملكة العربية السعودية',
            'description'  => 'شقة استوديو-بلس أنيقة في برج سكني حديث. غرفة نوم كبيرة مع غرفة دراسة، حمام، مطبخ مجهز وصالة. مناسبة للمهنيين والمستثمرين.',
            'features'     => ['أمن', 'مصعد', 'موقف سيارة'],
            'image_index'  => 8,
        ]);

        // ─── DAMMAM (city_id=13) — 5 listings ────────────────────────────────────
        $this->add([
            'city_id'      => 13,
            'city_name'    => 'الدمام',
            'district_id'  => 10500013007, // حي النخيل
            'category_id'  => 3,           // شقة في عمارة
            'type'         => 'residential',
            'purpose'      => 'rent',
            'beds'         => 3,
            'bath'         => 2,
            'area'         => 140,
            'price'        => 36000,
            'latitude'     => 26.452000,
            'longitude'    => 50.097000,
            'title'        => 'شقة للإيجار في حي النخيل، الدمام',
            'address'      => 'حي النخيل، الدمام 32252، المملكة العربية السعودية',
            'description'  => 'شقة عائلية للإيجار في حي النخيل بالدمام. 3 غرف نوم وحمامان، صالة مريحة ومطبخ مجهز. بالقرب من المدارس والمستشفيات والأسواق.',
            'features'     => ['مكيف', 'موقف سيارة', 'مخزن'],
            'image_index'  => 4,
        ]);

        $this->add([
            'city_id'      => 13,
            'city_name'    => 'الدمام',
            'district_id'  => 10500013004, // حي الفنار
            'category_id'  => 1,           // فيلا
            'type'         => 'residential',
            'purpose'      => 'sale',
            'beds'         => 5,
            'bath'         => 4,
            'area'         => 440,
            'price'        => 2300000,
            'latitude'     => 26.441000,
            'longitude'    => 50.112000,
            'title'        => 'فيلا للبيع في حي الفنار، الدمام',
            'address'      => 'حي الفنار، الدمام، المملكة العربية السعودية',
            'description'  => 'فيلا سكنية كبيرة للبيع في حي الفنار الهادئ. 5 غرف نوم بمواصفات عالية، صالة مزدوجة الارتفاع، مطبخ مجهز وحوش واسع. صك واضح.',
            'features'     => ['حوش واسع', 'موقف مسقوف', 'غرفة سائق'],
            'image_index'  => 2,
        ]);

        $this->add([
            'city_id'      => 13,
            'city_name'    => 'الدمام',
            'district_id'  => 10500013001, // حي الخالدية الشمالية
            'category_id'  => 4,           // أرض
            'type'         => 'commercial',
            'purpose'      => 'sale',
            'beds'         => null,
            'bath'         => null,
            'area'         => 800,
            'price'        => 620000,
            'latitude'     => 26.475000,
            'longitude'    => 50.085000,
            'title'        => 'أرض للبيع في حي الخالدية الشمالية، الدمام',
            'address'      => 'حي الخالدية الشمالية، الدمام، المملكة العربية السعودية',
            'description'  => 'قطعة أرض تجارية-سكنية في حي الخالدية الشمالية. مساحة 800 م٢ على شارعين. صالحة للبناء التجاري السكني وفق نظام البناء المحلي.',
            'features'     => ['على شارعين', 'صك مفرز'],
            'image_index'  => 13,
        ]);

        $this->add([
            'city_id'      => 13,
            'city_name'    => 'الدمام',
            'district_id'  => 10500013005, // حي الأثير
            'category_id'  => 18,          // شقة
            'type'         => 'residential',
            'purpose'      => 'sale',
            'beds'         => 2,
            'bath'         => 2,
            'area'         => 120,
            'price'        => 530000,
            'latitude'     => 26.435000,
            'longitude'    => 50.105000,
            'title'        => 'شقة للبيع في حي الأثير، الدمام',
            'address'      => 'حي الأثير، الدمام، المملكة العربية السعودية',
            'description'  => 'شقة حديثة للبيع في حي الأثير بالدمام. غرفتا نوم وحمامان، مطبخ مجهز وصالة. الطابق الثالث. مناسبة للعائلات الصغيرة والمستثمرين.',
            'features'     => ['مكيف', 'موقف سيارة', 'مصعد'],
            'image_index'  => 7,
        ]);

        $this->add([
            'city_id'      => 13,
            'city_name'    => 'الدمام',
            'district_id'  => 10500013006, // حي الجلوية
            'category_id'  => 8,           // محل تجاري
            'type'         => 'commercial',
            'purpose'      => 'rent',
            'beds'         => null,
            'bath'         => 1,
            'area'         => 85,
            'price'        => 58000,
            'latitude'     => 26.445000,
            'longitude'    => 50.118000,
            'title'        => 'محل تجاري للإيجار في حي الجلوية، الدمام',
            'address'      => 'حي الجلوية، الدمام، المملكة العربية السعودية',
            'description'  => 'محل تجاري في شارع تجاري حيوي بحي الجلوية. مساحة 85 م٢ مع واجهة 7 أمتار. تكييف وإضاءة LED. يصلح لمختلف أنواع التجارة والخدمات.',
            'features'     => ['واجهة 7م', 'مكيف', 'موقع حيوي'],
            'image_index'  => 11,
        ]);
    }

    private function add(array $data): void
    {
        $idx = $data['image_index'] % count($this->images);
        $data['image'] = $this->images[$idx];

        // Derive price_per_meter
        $data['price_per_meter'] = $data['area'] > 0
            ? round($data['price'] / $data['area'], 2)
            : null;

        unset($data['image_index']);
        $this->properties[] = $data;
    }
}
