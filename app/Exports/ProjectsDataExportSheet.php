<?php

namespace App\Exports;

use App\Models\User\RealestateManagement\Project;
use Illuminate\Support\Str;
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

class ProjectsDataExportSheet implements FromQuery, WithChunkReading, WithHeadings, WithMapping, WithTitle, WithEvents
{
    public function __construct(
        private array $allowedUserIds,
    ) {}

    public function query()
    {
        return Project::query()
            ->with([
                'contents',
                'galleryImages',
                'floorplanImages',
                'specifications',
                'projectTypes',
                'district',
            ])
            ->withCount('properties')
            ->whereIn('user_id', $this->allowedUserIds)
            ->orderBy('created_at', 'desc');
    }

    public function chunkSize(): int
    {
        return 500;
    }

    public function title(): string
    {
        return 'المشاريع';
    }

    public function headings(): array
    {
        return [
            'id',
            'user_id',
            'title',
            'slug',
            'address',
            'description',
            'developer',
            'min_price',
            'max_price',
            'units',
            'completion_date',
            'complete_status',
            'published',
            'featured',
            'city_id',
            'district_name_ar',
            'amenities',
            'properties_count',
            'latitude',
            'longitude',
            'featured_image_url',
            'gallery_image_urls',
            'floorplan_image_urls',
            'brochure_url',
            'video_url',
            'created_by',
            'created_at',
            'updated_at',
        ];
    }

    public function map($project): array
    {
        $content = $project->contents->first();

        $featuredImage = $project->featured_image ? asset($project->featured_image) : '';
        $galleryImages = $project->galleryImages
            ->map(fn ($img) => asset($img->image))
            ->implode(', ');
        $floorplanImages = $project->floorplanImages
            ->map(fn ($img) => asset($img->image))
            ->implode(', ');
        $brochureUrl = $project->brochure ? asset($project->brochure) : '';
        $videoUrl = Str::startsWith($project->video_url ?? '', 'http')
            ? $project->video_url
            : ($project->video_url ? asset($project->video_url) : '');

        $amenities = is_array($project->amenities)
            ? implode(', ', $project->amenities)
            : '';

        return [
            $project->id,
            $project->user_id,
            $content?->title ?? '',
            $content?->slug ?? '',
            $content?->address ?? '',
            $content?->description ?? '',
            $project->developer,
            $project->min_price,
            $project->max_price,
            $project->units,
            $project->completion_date,
            $project->complete_status,
            $project->published,
            $project->featured,
            $project->city_id,
            $project->district?->name_ar ?? '',
            $amenities,
            $project->properties_count,
            $project->latitude,
            $project->longitude,
            $featuredImage,
            $galleryImages,
            $floorplanImages,
            $brochureUrl,
            $videoUrl,
            $project->created_by,
            $project->created_at?->toDateTimeString() ?? '',
            $project->updated_at?->toDateTimeString() ?? '',
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
