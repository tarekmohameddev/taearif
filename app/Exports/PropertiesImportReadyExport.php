<?php

namespace App\Exports;

use App\Models\User\RealestateManagement\Property;
use App\Models\User\UserDistrict;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class PropertiesImportReadyExport implements WithMultipleSheets
{
    protected $userId;
    protected $allowedUserIds;
    protected $filters;

    public function __construct($userId, array $allowedUserIds, array $filters = [])
    {
        $this->userId = $userId;
        $this->allowedUserIds = $allowedUserIds;
        $this->filters = $filters;
    }

    public function sheets(): array
    {
        return [
            new PropertiesImportReadyMainSheetExport($this->userId, $this->allowedUserIds, $this->filters),
            new PropertiesImportReadyLookupSheetExport(),
        ];
    }
}

class PropertiesImportReadyMainSheetExport implements FromQuery, WithHeadings, WithMapping, WithTitle, WithEvents
{
    protected $userId;
    protected $allowedUserIds;
    protected $filters;

    public function __construct($userId, array $allowedUserIds, array $filters = [])
    {
        $this->userId = $userId;
        $this->allowedUserIds = $allowedUserIds;
        $this->filters = $filters;
    }

    public function query()
    {
        $query = Property::query()
            ->with([
                'category:id,name',
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
            ])
            ->whereIn('user_id', $this->allowedUserIds);

        // Apply property IDs filter if provided
        if (!empty($this->filters['ids']) && is_array($this->filters['ids']) && count($this->filters['ids']) > 0) {
            $query->whereIn('id', $this->filters['ids']);
        }

        // Apply date range filter
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

        // Apply search filter
        if (!empty($this->filters['search'])) {
            $search = $this->filters['search'];
            $query->whereHas('contents', function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('address', 'like', "%{$search}%");
            });
        }

