<?php

namespace App\Services;

use App\Models\User\RealestateManagement\Property;
use App\Models\User\RealestateManagement\Category;

class PropertyFilterService
{
    public function buildQuery($tenantId, $request, $userCurrentLang)
    {
        $cityNameColumn = $userCurrentLang->code === 'ar' ? 'user_cities.name_ar' : 'user_cities.name_en';

        $sortOptions = [
            'new' => ['user_properties.id', 'desc'],
            'old' => ['user_properties.id', 'asc'],
            'high-to-low' => ['user_properties.price', 'desc'],
            'low-to-high' => ['user_properties.price', 'asc'],
        ];
        [$order_by_column, $order] = $sortOptions[$request->sort] ?? ['user_properties.id', 'desc'];

        $query = Property::where([
                ['user_properties.user_id', $tenantId],
                ['user_properties.status', 1],
                ['user_properties.featured', 1],
            ])
            ->join('user_property_contents', 'user_properties.id', '=', 'user_property_contents.property_id')
            ->leftJoin('user_cities', 'user_cities.id', '=', 'user_property_contents.city_id')
            ->leftJoin('user_states', 'user_states.id', '=', 'user_property_contents.state_id')
            ->leftJoin('user_countries', 'user_countries.id', '=', 'user_property_contents.country_id')
            ->where('user_property_contents.language_id', $userCurrentLang->id)
            // --- Dynamic filters
            ->when($request->filled('type') && $request->type !== 'all', fn($q) => $q->where('user_properties.type', $request->type))
            ->when($request->filled('purpose') && $request->purpose !== 'all', fn($q) => $q->where('user_properties.purpose', $request->purpose))
            ->when($request->filled('category') && $request->category !== 'all', fn($q) => $q->where('user_properties.category_id', $request->category))
            ->when($request->filled('min'), fn($q) => $q->where('user_properties.price', '>=', intval($request->min)))
            ->when($request->filled('max'), fn($q) => $q->where('user_properties.price', '<=', intval($request->max)))
            ->when($request->filled('state_id'), fn($q) => $q->where('user_property_contents.state_id', $request->state_id))
            ->when($request->filled('city_id'), fn($q) => $q->where('user_property_contents.city_id', $request->city_id))
            ->when($request->filled('beds'), fn($q) => $q->where('user_properties.beds', $request->beds))
            ->when($request->filled('baths'), fn($q) => $q->where('user_properties.bath', $request->baths))
            // Add more filters as needed
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

        return $query;
    }
}
