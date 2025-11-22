<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class PropertiesTemplateExport implements WithHeadings, WithTitle
{
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
            'region_id',
            'city_id',
            'featured_image',
            'video_url',
            'status',
            'price_per_meter',
            'gallery_images',
            'amenity_ids',
            'specifications'
        ];
    }

    public function title(): string
    {
        return 'Properties Template';
    }
}
