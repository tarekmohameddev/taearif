<?php

namespace App\Exports;

use App\Models\User\UserDistrict;
use App\Support\PropertyExcelMapping;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithTitle;

use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
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
    /**
     * Required-for-complete columns (Excel ` *` is UX only; server soft-incomplete still applies).
     * Indices match headings() below.
     */
    private const REQUIRED_HEADER_INDICES = [0, 2, 3, 5, 11]; // A, C, D, F, L

    public function array(): array
    {
        // Sparse samples: values only in the five required columns.
        // Delete these rows before a real import (they create live properties).
        $empty = array_fill(0, 40, '');

        $row = static function (string $title, string $address, string $description, string $type, string $image) use ($empty): array {
            $r = $empty;
            $r[0] = $title;
            $r[2] = $address;
            $r[3] = $description;
            $r[5] = $type;
            $r[11] = $image;

            return $r;
        };

        return [
            $row(
                'شقة فاخرة في القلب التجاري',
                'شارع الملك فهد، حي العليا',
                'شقة ثلاث غرف نوم بإطلالة مدينة ومصعد وأمن على مدار الساعة',
                'سكني',
                'https://example.com/img1.jpg'
            ),
            $row(
                'فيلا للإيجار بحديقة ومسبح',
                'حي النخيل، طريق الأمير محمد',
                'فيلا أربع غرف نوم مع حديقة خاصة ومسبح وصالة رياضية',
                'سكني',
                'https://example.com/img2.jpg'
            ),
            $row(
                'مكتب تجاري للبيع بموقع مميز',
                'شارع العليا العام، برج الأعمال',
                'مكتب بإطلالة بحرية ومناسب للشركات، مواقف تحت الأرض',
                'تجاري',
                'https://example.com/img3.jpg'
            ),
        ];
    }

    public function headings(): array
    {
        return [
            'عنوان الإعلان *',
            'السعر',
            'العنوان *',
            'الوصف *',
            'الغرض',
            'النوع *',
            'المساحة',
            'غرف النوم',
            'دورات المياه',
            'المدينة',
            'الحي',
            'الصورة الرئيسية *',
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
        $unitNumberColIndex = array_search('رقم الوحدة', $this->headings(), true);
        $unitNumberCol = $unitNumberColIndex !== false
            ? Coordinate::stringFromColumnIndex($unitNumberColIndex + 1)
            : null;

        $cityCount = (int) \App\Models\User\RealestateManagement\City::query()
            ->distinct()
            ->count('name_ar');
        $districtCount = (int) UserDistrict::query()
            ->distinct()
            ->count('name_ar');
        $cityEndRow = max(2, 1 + $cityCount);
        $districtEndRow = max(2, 1 + $districtCount);

        return [
            AfterSheet::class => function(AfterSheet $event) use ($unitNumberCol, $cityEndRow, $districtEndRow) {
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

                // Emphasize the five required header cells (A, C, D, F, L)
                foreach (self::REQUIRED_HEADER_INDICES as $index) {
                    $col = Coordinate::stringFromColumnIndex($index + 1);
                    $sheet->getStyle("{$col}1")->applyFromArray([
                        'font' => [
                            'bold' => true,
                            'color' => ['rgb' => 'FFFFFF'],
                        ],
                        'fill' => [
                            'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                            'startColor' => ['rgb' => '2F5496'],
                        ],
                    ]);
                }

                // Set header row height for better visibility
                $sheet->getRowDimension(1)->setRowHeight(25);

                // Unit number column — keep numeric-looking values as text for re-import
                if ($unitNumberCol !== null) {
                    for ($i = 2; $i <= 4; $i++) {
                        $cell = $sheet->getCell("{$unitNumberCol}{$i}");
                        $cell->setValueExplicit((string) $cell->getValue(), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                    }
                }

                // Text-length "required" UX on title / address / description / featured_image.
                // Excel only warns on cell entry; untouched blanks never fire — ` *` is the real signal.
                foreach (['A', 'C', 'D', 'L'] as $col) {
                    $validation = $sheet->getCell("{$col}2")->getDataValidation();
                    $validation->setType(DataValidation::TYPE_TEXTLENGTH);
                    $validation->setOperator(DataValidation::OPERATOR_GREATERTHAN);
                    $validation->setFormula1('0');
                    $validation->setErrorStyle(DataValidation::STYLE_INFORMATION);
                    $validation->setAllowBlank(false);
                    $validation->setShowInputMessage(true);
                    $validation->setShowErrorMessage(true);
                    $validation->setPromptTitle('حقل مطلوب');
                    $validation->setPrompt('هذا الحقل مطلوب لاكتمال العقار');
                    $validation->setErrorTitle('حقل مطلوب');
                    $validation->setError('يرجى إدخال قيمة في هذا الحقل');

                    for ($i = 3; $i <= $rowCount; $i++) {
                        $sheet->getCell("{$col}{$i}")->setDataValidation(clone $validation);
                    }
                }

                // Purpose Column (E) — optional for complete
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

                // Type Column (F) — required; four types
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
                $validation->setFormula1("'" . PropertyExcelMapping::LOOKUP_SHEET_TITLE . "'!\$A\$2:\$A\${$cityEndRow}");

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
                $validation->setFormula1("'" . PropertyExcelMapping::LOOKUP_SHEET_TITLE . "'!\$B\$2:\$B\${$districtEndRow}");

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
