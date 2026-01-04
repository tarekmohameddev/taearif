<?php

namespace App\Exports;

use App\Models\User\UserCity;
use App\Models\User\UserDistrict;
use App\Models\Api\UserApiCustomerType;
use App\Models\Api\UserApiCustomerStage;
use App\Models\Api\UserApiCustomerPriority;
use App\Models\Api\UserApiCustomerProcedure;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class CustomersTemplateExport implements WithMultipleSheets
{
    protected $userId;

    public function __construct($userId)
    {
        $this->userId = $userId;
    }

    public function sheets(): array
    {
        return [
            new CustomersTemplateMainSheetExport($this->userId),
            new CustomersTemplateLookupSheetExport($this->userId),
            new CustomersTemplateCityDistrictSheetExport(),
        ];
    }
}

class CustomersTemplateMainSheetExport implements FromArray, WithHeadings, WithTitle, WithEvents
{
    protected $userId;
    protected int $rowLimit = 1000;
    protected int $typeCount = 0;
    protected int $priorityCount = 0;
    protected int $stageCount = 0;
    protected int $procedureCount = 0;
    protected int $cityCount = 0;
    protected int $districtCount = 0;

    public function __construct($userId)
    {
        $this->userId = $userId;

        if ($this->userId) {
            $this->typeCount = UserApiCustomerType::where('user_id', $this->userId)->count();
            $this->priorityCount = UserApiCustomerPriority::where('user_id', $this->userId)->count();
            $this->stageCount = UserApiCustomerStage::where('user_id', $this->userId)->count();
            $this->procedureCount = UserApiCustomerProcedure::where('user_id', $this->userId)->count();
        }

        $this->cityCount = UserCity::count();
        $this->districtCount = UserDistrict::count();
    }

    public function array(): array
    {
        // Sample row with example data
        return [
            [
                'أحمد محمد',        // name
                'ahmed@example.com', // email
                '0512345678',        // phone_number
                'ملاحظات العميل',    // note
                '',                  // type_name (user fills from lookup)
                '',                  // priority_name
                '',                  // stage_name
                '',                  // procedure_name
                'الرياض',           // city_name
                'حي العليا',        // district_name
                '',                  // password (optional)
                '',                  // interested_category_ids (comma-separated)
                '',                  // interested_property_ids (comma-separated)
            ],
        ];
    }

    public function headings(): array
    {
        return [
            'name',
            'email',
            'phone_number',
            'note',
            'type_name',
            'priority_name',
            'stage_name',
            'procedure_name',
            'city_name',
            'district_name',
            'password',
            'interested_category_ids',
            'interested_property_ids',
        ];
    }

    public function title(): string
    {
        return 'Import Template';
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

                // Auto-size columns
                foreach (range('A', 'M') as $col) {
                    $sheet->getColumnDimension($col)->setAutoSize(true);
                }

                // Add dropdown validations from lookup sheets (only if data exists)
                $this->addDropdown($sheet, 'E', 'Lookup Reference', 'A', $this->typeCount);
                $this->addDropdown($sheet, 'F', 'Lookup Reference', 'B', $this->priorityCount);
                $this->addDropdown($sheet, 'G', 'Lookup Reference', 'C', $this->stageCount);
                $this->addDropdown($sheet, 'H', 'Lookup Reference', 'D', $this->procedureCount);
                $this->addDropdown($sheet, 'I', 'Lookup Reference', 'E', $this->cityCount);
                $this->addDropdown($sheet, 'J', 'Lookup Reference', 'F', $this->districtCount);
            },
        ];
    }

    /**
     * Attach a data validation list to a column based on a reference sheet.
     */
    protected function addDropdown($sheet, string $targetColumn, string $sourceSheet, string $sourceColumn, int $itemCount): void
    {
        if ($itemCount <= 0) {
            return;
        }

        $startRow = 2; // skip header
        $endRow = $itemCount + 1; // header + items
        $formula = "'{$sourceSheet}'!\${$sourceColumn}\${$startRow}:\${$sourceColumn}\${$endRow}";

        for ($row = $startRow; $row <= $this->rowLimit; $row++) {
            $cell = $sheet->getCell("{$targetColumn}{$row}");
            $validation = $cell->getDataValidation();
            $validation->setType(DataValidation::TYPE_LIST);
            $validation->setErrorStyle(DataValidation::STYLE_INFORMATION);
            $validation->setAllowBlank(true);
            $validation->setShowDropDown(true);
            $validation->setFormula1($formula);
            $validation->setShowErrorMessage(true);
            $validation->setErrorTitle('Invalid selection');
            $validation->setError('Please select a value from the dropdown list.');
        }
    }
}

