<?php

namespace App\Services;

use App\Models\Api\UserPropertyRequestFieldSetting;
use Illuminate\Support\Facades\Cache;

class PropertyRequestFormSettings
{
    public static function defaultMap(): array
    {
        return [
            'category_id'         => ['is_visible' => true,  'is_required' => true],
            'property_type'       => ['is_visible' => true,  'is_required' => true],
            'city_id'             => ['is_visible' => true,  'is_required' => true],
            'neighborhood_id'     => ['is_visible' => true,  'is_required' => true],
            'area_from'           => ['is_visible' => true,  'is_required' => false],
            'area_to'             => ['is_visible' => true,  'is_required' => false],
            'purchase_method'     => ['is_visible' => true,  'is_required' => true],
            'budget_from'         => ['is_visible' => true,  'is_required' => true],
            'budget_to'           => ['is_visible' => true,  'is_required' => true],
            'seriousness'         => ['is_visible' => true,  'is_required' => false],
            'purchase_goal'       => ['is_visible' => true,  'is_required' => false],
            'wants_similar_offers'=> ['is_visible' => true,  'is_required' => false],
            'full_name'           => ['is_visible' => true,  'is_required' => true],
            'phone'               => ['is_visible' => true,  'is_required' => true],
            'contact_on_whatsapp' => ['is_visible' => true,  'is_required' => false],
            'notes'               => ['is_visible' => true,  'is_required' => false],
        ];
    }

    public function forTenant(int $tenantId): array
    {
        $cacheKey = "pr_form_settings.tenant.$tenantId";

        return Cache::remember($cacheKey, 300, function () use ($tenantId) {
            $defaults = self::defaultMap();

            $rows = UserPropertyRequestFieldSetting::where('user_id', $tenantId)->get();

            $db = [];
            foreach ($rows as $row) {
                $db[$row->field_key] = [
                    'is_visible'  => (bool) $row->is_visible,
                    'is_required' => (bool) $row->is_required,
                    'label_ar'    => $row->label_ar,
                    'label_en'    => $row->label_en,
                    'sort_order'  => $row->sort_order,
                    'meta'        => $row->meta ?? [],
                ];
            }

            $merged = [];
            foreach ($defaults as $key => $def) {
                $merged[$key] = array_merge($def, $db[$key] ?? []);
            }

            return $merged;
        });
    }
}
