<?php

namespace App\Services;

use App\Models\User\RealestateManagement\Property;
use App\Models\User\RealestateManagement\Category;


class PropertyFilterService
{
    public function buildQuery($tenantId, $request, $userCurrentLang)
    {
        $cityNameColumn = $userCurrentLang->code === 'ar' ? 'user_cities.name_ar' : 'user_cities.name_en';

        $type = $request->filled('type') && $request->type !== 'all' ? $request->type : null;
        $price = $request->filled('price') && $request->price !== 'all' ? $request->price : null;
        $purpose = $request->filled('purpose') && $request->purpose !== 'all' ? $request->purpose : null;
        $min = $request->filled('min') ? intval($request->min) : null;
        $max = $request->filled('max') ? intval($request->max) : null;
        $category = $request->filled('category') && $request->category !== 'all' ? $request->category : null;
        $propertyCategory = $category ? Category::where('id', $category)->first() : null;

        $sortOptions = [
            'new' => ['user_properties.id', 'desc'],
            'old' => ['user_properties.id', 'asc'],
            'high-to-low' => ['user_properties.price', 'desc'],
            'low-to-high' => ['user_properties.price', 'asc'],
        ];
        [$order_by_column, $order] = $sortOptions[$request->sort] ?? ['user_properties.id', 'desc'];

        $propertyQuery = Property::where([
                ['user_properties.user_id', $tenantId],
                ['user_properties.status', 1],
            ])
            ->join('user_property_contents', 'user_properties.id', '=', 'user_property_contents.property_id')
            ->leftJoin('user_cities', 'user_cities.id', '=', 'user_property_contents.city_id')
            ->leftJoin('user_states', 'user_states.id', '=', 'user_property_contents.state_id')
            ->leftJoin('user_countries', 'user_countries.id', '=', 'user_property_contents.country_id')
            ->where('user_property_contents.language_id', $userCurrentLang->id)
            ->when($type, fn($q) => $q->where('user_properties.type', $type))
            ->when($purpose, fn($q) => $q->where('user_properties.purpose', $purpose))
            ->when($category && $propertyCategory, fn($q) => $q->where('user_properties.category_id', $propertyCategory->id))
            ->when($min && $max, fn($q) => $q->whereBetween('user_properties.price', [$min, $max]))
            ->selectRaw("
                user_properties.*,
                user_property_contents.title,
                user_property_contents.slug,
                user_property_contents.address,
                user_property_contents.description,
                user_property_contents.language_id,
                {$cityNameColumn} as city_name,
                user_states.name as state_name,
                user_countries.name as country_name
            ")
            ->orderBy($order_by_column, $order);

        return $propertyQuery;
    }
}