class CustomersTemplateLookupSheetExport implements FromCollection, WithHeadings, WithTitle, WithEvents
{
    protected $userId;

    public function __construct($userId)
    {
        $this->userId = $userId;
    }

    public function collection()
    {
        // Load tenant-specific lookups (optional user)
        if ($this->userId) {
            $types = UserApiCustomerType::where('user_id', $this->userId)
                ->orderBy('order')
                ->pluck('name')
                ->toArray();

            $priorities = UserApiCustomerPriority::where('user_id', $this->userId)
                ->orderBy('order')
                ->pluck('name')
                ->toArray();

            $stages = UserApiCustomerStage::where('user_id', $this->userId)
                ->orderBy('order')
                ->pluck('stage_name')
                ->toArray();

            $procedures = UserApiCustomerProcedure::where('user_id', $this->userId)
                ->orderBy('order')
                ->pluck('procedure_name')
                ->toArray();
        } else {
            $types = [];
            $priorities = [];
            $stages = [];
            $procedures = [];
        }

        // Load cities (global)
        $cities = UserCity::orderBy('name_ar')
            ->pluck('name_ar')
            ->toArray();

        // Load districts (global)
        $districts = UserDistrict::orderBy('name_ar')
            ->pluck('name_ar')
            ->toArray();

        // Build rows - align all columns
        $maxCount = max(
            count($types),
            count($priorities),
            count($stages),
            count($procedures),
            count($cities),
            count($districts)
        );

        $data = [];
        for ($i = 0; $i < $maxCount; $i++) {
            $data[] = [
                'type_name' => $types[$i] ?? '',
                'priority_name' => $priorities[$i] ?? '',
                'stage_name' => $stages[$i] ?? '',
                'procedure_name' => $procedures[$i] ?? '',
                'city_name' => $cities[$i] ?? '',
                'district_name' => $districts[$i] ?? '',
            ];
        }

        return collect($data);
    }

    public function headings(): array
    {
        return [
            'type_name (Types)',
            'priority_name (Priorities)',
            'stage_name (Stages)',
            'procedure_name (Procedures)',
            'city_name (Cities)',
            'district_name (Districts)',
        ];
    }

    public function title(): string
    {
        return 'Lookup Reference';
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
                        'startColor' => ['rgb' => '70AD47'], // Green for reference
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ]);

                $sheet->getRowDimension(1)->setRowHeight(25);

                // Auto-size columns
                foreach (range('A', 'F') as $col) {
                    $sheet->getColumnDimension($col)->setAutoSize(true);
                }
            },
        ];
    }
}

class CustomersTemplateCityDistrictSheetExport implements FromCollection, WithHeadings, WithTitle, WithEvents
{
    public function collection()
    {
        $districts = UserDistrict::with('city:id,name_ar,name_en')
            ->orderBy('city_id')
            ->orderBy('name_ar')
            ->get();

        return $districts->map(function ($district) {
            return [
                'city_name' => $district->city?->name_ar ?? '',
                'district_name' => $district->name_ar ?? '',
                'district_id' => $district->id,
                'city_id' => $district->city_id,
            ];
        });
    }

    public function headings(): array
    {
        return [
            'city_name',
            'district_name',
            'district_id',
            'city_id',
        ];
    }

    public function title(): string
    {
        return 'Cities & Districts';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet;

                $sheet->getStyle('1:1')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => 'FFFFFF'],
                        'size' => 11,
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '2F5597'],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ]);

                $sheet->getRowDimension(1)->setRowHeight(25);

                foreach (range('A', 'D') as $col) {
                    $sheet->getColumnDimension($col)->setAutoSize(true);
                }
            },
        ];
    }
}
