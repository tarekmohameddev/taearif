<?php

namespace App\Exports;

use App\Exports\Concerns\PreservesNumericStringsBinder;
use App\Models\ApiCustomer;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class CrmCustomersExport implements FromQuery, WithHeadings, WithMapping, WithTitle, WithEvents, WithStrictNullComparison, WithCustomValueBinder
{
    protected $userId;
    protected $filters;

    public function __construct($userId, array $filters = [])
    {
        $this->userId = $userId;
        $this->filters = $filters;
    }

    public function query()
    {
        $query = ApiCustomer::query()
            ->where('user_id', $this->userId)
            ->with([
                'type', 
                'priorityRef', 
                'stage', 
                'procedure', 
                'city', 
                'district',
                'responsibleEmployee.activeWhatsappUser'
            ]);

        // Apply optional filters (same as CRMController::searchCustomers)
        if (!empty($this->filters['type_id'])) {
            $query->where('type_id', $this->filters['type_id']);
        }

        if (!empty($this->filters['priority_id'])) {
            $query->where('priority_id', $this->filters['priority_id']);
        }

        if (!empty($this->filters['stage_id'])) {
            $query->where('stage_id', $this->filters['stage_id']);
        }

        if (!empty($this->filters['procedure_id'])) {
            $query->where('procedure_id', $this->filters['procedure_id']);
        }

        if (!empty($this->filters['city_id'])) {
            $query->where('city_id', $this->filters['city_id']);
        }

        if (!empty($this->filters['district_id'])) {
            $query->where('district_id', $this->filters['district_id']);
        }

        if (!empty($this->filters['responsible_employee_id'])) {
            $query->where('responsible_employee_id', $this->filters['responsible_employee_id']);
        }

        if (!empty($this->filters['search'])) {
            $search = $this->filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone_number', 'like', "%{$search}%")
                    ->orWhereHas('responsibleEmployee', function ($empQuery) use ($search) {
                        $empQuery->where('first_name', 'like', "%{$search}%")
                                 ->orWhere('last_name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('responsibleEmployee.activeWhatsappUser', function ($whatsappQuery) use ($search) {
                        $whatsappQuery->where('number', 'like', "%{$search}%");
                    });
            });
        }

        return $query->orderBy('created_at', 'desc');
    }

    public function headings(): array
    {
        return [
            'id',
            'name',
            'email',
            'phone_number',
            'note',
            'type_name',
            'priority_name',
            'stage_name',
            'procedure_name',
            'city_name_ar',
            'city_name_en',
            'district_name_ar',
            'district_name_en',
            'responsible_employee_name',
            'responsible_employee_email',
            'responsible_employee_whatsapp',
            'customers_hub_stage_id',
            'created_at',
            'updated_at',
        ];
    }

    public function map($customer): array
    {
        $employee = $customer->responsibleEmployee;
        $employeeName = $employee ? trim(($employee->first_name ?? '') . ' ' . ($employee->last_name ?? '')) : '';
        $employeeEmail = $employee ? $employee->email : '';
        $employeeWhatsapp = $employee && $employee->activeWhatsappUser ? $employee->activeWhatsappUser->number : '';

        return [
            $customer->id,
            $customer->name,
            $customer->email,
            $customer->phone_number,
            $customer->note ?? '',
            $customer->type?->name ?? '',
            $customer->priorityRef?->name ?? '',
            $customer->stage?->stage_name ?? '',
            $customer->procedure?->procedure_name ?? '',
            $customer->city?->name_ar ?? '',
            $customer->city?->name_en ?? '',
            $customer->district?->name_ar ?? '',
            $customer->district?->name_en ?? '',
            $employeeName,
            $employeeEmail,
            $employeeWhatsapp,
            $customer->customers_hub_stage_id ?? '',
            $customer->created_at?->toDateTimeString() ?? '',
            $customer->updated_at?->toDateTimeString() ?? '',
        ];
    }

    public function title(): string
    {
        return 'CRM Customers';
    }

    /**
     * @param  mixed  $value
     */
    public function bindValue(Cell $cell, $value)
    {
        return (new PreservesNumericStringsBinder())->bindValue($cell, $value);
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

                // Auto-size columns (A to S for 19 columns)
                foreach (range('A', 'S') as $col) {
                    $sheet->getColumnDimension($col)->setAutoSize(true);
                }
            },
        ];
    }
}