        return $query->orderBy('id', 'desc');
    }

    public function headings(): array
    {
        return [
            'المعرّف',
            'عنوان الإعلان',
            'السعر',
            'العنوان',
            'الوصف',
            'الغرض',
            'النوع',
            'المساحة',
            'غرف النوم',
            'دورات المياه',
            'المدينة',
            'الحي',
            'الصورة الرئيسية',
            'رابط الفيديو',
            'الحالة',
            'معرض الصور',
            'مصعد',
            'أمن',
            'كاميرات مراقبة',
            'تكييف مركزي',
            'تدفئة مركزية',
            'صيانة',
            'بواب',
            'إنترنت',
            'مرافق إضافية',
            'رقم الوحدة',
            'رقم الطابق',
            'عمر المبنى',
            'نوع الإطلالة',
            'مفروش',
            'مواقف السيارات',
            'بلكونة',
            'غرفة خادمة',
            'غرفة تخزين',
            'مسبح',
            'صالة رياضية',
            'مساحة الحديقة',
            'المواصفات',
            'طريقة الدفع',
            'مميز',
            'مميزات'
        ];
    }

    public function map($property): array
    {
        $content = $property->contents->first();
        
        // Get location names
        $cityName = $content?->city?->name_ar ?? $content?->city?->name_en ?? '';
        $districtName = $content?->state?->name ?? '';

        // Get gallery images as comma-separated URLs
        $galleryImages = $property->galleryImages->map(function ($img) {
            return asset($img->image);
        })->implode(', ');

        // Get amenities
        $amenityNames = $property->proertyAmenities->map(function ($amenity) {
            return $amenity->amenity?->name ?? '';
        })->filter()->values();

        // Map individual amenity columns
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
            $amenityColumns[$column] = $hasAmenity ? 'Yes' : 'No';
        }

        // Get additional amenities
        $standardAmenities = array_values($amenityMapping);
        $additionalAmenities = $amenityNames->reject(function ($name) use ($standardAmenities) {
            return in_array($name, $standardAmenities);
        })->implode(', ');

        // Get features
        $features = is_array($property->features) 
            ? implode(', ', array_filter($property->features))
            : '';

        // Get specifications
        $specifications = $property->specifications->map(function ($spec) {
            return ($spec->label ?? '') . ': ' . ($spec->value ?? '');
        })->implode(' | ');

        // Get individual specification values
        $specValues = [];
        foreach ($property->specifications as $spec) {
            $key = strtolower(str_replace(' ', '_', $spec->label ?? ''));
            $specValues[$key] = $spec->value ?? '';
        }

        $characteristics = $property->UserPropertyCharacteristics;

        // Get featured image URL
        $featuredImage = $property->featured_image ? asset($property->featured_image) : '';

        return [
            $property->id,
            $content?->title ?? '',
            $property->price ?? '',
            $content?->address ?? '',
            $content?->description ?? '',
            $property->purpose ?? '',
            $property->type ?? '',
            $property->area ?? '',
            $property->beds ?? '',
            $property->bath ?? '',
            $cityName,
            $districtName,
            $featuredImage,
            $property->video_url ?? '',
            $property->status ? '1' : '0',
            $galleryImages,
            $amenityColumns['amenity_مصعد'] ?? 'No',
            $amenityColumns['amenity_أمن'] ?? 'No',
            $amenityColumns['amenity_كاميرات_مراقبة'] ?? 'No',
            $amenityColumns['amenity_تكييف_مركزي'] ?? 'No',
            $amenityColumns['amenity_تدفئة_مركزية'] ?? 'No',
            $amenityColumns['amenity_صيانة'] ?? 'No',
            $amenityColumns['amenity_بواب'] ?? 'No',
            $amenityColumns['amenity_إنترنت'] ?? 'No',
            $additionalAmenities,
            $specValues['unit_number'] ?? '',
            $specValues['floor_number'] ?? ($characteristics?->floor_number ?? ''),
            $specValues['building_age'] ?? ($characteristics?->building_age ?? ''),
            $specValues['view_type'] ?? '',
            $specValues['furnished'] ?? '',
            $specValues['parking_spaces'] ?? ($characteristics?->private_parking ? '1' : ''),
            $specValues['balcony'] ?? ($characteristics?->balcony ? 'Yes' : 'No'),
            $specValues['maid_room'] ?? ($characteristics?->maid_room ? 'Yes' : 'No'),
            $specValues['storage_room'] ?? ($characteristics?->storage_room ? 'Yes' : 'No'),
            $specValues['swimming_pool'] ?? ($characteristics?->swimming_pool ? 'Yes' : 'No'),
            $specValues['gym'] ?? '',
            $specValues['garden_size'] ?? '',
            $specifications,
            $property->payment_method ?? '',
            $property->featured ? 'Yes' : 'No',
            $features
        ];
    }

    public function title(): string
    {
        return 'Properties for Import';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet;
                $highestRow = $sheet->getHighestRow();
                $rowCount = min($highestRow, 1000); // Apply validation to first 1000 rows

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

                // Set header row height
                $sheet->getRowDimension(1)->setRowHeight(25);

                // Purpose Column (F) - "sale,rent"
                $validation = $sheet->getCell('F2')->getDataValidation();
                $validation->setType(DataValidation::TYPE_LIST);
                $validation->setErrorStyle(DataValidation::STYLE_INFORMATION);
                $validation->setAllowBlank(false);
                $validation->setShowInputMessage(true);
                $validation->setShowErrorMessage(true);
                $validation->setShowDropDown(true);
                $validation->setFormula1('"sale,rent"');
                
                for ($i = 3; $i <= $rowCount; $i++) {
                    $sheet->getCell("F$i")->setDataValidation(clone $validation);
                }

                // Type Column (G) - "residential,commercial"
                $validation = $sheet->getCell('G2')->getDataValidation();
                $validation->setType(DataValidation::TYPE_LIST);
                $validation->setErrorStyle(DataValidation::STYLE_INFORMATION);
                $validation->setAllowBlank(false);
                $validation->setShowInputMessage(true);
                $validation->setShowErrorMessage(true);
                $validation->setShowDropDown(true);
                $validation->setFormula1('"residential,commercial"');

                for ($i = 3; $i <= $rowCount; $i++) {
                    $sheet->getCell("G$i")->setDataValidation(clone $validation);
                }

                // City Name Column (K) - Reference Lookup Sheet
                $validation = $sheet->getCell('K2')->getDataValidation();
                $validation->setType(DataValidation::TYPE_LIST);
                $validation->setErrorStyle(DataValidation::STYLE_INFORMATION);
                $validation->setAllowBlank(true);
                $validation->setShowInputMessage(true);
                $validation->setShowErrorMessage(true);
                $validation->setShowDropDown(true);
                $validation->setFormula1("'City-District Reference'!\$A\$2:\$A\$500");

                for ($i = 3; $i <= $rowCount; $i++) {
                    $sheet->getCell("K$i")->setDataValidation(clone $validation);
                }

                // District Name Column (L) - Reference Lookup Sheet
                $validation = $sheet->getCell('L2')->getDataValidation();
                $validation->setType(DataValidation::TYPE_LIST);
                $validation->setErrorStyle(DataValidation::STYLE_INFORMATION);
                $validation->setAllowBlank(true);
                $validation->setShowInputMessage(true);
                $validation->setShowErrorMessage(true);
                $validation->setShowDropDown(true);
                $validation->setFormula1("'City-District Reference'!\$B\$2:\$B\$10000");

                for ($i = 3; $i <= $rowCount; $i++) {
                    $sheet->getCell("L$i")->setDataValidation(clone $validation);
                }

                // Payment Method Column (AJ) - Arabic options
                $validation = $sheet->getCell('AJ2')->getDataValidation();
                $validation->setType(DataValidation::TYPE_LIST);
                $validation->setErrorStyle(DataValidation::STYLE_INFORMATION);
                $validation->setAllowBlank(true);
                $validation->setShowInputMessage(true);
                $validation->setShowErrorMessage(true);
                $validation->setShowDropDown(true);
                $validation->setFormula1('"شهري,ربع سنوي,نصف سنوي,سنوي"');

                for ($i = 3; $i <= $rowCount; $i++) {
                    $sheet->getCell("AJ$i")->setDataValidation(clone $validation);
                }

                // Featured Column (AK) - "Yes,No"
                $validation = $sheet->getCell('AK2')->getDataValidation();
                $validation->setType(DataValidation::TYPE_LIST);
                $validation->setErrorStyle(DataValidation::STYLE_INFORMATION);
                $validation->setAllowBlank(true);
                $validation->setShowInputMessage(true);
                $validation->setShowErrorMessage(true);
                $validation->setShowDropDown(true);
                $validation->setFormula1('"Yes,No"');

                for ($i = 3; $i <= $rowCount; $i++) {
                    $sheet->getCell("AK$i")->setDataValidation(clone $validation);
                }
            },
        ];
    }
}

