<?php

namespace App\Exports;

use App\Exports\Concerns\PreservesNumericStringsBinder;
use App\Models\User\RealestateManagement\Property;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithChunkReading;
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

class TenantPropertiesDataExportSheet implements FromQuery, WithChunkReading, WithHeadings, WithMapping, WithTitle, WithEvents, WithStrictNullComparison, WithCustomValueBinder
{
    public function __construct(
        private int $ownerId,
        private array $allowedUserIds,
    ) {}

    public function query()
    {
        return Property::query()
            ->with([
                'contents' => function ($q) {
                    $q->with([
                        'city:id,name_ar,name_en',
                        'state:id,name',
                    ]);
                },
                'galleryImages:id,property_id,image',
                'proertyAmenities.amenity:id,name',
                'specifications:id,property_id,label,value',
            ])
            ->whereIn('user_id', $this->allowedUserIds)
            ->orderBy('created_at', 'desc');
    }

    public function chunkSize(): int
    {
        return 500;
    }

    public function title(): string
    {
        return 'العقارات';
    }

    public function headings(): array
    {
        return [
            'id',
            'user_id',
            'created_by',
            'project_id',
            'created_at',
            'updated_at',
            'title',
            'address',
            'description',
            'price',
            'purpose',
            'property_type',
            'area',
            'beds',
            'bath',
            'status',
            'featured',
            'payment_method',
            'video_url',
            'city_name_ar',
            'district_name',
            'latitude',
            'longitude',
            'deed_number',
            'owner_number',
            'water_meter_number',
            'electricity_meter_number',
            'advertising_license',
            'featured_image_url',
            'gallery_image_urls',
            'amenities',
            'specifications',
        ];
    }

    public function map($property): array
    {
        $content = $property->contents->first();

        $featuredImage = $property->featured_image ? asset($property->featured_image) : '';
        $galleryImages = $property->galleryImages
            ->map(fn ($img) => asset($img->image))
            ->implode(', ');

        $amenities = $property->proertyAmenities
            ->map(fn ($a) => $a->amenity?->name)
            ->filter()
            ->implode(', ');

        $specifications = $property->specifications
            ->map(fn ($s) => ($s->label ?? '') . ': ' . ($s->value ?? ''))
            ->implode(' | ');

        $videoUrl = Str::startsWith($property->video_url ?? '', 'http')
            ? $property->video_url
            : ($property->video_url ? asset($property->video_url) : '');

        return [
            $property->id,
            $property->user_id,
            $property->created_by,
            $property->project_id,
            $property->created_at?->toDateTimeString() ?? '',
            $property->updated_at?->toDateTimeString() ?? '',
            $content?->title ?? '',
            $content?->address ?? '',
            $content?->description ?? '',
            $property->price,
            $property->purpose,
            $property->property_type,
            $property->area,
            $property->beds,
            $property->bath,
            $property->status,
            $property->featured,
            $property->payment_method,
            $videoUrl,
            $content?->city?->name_ar ?? '',
            $content?->state?->name ?? '',
            $property->latitude,
            $property->longitude,
            $property->deed_number,
            $property->owner_number,
            $property->water_meter_number,
            $property->electricity_meter_number,
            $property->advertising_license,
            $featuredImage,
            $galleryImages,
            $amenities,
            $specifications,
        ];
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

                $lastColumnIndex = count($this->headings());
                for ($i = 1; $i <= $lastColumnIndex; $i++) {
                    $sheet->getColumnDimensionByColumn($i)->setAutoSize(true);
                }
            },
        ];
    }
}
