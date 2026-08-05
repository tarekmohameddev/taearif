<?php

namespace App\Exports;

use App\Models\User\UserDistrict;
use App\Support\PropertyExcelMapping;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithTitle;

use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;

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



class PropertiesTemplateMainSheetExport implements FromArray, WithHeadings, WithTitle, WithEvents
{
    public function array(): array
    {
        return [
            [
                'شقة فاخرة في القلب التجاري',
                '750000',
                'شارع الملك فهد، حي العليا',
                'شقة ثلاث غرف نوم بإطلالة مدينة ومصعد وأمن على مدار الساعة',
                'بيع',
                'سكني',
                '180',
                '3',
                '2',
                'الرياض',
                'حي العليا',
                'https://example.com/img1.jpg',
                'https://example.com/video1.mp4',
                '1',
                'https://example.com/g1.jpg,https://example.com/g2.jpg',
                'نعم',
                'نعم',
                'نعم',
                'نعم',
                'لا',
                'نعم',
                'نعم',
                'نعم',
                'موقف دراجات,غرفة غسيل',
                'A-101',
                '5',
                '5',
                'إطلالة مدينة',
                'مفروش بالكامل',
                '2',
                'نعم',
                'نعم',
                'نعم',
                'نعم',
                'نعم',
                '0',
                'ارتفاع السقف: 3.5 م، نوع المطبخ: مطبخ مفتوح، أرضيات: رخام',
                'شهري',
                'نعم',
                'منزل ذكي، ألواح شمسية',
            ],
            [
                'فيلا للإيجار بحديقة ومسبح',
                '120000',
                'حي النخيل، طريق الأمير محمد',
                'فيلا أربع غرف نوم مع حديقة خاصة ومسبح وصالة رياضية',
                'إيجار',
                'سكني',
                '350',
                '4',
                '4',
                'جدة',
                'حي النخيل',
                'https://example.com/img2.jpg',
                '',
                '1',
                'https://example.com/g1.jpg',
                'نعم',
                'نعم',
                'نعم',
                'نعم',
                'نعم',
                'نعم',
                'نعم',
                'نعم',
                'سونا، جاكوزي',
                '1',
                '0',
                '8',
                'إطلالة حديقة',
                'مفروش جزئياً',
                '3',
                'نعم',
                'نعم',
                'نعم',
                'نعم',
                'نعم',
                '120',
                'مساحة مبنية: 350 م²، التكييف: مركزي',
                'سنوي',
                'نعم',
                'مدخل مستقل، مواقف زوار',
            ],
            [
                'مكتب تجاري للبيع بموقع مميز',
                '1200000',
                'شارع العليا العام، برج الأعمال',
                'مكتب بإطلالة بحرية ومناسب للشركات، مواقف تحت الأرض',
                'بيع',
                'تجاري',
                '220',
                '0',
                '2',
                'الدمام',
                'حي الفيصلية',
                'https://example.com/img3.jpg',
                '',
                '1',
                '',
                'نعم',
                'نعم',
                'نعم',
                'نعم',
                'نعم',
                'نعم',
                'نعم',
                'نعم',
                'غرفة اجتماعات، استقبال',
                '301',
                '3',
                '12',
                'إطلالة بحرية',
                'غير مفروش',
                '10',
                'نعم',
                'لا',
                'نعم',
                'نعم',
                'لا',
                '0',
                'نظام إطفاء حريق، إنذار، كاميرات',
                'ربع سنوي',
                'لا',
                'مدخل فاخر، مصعد خاص بالمكتب',
            ],
        ];
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
            'مميزات'
        ];
    }

    public function title(): string
    {
        return 'قالب الاستيراد';
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

                // Unit number column (Y) — keep numeric-looking values as text for re-import
                for ($i = 2; $i <= 4; $i++) {
                    $cell = $sheet->getCell("Y{$i}");
                    $cell->setValueExplicit((string) $cell->getValue(), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                }

                // Purpose Column (E) - "sale,rent"
                $validation = $sheet->getCell('E2')->getDataValidation();
                $validation->setType(DataValidation::TYPE_LIST);
                $validation->setErrorStyle(DataValidation::STYLE_INFORMATION);
                $validation->setAllowBlank(false);
                $validation->setShowInputMessage(true);
                $validation->setShowErrorMessage(true);
                $validation->setShowDropDown(true);
                $validation->setFormula1('"' . PropertyExcelMapping::purposeExcelOptions() . '"');
                
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
                $validation->setFormula1('"' . PropertyExcelMapping::typeExcelOptions() . '"');

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
                $validation->setFormula1("'" . PropertyExcelMapping::LOOKUP_SHEET_TITLE . "'!\$A\$2:\$A\$500");

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
                $validation->setFormula1("'" . PropertyExcelMapping::LOOKUP_SHEET_TITLE . "'!\$B\$2:\$B\$10000");

                for ($i = 3; $i <= $rowCount; $i++) {
                    $sheet->getCell("K$i")->setDataValidation(clone $validation);
                }

                // Payment Method Column (AL) - "daily,monthly,yearly,semi_annual"
                $validation = $sheet->getCell('AL2')->getDataValidation();
                $validation->setType(DataValidation::TYPE_LIST);
                $validation->setErrorStyle(DataValidation::STYLE_INFORMATION);
                $validation->setAllowBlank(true);
                $validation->setShowInputMessage(true);
                $validation->setShowErrorMessage(true);
                $validation->setShowDropDown(true);
                $validation->setFormula1('"شهري,ربع سنوي,نصف سنوي,سنوي"');

                for ($i = 3; $i <= $rowCount; $i++) {
                    $sheet->getCell("AL$i")->setDataValidation(clone $validation);
                }

                // Featured Column (AM) - "Yes,No"
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
            'المدينة (مصدر القائمة)',
            'الحي (مصدر القائمة)',
            'اسم الحي (مرجع)',
            'تابع للمدينة (مرجع)',
        ];
    }

    public function title(): string
    {
        return PropertyExcelMapping::LOOKUP_SHEET_TITLE;
    }
}