class PropertiesImportReadyLookupSheetExport implements \Maatwebsite\Excel\Concerns\FromCollection, WithHeadings, WithTitle
{
    public function collection()
    {
        // Get unique cities (Arabic)
        $cities = \App\Models\User\RealestateManagement\City::query()
            ->select('name_ar')
            ->distinct()
            ->orderBy('name_ar')
            ->pluck('name_ar');

        // Get all districts (Arabic)
        $districts = UserDistrict::query()
            ->select('name_ar')
            ->distinct()
            ->orderBy('name_ar')
            ->pluck('name_ar');

        // Get full mapping for reference
        $fullMapping = UserDistrict::query()
            ->join('user_cities', 'user_districts.city_id', '=', 'user_cities.id')
            ->select('user_districts.name_ar as district', 'user_cities.name_ar as city')
            ->orderBy('user_cities.name_ar')
            ->orderBy('user_districts.name_ar')
            ->get();

        // Merge into a collection of rows
        $maxCount = max($cities->count(), $districts->count(), $fullMapping->count());
        $data = [];

        for ($i = 0; $i < $maxCount; $i++) {
            $data[] = [
                'city_name_unique' => $cities[$i] ?? '',
                'district_name_unique' => $districts[$i] ?? '',
                'district_ref' => $fullMapping[$i]->district ?? '',
                'city_ref' => $fullMapping[$i]->city ?? '',
            ];
        }

        return collect($data);
    }

    public function headings(): array
    {
        return [
            'المدينة (مصدر القائمة)',
            'الحي (مصدر القائمة)',
            'اسم الحي (مرجع)',
            'تابع للمدينة (مرجع)',
        ];
    }

    public function title(): string
    {
        return 'City-District Reference';
    }
}
