<?php

namespace App\Exports;

use App\Models\User\RealestateManagement\Property;
use App\Models\User\UserDistrict;
use App\Support\PropertyExcelMapping;
use App\Support\PropertyFilterQuery;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

/**
 * Regular properties export — same 40-column import-safe layout as
 * PropertiesTemplateExport / PropertiesImportReadyExport (no id, no export-only metadata).
 */
class PropertiesExport implements WithMultipleSheets
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
            new PropertiesExportMainSheet($this->userId, $this->allowedUserIds, $this->filters),
            new PropertiesTemplateLookupSheetExport(),
        ];
    }
}

class PropertiesExportMainSheet implements FromQuery, WithChunkReading, WithHeadings, WithMapping, WithTitle, WithEvents
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
                'contents' => function ($q) {
                    $q->with([
                        'city:id,name_ar,name_en',
                        'state:id,name',
                    ]);
                },
                'galleryImages:id,property_id,image',
                'proertyAmenities.amenity:id,name',
                'UserPropertyCharacteristics',
                'specifications:id,property_id,label,value',
            ])
            ->whereIn('user_id', $this->allowedUserIds);

        PropertyFilterQuery::apply($query, $this->filters);

        return $query->orderBy('created_at', 'desc');
    }

    public function chunkSize(): int
    {
        return 500;
    }

    public function headings(): array
    {
        return [
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
            'مميزات',
        ];
    }

    public function map($property): array
    {
        $content = $property->contents->first();

        $cityName = $content?->city?->name_ar ?? $content?->city?->name_en ?? '';
        $districtName = $content?->state?->name ?? '';

        $galleryImages = $property->galleryImages->map(function ($img) {
            return asset($img->image);
        })->implode(', ');

        $amenityNames = $property->proertyAmenities->map(function ($amenity) {
            return $amenity->amenity?->name ?? '';
        })->filter()->values();

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
            $amenityColumns[$column] = $hasAmenity
                ? PropertyExcelMapping::booleanToExcel(true)
                : PropertyExcelMapping::booleanToExcel(false);
        }

        $standardAmenities = array_values($amenityMapping);
        $additionalAmenities = $amenityNames->reject(function ($name) use ($standardAmenities) {
            return in_array($name, $standardAmenities);
        })->implode(', ');

        $features = is_array($property->features)
            ? implode(', ', array_filter($property->features))
            : '';

        $specifications = $property->specifications->map(function ($spec) {
            return ($spec->label ?? '') . ': ' . ($spec->value ?? '');
        })->implode(' | ');

        $specValues = [];
        foreach ($property->specifications as $spec) {
            $key = strtolower(str_replace(' ', '_', $spec->label ?? ''));
            $specValues[$key] = $spec->value ?? '';
        }

        $characteristics = $property->UserPropertyCharacteristics;

        $featuredImage = $property->featured_image ? asset($property->featured_image) : '';

        return [
            $content?->title ?? '',
            $property->price ?? '',
            $content?->address ?? '',
            $content?->description ?? '',
            PropertyExcelMapping::purposeToExcel($property->purpose ?? null),
            PropertyExcelMapping::typeToExcel($property->property_type ?? null),
            $property->area ?? '',
            $property->beds ?? '',
            $property->bath ?? '',
            $cityName,
            $districtName,
            $featuredImage,
            $property->video_url ?? '',
            $property->status ? '1' : '0',
            $galleryImages,
            $amenityColumns['amenity_مصعد'] ?? PropertyExcelMapping::booleanToExcel(false),
            $amenityColumns['amenity_أمن'] ?? PropertyExcelMapping::booleanToExcel(false),
            $amenityColumns['amenity_كاميرات_مراقبة'] ?? PropertyExcelMapping::booleanToExcel(false),
            $amenityColumns['amenity_تكييف_مركزي'] ?? PropertyExcelMapping::booleanToExcel(false),
            $amenityColumns['amenity_تدفئة_مركزية'] ?? PropertyExcelMapping::booleanToExcel(false),
            $amenityColumns['amenity_صيانة'] ?? PropertyExcelMapping::booleanToExcel(false),
            $amenityColumns['amenity_بواب'] ?? PropertyExcelMapping::booleanToExcel(false),
            $amenityColumns['amenity_إنترنت'] ?? PropertyExcelMapping::booleanToExcel(false),
            $additionalAmenities,
            $specValues['unit_number'] ?? '',
            $specValues['floor_number'] ?? ($characteristics?->floor_number ?? ''),
            $specValues['building_age'] ?? ($characteristics?->building_age ?? ''),
            $specValues['view_type'] ?? '',
            $specValues['furnished'] ?? '',
            $specValues['parking_spaces'] ?? ($characteristics?->private_parking ? '1' : ''),
            $specValues['balcony'] ?? ($characteristics?->balcony ? PropertyExcelMapping::booleanToExcel(true) : PropertyExcelMapping::booleanToExcel(false)),
            $specValues['maid_room'] ?? ($characteristics?->maid_room ? PropertyExcelMapping::booleanToExcel(true) : PropertyExcelMapping::booleanToExcel(false)),
            $specValues['storage_room'] ?? ($characteristics?->storage_room ? PropertyExcelMapping::booleanToExcel(true) : PropertyExcelMapping::booleanToExcel(false)),
            $specValues['swimming_pool'] ?? ($characteristics?->swimming_pool ? PropertyExcelMapping::booleanToExcel(true) : PropertyExcelMapping::booleanToExcel(false)),
            $specValues['gym'] ?? '',
            $specValues['garden_size'] ?? '',
            $specifications,
            PropertyExcelMapping::paymentMethodToExcel($property->payment_method ?? null),
            PropertyExcelMapping::booleanToExcel((bool) $property->featured),
            $features,
        ];
    }

    public function title(): string
    {
        return 'العقارات';
    }

    public function registerEvents(): array
    {
        $cityCount = (int) \App\Models\User\RealestateManagement\City::query()
            ->distinct()
            ->count('name_ar');
        $districtCount = (int) UserDistrict::query()
            ->distinct()
            ->count('name_ar');
        $cityEndRow = max(2, 1 + $cityCount);
        $districtEndRow = max(2, 1 + $districtCount);

        return [
            AfterSheet::class => function (AfterSheet $event) use ($cityEndRow, $districtEndRow) {
                $sheet = $event->sheet;
                $highestRow = $sheet->getHighestRow();
                $rowCount = min(max($highestRow, 2), 1000);

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

                // Purpose Column (E)
                $validation = $sheet->getCell('E2')->getDataValidation();
                $validation->setType(DataValidation::TYPE_LIST);
                $validation->setErrorStyle(DataValidation::STYLE_INFORMATION);
                $validation->setAllowBlank(true);
                $validation->setShowInputMessage(true);
                $validation->setShowErrorMessage(true);
                $validation->setShowDropDown(true);
                $validation->setFormula1('"' . PropertyExcelMapping::purposeExcelOptions() . '"');

                for ($i = 3; $i <= $rowCount; $i++) {
                    $sheet->getCell("E$i")->setDataValidation(clone $validation);
                }

                // Type Column (F)
                $validation = $sheet->getCell('F2')->getDataValidation();
                $validation->setType(DataValidation::TYPE_LIST);
                $validation->setErrorStyle(DataValidation::STYLE_INFORMATION);
                $validation->setAllowBlank(false);
                $validation->setShowInputMessage(true);
                $validation->setShowErrorMessage(true);
                $validation->setShowDropDown(true);
                $validation->setFormula1('"' . PropertyExcelMapping::typeExcelOptions() . '"');

                for ($i = 3; $i <= $rowCount; $i++) {
                    $sheet->getCell("F$i")->setDataValidation(clone $validation);
                }

                // City Name Column (J) — lookup sheet
                $validation = $sheet->getCell('J2')->getDataValidation();
                $validation->setType(DataValidation::TYPE_LIST);
                $validation->setErrorStyle(DataValidation::STYLE_INFORMATION);
                $validation->setAllowBlank(true);
                $validation->setShowInputMessage(true);
                $validation->setShowErrorMessage(true);
                $validation->setShowDropDown(true);
                $validation->setFormula1("'" . PropertyExcelMapping::LOOKUP_SHEET_TITLE . "'!\$A\$2:\$A\${$cityEndRow}");

                for ($i = 3; $i <= $rowCount; $i++) {
                    $sheet->getCell("J$i")->setDataValidation(clone $validation);
                }

                // District Name Column (K) — lookup sheet
                $validation = $sheet->getCell('K2')->getDataValidation();
                $validation->setType(DataValidation::TYPE_LIST);
                $validation->setErrorStyle(DataValidation::STYLE_INFORMATION);
                $validation->setAllowBlank(true);
                $validation->setShowInputMessage(true);
                $validation->setShowErrorMessage(true);
                $validation->setShowDropDown(true);
                $validation->setFormula1("'" . PropertyExcelMapping::LOOKUP_SHEET_TITLE . "'!\$B\$2:\$B\${$districtEndRow}");

                for ($i = 3; $i <= $rowCount; $i++) {
                    $sheet->getCell("K$i")->setDataValidation(clone $validation);
                }

                // Payment Method Column (AL)
                $validation = $sheet->getCell('AL2')->getDataValidation();
                $validation->setType(DataValidation::TYPE_LIST);
                $validation->setErrorStyle(DataValidation::STYLE_INFORMATION);
                $validation->setAllowBlank(true);
                $validation->setShowInputMessage(true);
                $validation->setShowErrorMessage(true);
                $validation->setShowDropDown(true);
                $validation->setFormula1('"' . PropertyExcelMapping::paymentMethodExcelOptions() . '"');

                for ($i = 3; $i <= $rowCount; $i++) {
                    $sheet->getCell("AL$i")->setDataValidation(clone $validation);
                }

                // Featured Column (AM) — Arabic نعم/لا to match booleanToExcel
                $validation = $sheet->getCell('AM2')->getDataValidation();
                $validation->setType(DataValidation::TYPE_LIST);
                $validation->setErrorStyle(DataValidation::STYLE_INFORMATION);
                $validation->setAllowBlank(true);
                $validation->setShowInputMessage(true);
                $validation->setShowErrorMessage(true);
                $validation->setShowDropDown(true);
                $validation->setFormula1('"نعم,لا"');

                for ($i = 3; $i <= $rowCount; $i++) {
                    $sheet->getCell("AM$i")->setDataValidation(clone $validation);
                }
            },
        ];
    }
}
