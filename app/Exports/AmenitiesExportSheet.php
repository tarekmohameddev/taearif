<?php

namespace App\Exports;

use App\Models\User\RealestateManagement\Amenity;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Concerns\WithTitle;

/**
 * Exports the tenant's property amenities so they can be recreated on import
 * and keep property amenity references intact across tenants.
 */
class AmenitiesExportSheet implements FromArray, WithHeadings, WithTitle, WithStrictNullComparison
{
    public function __construct(private int $ownerId) {}

    public function title(): string
    {
        return 'Amenities';
    }

    public function headings(): array
    {
        return ['id', 'name', 'slug', 'icon', 'language_id', 'status', 'serial_number'];
    }

    public function array(): array
    {
        $rows = [];

        foreach (Amenity::where('user_id', $this->ownerId)->get() as $a) {
            $rows[] = [$a->id, $a->name, $a->slug, $a->icon, $a->language_id, (int) $a->status, (int) $a->serial_number];
        }

        return $rows;
    }
}
