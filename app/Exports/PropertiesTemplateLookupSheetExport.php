<?php

namespace App\Exports;

use App\Models\User\UserDistrict;
use App\Support\PropertyExcelMapping;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

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
