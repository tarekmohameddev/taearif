<?php

namespace App\Exports;

use App\Models\User\UserDistrict;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithTitle;

class PropertiesTemplateExport implements WithMultipleSheets
{
    public function sheets(): array
    {
        return [
            new PropertiesTemplateMainSheetExport(),
            new PropertiesTemplateLookupSheetExport(),
        ];
    }
}

use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;

class PropertiesTemplateMainSheetExport implements FromArray, WithHeadings, WithTitle, WithEvents
{
    public function array(): array
    {
        return [
            [
                'Luxury Apartment Downtown',
                '500000',
                '123 Main Street, Downtown',
                'Beautiful 3-bedroom apartment with city views',
                'sale',
                'residential',
                '150',
                '3',
                '2',
                'الرياض',
                'حي العليا',
                'https://amp.dev/static/samples/img/image1.jpg',
                'https://amp.dev/static/samples/img/image1.jpg',
                '1',
                '3333',
                'https://amp.dev/static/samples/img/image1.jpg',
                'Yes',
                'Yes',
                'Yes',
                'Yes',
                '',
                'Yes',
                'Yes',
                'Yes',
                'موقف دراجات,غرفة غسيل',
                'A-101',
                '5',
                '2020',
                'Sea View',
                'Fully Furnished',
                '2',
                'Yes',
                'Yes',
                'Yes',
                'Yes',
                'Yes',
                '50',
                'Ceiling Height: 3.5m, Kitchen Type: Open Kitchen, Floor Material: Marble',
                'نصف سنوي',
                'Yes',
                'Smart Home, Solar Panels'
            ],
        ];
    }

    public function headings(): array
    {
        return [
            'title',
            'price',
            'address',
            'description',
            'purpose',
            'type',
            'area',
            'beds',
            'bath',
            'city_name',
            'district_name',
            'featured_image',
            'video_url',
            'status',
            'price_per_meter',
            'gallery_images',
            'amenity_مصعد',
            'amenity_أمن',
            'amenity_كاميرات_مراقبة',
            'amenity_تكييف_مركزي',
            'amenity_تدفئة_مركزية',
            'amenity_صيانة',
            'amenity_بواب',
            'amenity_إنترنت',
            'additional_amenities',
            'unit_number',
            'floor_number',
            'building_age',
            'view_type',
            'furnished',
            'parking_spaces',
            'balcony',
            'maid_room',
            'storage_room',
            'swimming_pool',
            'gym',
            'garden_size',
            'specifications',
            'payment_method',
            'featured',
            'features'
        ];
    }

    public function title(): string
    {
        return 'Import Template';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet;
                $rowCount = 1000; // Apply validation to first 1000 rows

                // Style the header row
                $sheet->getStyle('1:1')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => 'FFFFFF'],
                        'size' => 11,
                    ],
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '4472C4'], // Professional blue
                    ],
                    'alignment' => [
                        'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                        'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                    ],
                ]);

                // Set header row height for better visibility
                $sheet->getRowDimension(1)->setRowHeight(25);

                // Purpose Column (E) - "sale,rent"
                $validation = $sheet->getCell('E2')->getDataValidation();
                $validation->setType(DataValidation::TYPE_LIST);
                $validation->setErrorStyle(DataValidation::STYLE_INFORMATION);
                $validation->setAllowBlank(false);
                $validation->setShowInputMessage(true);
                $validation->setShowErrorMessage(true);
                $validation->setShowDropDown(true);
                $validation->setFormula1('"sale,rent"');
                
                for ($i = 3; $i <= $rowCount; $i++) {
                    $sheet->getCell("E$i")->setDataValidation(clone $validation);
                }

                // Type Column (F) - "residential,commercial"
                $validation = $sheet->getCell('F2')->getDataValidation();
                $validation->setType(DataValidation::TYPE_LIST);
                $validation->setErrorStyle(DataValidation::STYLE_INFORMATION);
                $validation->setAllowBlank(false);
                $validation->setShowInputMessage(true);
                $validation->setShowErrorMessage(true);
                $validation->setShowDropDown(true);
                $validation->setFormula1('"residential,commercial"');

                for ($i = 3; $i <= $rowCount; $i++) {
                    $sheet->getCell("F$i")->setDataValidation(clone $validation);
                }

                // City Name Column (J) - Reference Lookup Sheet Column A (Unique Cities)
                $validation = $sheet->getCell('J2')->getDataValidation();
                $validation->setType(DataValidation::TYPE_LIST);
                $validation->setErrorStyle(DataValidation::STYLE_INFORMATION);
                $validation->setAllowBlank(true);
                $validation->setShowInputMessage(true);
                $validation->setShowErrorMessage(true);
                $validation->setShowDropDown(true);
                $validation->setFormula1("'City-District Reference'!\$A\$2:\$A\$500");

                for ($i = 3; $i <= $rowCount; $i++) {
                    $sheet->getCell("J$i")->setDataValidation(clone $validation);
                }

                // District Name Column (K) - Reference Lookup Sheet Column B (All Districts)
                $validation = $sheet->getCell('K2')->getDataValidation();
                $validation->setType(DataValidation::TYPE_LIST);
                $validation->setErrorStyle(DataValidation::STYLE_INFORMATION);
                $validation->setAllowBlank(true);
                $validation->setShowInputMessage(true);
                $validation->setShowErrorMessage(true);
                $validation->setShowDropDown(true);
                $validation->setFormula1("'City-District Reference'!\$B\$2:\$B\$10000");

                for ($i = 3; $i <= $rowCount; $i++) {
                    $sheet->getCell("K$i")->setDataValidation(clone $validation);
                }

                // Payment Method Column (AM) - "daily,monthly,yearly,semi_annual"
                $validation = $sheet->getCell('AM2')->getDataValidation();
                $validation->setType(DataValidation::TYPE_LIST);
                $validation->setErrorStyle(DataValidation::STYLE_INFORMATION);
                $validation->setAllowBlank(true);
                $validation->setShowInputMessage(true);
                $validation->setShowErrorMessage(true);
                $validation->setShowDropDown(true);
                $validation->setFormula1('"شهري,ربع سنوي,نصف سنوي,سنوي"');

                for ($i = 3; $i <= $rowCount; $i++) {
                    $sheet->getCell("AM$i")->setDataValidation(clone $validation);
                }

                // Featured Column (AN) - "Yes,No"
                $validation = $sheet->getCell('AN2')->getDataValidation();
                $validation->setType(DataValidation::TYPE_LIST);
                $validation->setErrorStyle(DataValidation::STYLE_INFORMATION);
                $validation->setAllowBlank(true);
                $validation->setShowInputMessage(true);
                $validation->setShowErrorMessage(true);
                $validation->setShowDropDown(true);
                $validation->setFormula1('"Yes,No"');

                for ($i = 3; $i <= $rowCount; $i++) {
                    $sheet->getCell("AN$i")->setDataValidation(clone $validation);
                }
            },
        ];
    }
}

class PropertiesTemplateLookupSheetExport implements FromCollection, WithHeadings, WithTitle
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
            'City Name (Dropdown Source)',
            'District Name (Dropdown Source)',
            'District Name (Reference)',
            'Belongs to City (Reference)',
        ];
    }

    public function title(): string
    {
        return 'City-District Reference';
    }
}
