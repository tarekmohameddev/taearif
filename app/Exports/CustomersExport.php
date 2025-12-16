<?php

namespace App\Exports;

use App\Models\ApiCustomer;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class CustomersExport implements FromQuery, WithHeadings, WithMapping, WithTitle, WithEvents
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
            ->with(['type', 'priorityRef', 'stage', 'procedure', 'city', 'district']);

        // Apply optional filters
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

        if (!empty($this->filters['search'])) {
            $search = $this->filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone_number', 'like', "%{$search}%");
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
            'city_name',
            'district_name',
            'created_at',
            'updated_at',
        ];
    }

    public function map($customer): array
    {
        return [
            $customer->id,
            $customer->name,
            $customer->email,
            $customer->phone_number,
            $customer->note,
            $customer->type?->name ?? '',
            $customer->priorityRef?->name ?? '',
            $customer->stage?->stage_name ?? '',
            $customer->procedure?->procedure_name ?? '',
            $customer->city?->name_ar ?? '',
            $customer->district?->name_ar ?? '',
            $customer->created_at?->toDateTimeString() ?? '',
            $customer->updated_at?->toDateTimeString() ?? '',
        ];
    }

    public function title(): string
    {
        return 'Customers';
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
            },
        ];
    }
}











