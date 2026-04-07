<?php

namespace App\Exports;

use App\Models\User\RealestateManagement\Property;
use App\Support\PropertyExcelMapping;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class PropertiesExport implements FromQuery, WithHeadings, WithMapping, WithTitle, WithEvents
{
    protected $userId;
    protected $allowedUserIds;
    protected $filters;
    protected $isTemplate;

    public function __construct($userId, array $allowedUserIds, array $filters = [], $isTemplate = false)
    {
        $this->userId = $userId;
        $this->allowedUserIds = $allowedUserIds;
        $this->filters = $filters;
        $this->isTemplate = $isTemplate;
    }

    public function query()
    {
        $query = Property::query()
            ->with([
                'category:id,name',
                'user:id,username,first_name,last_name',
                'contents' => function ($q) {
                    $q->with([
                        'city:id,name_ar,name_en',
                        'state:id,name',
                        'country:id,name',
                    ]);
                },
                'galleryImages:id,property_id,image',
                'proertyAmenities.amenity:id,name',
                'UserPropertyCharacteristics',
                'specifications:id,property_id,label,value',
                'project.contents:id,project_id,title',
                'building:id,name',
                'creator:id,first_name,last_name,username',
            ])
            ->whereIn('user_id', $this->allowedUserIds);

        // Apply property IDs filter if provided (for selected properties export)
        if (!empty($this->filters['ids']) && is_array($this->filters['ids']) && count($this->filters['ids']) > 0) {
            $query->whereIn('id', $this->filters['ids']);
        }

        // Apply date range filter (optional if IDs are provided)
        if (!empty($this->filters['date_from'])) {
            $query->whereDate('created_at', '>=', $this->filters['date_from']);
        }
        if (!empty($this->filters['date_to'])) {
            $query->whereDate('created_at', '<=', $this->filters['date_to']);
        }

        // Apply purpose filter
        if (!empty($this->filters['purposes_filter'])) {
            $query->where('purpose', $this->filters['purposes_filter']);
        }
        if (!empty($this->filters['purpose'])) {
            $query->where('purpose', $this->filters['purpose']);
        }

        // Apply type filter
        if (!empty($this->filters['type'])) {
            $query->where('type', $this->filters['type']);
        }

        // Apply price filters
        if (!empty($this->filters['price_from'])) {
            $query->where('price', '>=', $this->filters['price_from']);
        }
        if (!empty($this->filters['price_to'])) {
            $query->where('price', '<=', $this->filters['price_to']);
        }

        // Apply area filters
        if (!empty($this->filters['area_from'])) {
            $query->where('area', '>=', $this->filters['area_from']);
        }
        if (!empty($this->filters['area_to'])) {
            $query->where('area', '<=', $this->filters['area_to']);
        }

        // Apply beds filter
        if (!empty($this->filters['beds'])) {
            $query->where('beds', $this->filters['beds']);
        }

        // Apply bath filter
        if (!empty($this->filters['bath'])) {
            $query->where('bath', $this->filters['bath']);
        }

        // Apply category filter
        if (!empty($this->filters['category_id'])) {
            $query->where('category_id', $this->filters['category_id']);
        }

        // Apply status filter
        if (isset($this->filters['status']) && $this->filters['status'] !== '') {
            $query->where('status', $this->filters['status']);
        }

        // Apply featured filter
        if (isset($this->filters['featured']) && $this->filters['featured'] !== '') {
            $query->where('featured', $this->filters['featured']);
        }

        // Apply city filter
        if (!empty($this->filters['city_id'])) {
            $query->whereHas('contents', function ($q) {
                $q->where('city_id', $this->filters['city_id']);
            });
        }

        // Apply district filter
        if (!empty($this->filters['district_id'])) {
            $query->whereHas('contents', function ($q) {
                $q->where('state_id', $this->filters['district_id']);
            });
        }

        // Apply search filter (title/address)
        if (!empty($this->filters['search'])) {
            $search = $this->filters['search'];
            $query->whereHas('contents', function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('address', 'like', "%{$search}%");
            });
        }

        // Apply features filter
        if (!empty($this->filters['features'])) {
            $featuresArray = explode(',', $this->filters['features']);
            foreach ($featuresArray as $feature) {
                $feature = trim($feature);
                $query->whereJsonContains('features', $feature);
            }
        }

        return $query->orderBy('created_at', 'desc');
    }

    public function headings(): array
    {
        if ($this->isTemplate) {
            return [
                'عنوان الإعلان', 'السعر', 'العنوان', 'الوصف', 'الغرض', 'النوع', 'المساحة',
                'غرف النوم', 'دورات المياه', 'المدينة', 'الحي', 'الصورة الرئيسية', 'رابط الفيديو',
                'الحالة', 'السعر للمتر', 'معرض الصور',
                'مصعد', 'أمن', 'كاميرات مراقبة', 'تكييف مركزي',
                'تدفئة مركزية', 'صيانة', 'بواب', 'إنترنت',
                'مرافق إضافية', 'رقم الوحدة', 'رقم الطابق', 'عمر المبنى',
                'نوع الإطلالة', 'مفروش', 'مواقف السيارات', 'بلكونة', 'غرفة خادمة',
                'غرفة تخزين', 'مسبح', 'صالة رياضية', 'مساحة الحديقة', 'المواصفات',
                'طريقة الدفع', 'مميز', 'مميزات'
            ];
        }

        return [
            'المعرّف',
            'عنوان الإعلان',
            'الوصف',
            'العنوان',
            'المدينة',
            'الحي',
            'المنطقة',
            'الدولة',
            'السعر',
            'السعر للمتر',
            'الغرض',
            'النوع',
            'المساحة',
            'الحجم',
            'غرف النوم',
            'دورات المياه',
            'الحالة',
            'مميز',
            'حالة العقار',
            'التصنيف',
            'خط العرض',
            'خط الطول',
            'اسم المشروع',
            'اسم المبنى',
            'الصورة الرئيسية',
            'معرض الصور',
            'رابط الفيديو',
            'الجولة الافتراضية',
            'صور التخطيط',
            'المرافق',
            // Individual amenity columns (for import compatibility)
            'مصعد',
            'أمن',
            'كاميرات مراقبة',
            'تكييف مركزي',
            'تدفئة مركزية',
            'صيانة',
            'بواب',
            'إنترنت',
            'مرافق إضافية',
            'مميزات',
            'الأسئلة الشائعة',
            'المواصفات',
            // Individual specification columns (for import compatibility)
            'رقم الوحدة',
            'نوع الإطلالة',
            'مفروش',
            'مواقف السيارات',
            'صالة رياضية',
            'مساحة الحديقة',
            'طريقة الدفع',
            'رقم عداد المياه',
            'رقم عداد الكهرباء',
            'رقم الصك',
            'إظهار الحجوزات',
            'ترتيب',
            'ترتيب المميز',
            // UserPropertyCharacteristics fields
            'معرّف الواجهة',
            'الطول',
            'العرض',
            'عرض الشارع شمال',
            'عرض الشارع جنوب',
            'عرض الشارع شرق',
            'عرض الشارع غرب',
            'عمر المبنى',
            'الغرف',
            'دورات المياه',
            'الطوابق',
            'رقم الطابق',
            'غرفة سائق',
            'غرفة خادمة',
            'غرفة طعام',
            'غرفة معيشة',
            'مجلس',
            'غرفة تخزين',
            'قبو',
            'مسبح',
            'مطبخ',
            'بلكونة',
            'حديقة',
            'ملحق',
            'مصعد',
            'موقف خاص',
            'مساحة الخصائص',
            // User information
            'معرّف المستخدم',
            'اسم المستخدم',
            'أنشئ بواسطة',
            'اسم المنشئ',
            'تاريخ الإنشاء',
            'تاريخ التحديث',
        ];
    }

    public function map($property): array
    {
        $content = $property->contents->first();
        $characteristics = $property->UserPropertyCharacteristics;
        
        // Get location names
        $cityName = $content?->city?->name_ar ?? $content?->city?->name_en ?? '';
        $districtName = $content?->state?->name ?? '';
        $stateName = '';
        $countryName = $content?->country?->name ?? '';

        // Get gallery images as comma-separated URLs
        $galleryImages = $property->galleryImages->map(function ($img) {
            return asset($img->image);
        })->implode(', ');

        // Get floor planning images as comma-separated URLs
        $floorPlanningImages = '';
        if (is_array($property->floor_planning_image)) {
            $floorPlanningImages = collect($property->floor_planning_image)
                ->map(fn($img) => asset($img))
                ->filter()
                ->implode(', ');
        }

        // Get amenities as comma-separated names
        $amenityNames = $property->proertyAmenities->map(function ($amenity) {
            return $amenity->amenity?->name ?? '';
        })->filter()->values();
        
        $amenities = $amenityNames->implode(', ');

        // Map individual amenity columns (for import compatibility)
        $amenityMapping = [
            'amenity_مصعد' => 'مصعد',
            'amenity_أمن' => 'أمن',
            'amenity_كاميرات_مراقبة' => 'كاميرات مراقبة',
            'amenity_تكييف_مركزي' => 'تكييف مركزي',
            'amenity_تدفئة_مركزية' => 'تدفئة مركزية',
            'amenity_صيانة' => 'صيانة',
            'amenity_بواب' => 'بواب',
            'amenity_إنترنت' => 'إنترنت',
        ];
        
        $amenityColumns = [];
        foreach ($amenityMapping as $column => $arabicName) {
            $hasAmenity = $amenityNames->contains($arabicName);
            $amenityColumns[$column] = $hasAmenity ? PropertyExcelMapping::booleanToExcel(true) : '';
        }
        
        // Get additional amenities (amenities not in the standard list)
        $standardAmenities = array_values($amenityMapping);
        $additionalAmenities = $amenityNames->reject(function ($name) use ($standardAmenities) {
            return in_array($name, $standardAmenities);
        })->implode(', ');

        // Get features as comma-separated string
        $features = is_array($property->features) 
            ? implode(', ', array_filter($property->features))
            : '';

        // Get FAQs as formatted string
        $faqs = '';
        if (is_array($property->faqs) && !empty($property->faqs)) {
            $faqStrings = collect($property->faqs)->map(function ($faq) {
                if (is_array($faq) && isset($faq['question']) && isset($faq['answer'])) {
                    return ($faq['question'] ?? '') . ': ' . ($faq['answer'] ?? '');
                }
                return '';
            })->filter()->implode(' | ');
            $faqs = $faqStrings;
        }

        // Get specifications as formatted string
        $specifications = $property->specifications->map(function ($spec) {
            return ($spec->label ?? '') . ': ' . ($spec->value ?? '');
        })->implode(' | ');
        
        // Get individual specification values (for import compatibility)
        // Check both specifications table and characteristics
        $specValues = [];
        foreach ($property->specifications as $spec) {
            $key = strtolower(str_replace(' ', '_', $spec->label ?? ''));
            $specValues[$key] = $spec->value ?? '';
        }
        
        // Get individual specification values (for import compatibility)
        // These come from the specifications table, not characteristics
        $unitNumber = $specValues['unit_number'] ?? '';
        $viewType = $specValues['view_type'] ?? '';
        $furnished = $specValues['furnished'] ?? '';
        $parkingSpaces = $specValues['parking_spaces'] ?? ($characteristics?->private_parking ? '1' : '');
        $gym = $specValues['gym'] ?? '';
        $gardenSize = $specValues['garden_size'] ?? '';

        // Get featured image URL
        $featuredImage = $property->featured_image ? asset($property->featured_image) : '';

        // Get user names
        $userName = '';
        if ($property->user) {
            $userName = trim(($property->user->first_name ?? '') . ' ' . ($property->user->last_name ?? ''));
            if (empty($userName)) {
                $userName = $property->user->username ?? '';
            }
        }

        $creatorName = '';
        if ($property->creator) {
            $creatorName = trim(($property->creator->first_name ?? '') . ' ' . ($property->creator->last_name ?? ''));
            if (empty($creatorName)) {
                $creatorName = $property->creator->username ?? '';
            }
        }

        if ($this->isTemplate) {
            return [
                $content?->title ?? '',
                $property->price ?? '',
                $content?->address ?? '',
                $content?->description ?? '',
                $property->purpose ?? '',
                $property->property_type ?? '',
                $property->area ?? '',
                $property->beds ?? '',
                $property->bath ?? '',
                $cityName,
                $districtName,
                $featuredImage,
                $property->video_url ?? '',
                $property->status ? '1' : '0',
                $property->pricePerMeter ?? '',
                $galleryImages,
                $amenityColumns['amenity_مصعد'] ?? '',
                $amenityColumns['amenity_أمن'] ?? '',
                $amenityColumns['amenity_كاميرات_مراقبة'] ?? '',
                $amenityColumns['amenity_تكييف_مركزي'] ?? '',
                $amenityColumns['amenity_تدفئة_مركزية'] ?? '',
                $amenityColumns['amenity_صيانة'] ?? '',
                $amenityColumns['amenity_بواب'] ?? '',
                $amenityColumns['amenity_إنترنت'] ?? '',
                $additionalAmenities,
                $unitNumber,
                $characteristics?->floor_number ?? '',
                $characteristics?->building_age ?? '',
                $viewType,
                $furnished,
                $parkingSpaces,
                $characteristics?->balcony ? '1' : '0',
                $characteristics?->maid_room ? '1' : '0',
                $characteristics?->storage_room ? '1' : '0',
                $characteristics?->swimming_pool ? '1' : '0',
                $gym,
                $gardenSize,
                $specifications,
                PropertyExcelMapping::paymentMethodToExcel($property->payment_method ?? null),
                $property->featured ? '1' : '0',
                $features
            ];
        }

        return [
            $property->id,
            $content?->title ?? '',
            $content?->description ?? '',
            $content?->address ?? '',
            $cityName,
            $districtName,
            $stateName,
            $countryName,
            $property->price ?? '',
            $property->pricePerMeter ?? '',
            PropertyExcelMapping::purposeToExcel($property->purpose ?? null),
            PropertyExcelMapping::typeToExcel($property->property_type ?? null),
            $property->area ?? '',
            $property->size ?? '',
            $property->beds ?? '',
            $property->bath ?? '',
            $property->status ? '1' : '0',
            $property->featured ? '1' : '0',
            PropertyExcelMapping::propertyStatusToExcel($property->property_status ?? null),
            $property->category?->name ?? '',
            $property->latitude ?? '',
            $property->longitude ?? '',
            $property->project?->contents->first()?->title ?? '',
            $property->building?->name ?? '',
            $featuredImage,
            $galleryImages,
            $property->video_url ?? '',
            $property->virtual_tour ?? '',
            $floorPlanningImages,
            $amenities,
            // Individual amenity columns
            $amenityColumns['amenity_مصعد'] ?? '',
            $amenityColumns['amenity_أمن'] ?? '',
            $amenityColumns['amenity_كاميرات_مراقبة'] ?? '',
            $amenityColumns['amenity_تكييف_مركزي'] ?? '',
            $amenityColumns['amenity_تدفئة_مركزية'] ?? '',
            $amenityColumns['amenity_صيانة'] ?? '',
            $amenityColumns['amenity_بواب'] ?? '',
            $amenityColumns['amenity_إنترنت'] ?? '',
            $additionalAmenities,
            $features,
            $faqs,
            $specifications,
            // Individual specification columns
            $unitNumber,
            $viewType,
            $furnished,
            $parkingSpaces,
            $gym,
            $gardenSize,
            PropertyExcelMapping::paymentMethodToExcel($property->payment_method ?? null),
            $property->water_meter_number ?? '',
            $property->electricity_meter_number ?? '',
            $property->deed_number ?? '',
            $property->show_reservations ? '1' : '0',
            $property->reorder ?? '',
            $property->reorder_featured ?? '',
            // UserPropertyCharacteristics fields
            $characteristics?->facade_id ?? '',
            $characteristics?->length ?? '',
            $characteristics?->width ?? '',
            $characteristics?->street_width_north ?? '',
            $characteristics?->street_width_south ?? '',
            $characteristics?->street_width_east ?? '',
            $characteristics?->street_width_west ?? '',
            $characteristics?->building_age ?? '',
            $characteristics?->rooms ?? '',
            $characteristics?->bathrooms ?? '',
            $characteristics?->floors ?? '',
            $characteristics?->floor_number ?? '',
            $characteristics?->driver_room ? '1' : '0',
            $characteristics?->maid_room ? '1' : '0',
            $characteristics?->dining_room ? '1' : '0',
            $characteristics?->living_room ? '1' : '0',
            $characteristics?->majlis ? '1' : '0',
            $characteristics?->storage_room ? '1' : '0',
            $characteristics?->basement ? '1' : '0',
            $characteristics?->swimming_pool ? '1' : '0',
            $characteristics?->kitchen ? '1' : '0',
            $characteristics?->balcony ? '1' : '0',
            $characteristics?->garden ? '1' : '0',
            $characteristics?->annex ? '1' : '0',
            $characteristics?->elevator ? '1' : '0',
            $characteristics?->private_parking ? '1' : '0',
            $characteristics?->size ?? '',
            // User information
            $property->user_id ?? '',
            $userName,
            $property->created_by ?? '',
            $creatorName,
            $property->created_at?->format('Y-m-d H:i:s') ?? '',
            $property->updated_at?->format('Y-m-d H:i:s') ?? '',
        ];
    }

    public function title(): string
    {
        return 'العقارات';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet;

                // Style the header row
                $sheet->getStyle('1:1')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => 'FFFFFF'],
                        'size' => 11,
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '4472C4'],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ]);

                $sheet->getRowDimension(1)->setRowHeight(25);

                // Auto-size columns (adjust range based on number of columns)
                $lastColumn = $sheet->getHighestColumn();
                $lastColumnIndex = Coordinate::columnIndexFromString($lastColumn);
                
                // Limit to reasonable number of columns (safety check)
                $maxColumns = min($lastColumnIndex, 100);
                
                for ($colIndex = 1; $colIndex <= $maxColumns; $colIndex++) {
                    $colLetter = Coordinate::stringFromColumnIndex($colIndex);
                    $sheet->getColumnDimension($colLetter)->setAutoSize(true);
                }
            },
        ];
    }
}
