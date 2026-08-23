<?php

namespace App\Exports;

use App\Models\Api\UserPropertyRequest;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class PropertyRequestsDataExportSheet implements FromQuery, WithChunkReading, WithHeadings, WithMapping, WithTitle, WithEvents
{
    public function __construct(
        private int $ownerId,
    ) {}

    public function query()
    {
        return UserPropertyRequest::query()
            ->where('user_id', $this->ownerId)
            ->with([
                'customer',
                'statusOption',
                'district',
                'initialProperty.contents',
                'project.contents',
                'customer.responsibleEmployee',
            ])
            ->orderBy('created_at', 'desc');
    }

    public function chunkSize(): int
    {
        return 500;
    }

    public function title(): string
    {
        return 'طلبات العقار';
    }

    public function headings(): array
    {
        return [
            'id',
            'user_id',
            'customer_id',
            'customer_name',
            'source',
            'referral_source',
            'region',
            'purpose',
            'property_type',
            'category_id',
            'city_id',
            'districts_id',
            'district_name',
            'area_from',
            'area_to',
            'purchase_method',
            'budget_from',
            'budget_to',
            'seriousness',
            'purchase_goal',
            'wants_similar_offers',
            'full_name',
            'phone',
            'contact_on_whatsapp',
            'notes',
            'is_read',
            'is_archived',
            'is_ignored',
            'is_active',
            'status_id',
            'status_name',
            'customers_hub_stage_id',
            'property_ids',
            'initial_property_id',
            'initial_property_title',
            'project_id',
            'project_title',
            'responsible_employee_id',
            'responsible_employee_name',
            'inquiry_type',
            'currency',
            'bedrooms',
            'bathrooms',
            'furnished',
            'location',
            'country_code',
            'region_code',
            'city',
            'district',
            'latitude',
            'longitude',
            'location_confidence',
            'lang',
            'detected_entities_json',
            'created_at',
            'updated_at',
        ];
    }

    public function map($request): array
    {
        $employee = $request->customer?->responsibleEmployee;
        $employeeName = $employee
            ? trim(($employee->first_name ?? '') . ' ' . ($employee->last_name ?? ''))
            : '';

        $propertyIds = is_array($request->property_ids)
            ? implode(', ', $request->property_ids)
            : '';

        $detectedEntities = $request->detected_entities_json;
        if (is_array($detectedEntities)) {
            $detectedEntities = json_encode($detectedEntities, JSON_UNESCAPED_UNICODE);
        }

        return [
            $request->id,
            $request->user_id,
            $request->customer_id,
            $request->customer?->name ?? '',
            $request->source,
            $request->referral_source,
            $request->region,
            $request->purpose,
            $request->property_type,
            $request->category_id,
            $request->city_id,
            $request->districts_id,
            $request->district?->name_ar ?? '',
            $request->area_from,
            $request->area_to,
            $request->purchase_method,
            $request->budget_from,
            $request->budget_to,
            $request->seriousness,
            $request->purchase_goal,
            $request->wants_similar_offers,
            $request->full_name,
            $request->phone,
            $request->contact_on_whatsapp,
            $request->notes,
            $request->is_read,
            $request->is_archived,
            $request->is_ignored,
            $request->is_active,
            $request->status_id,
            $request->statusOption?->name_ar ?? '',
            $request->customers_hub_stage_id,
            $propertyIds,
            $request->initial_property_id,
            $request->initialProperty?->contents?->first()?->title ?? '',
            $request->project_id,
            $request->project?->contents?->first()?->title ?? '',
            $request->responsible_employee_id,
            $employeeName,
            $request->inquiry_type,
            $request->currency,
            $request->bedrooms,
            $request->bathrooms,
            $request->furnished,
            $request->location,
            $request->country_code,
            $request->region_code,
            $request->city,
            $request->district,
            $request->latitude,
            $request->longitude,
            $request->location_confidence,
            $request->lang,
            $detectedEntities,
            $request->created_at?->toDateTimeString() ?? '',
            $request->updated_at?->toDateTimeString() ?? '',
        ];
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
                        'startColor' => ['rgb' => '4472C4'],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ]);

                $sheet->getRowDimension(1)->setRowHeight(25);

                $lastColumn = Coordinate::stringFromColumnIndex(count($this->headings()));
                foreach (range('A', $lastColumn) as $col) {
                    $sheet->getColumnDimension($col)->setAutoSize(true);
                }
            },
        ];
    }
}
