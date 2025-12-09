<?php

namespace App\Services\Matching;

use App\Support\DTO\UnifiedRequest;
use App\Models\User\RealestateManagement\Property;

class PromptBuilder
{
    public function buildScoringPrompt(UnifiedRequest $req, array $properties, string $lang = 'en'): array
    {
        $system = 'You are a real estate matching assistant. Score each property 0..50 for semantic fit. Explanations MUST be written in Arabic (Saudi Arabia). Return ONLY strict JSON: {"results":[{"property_id":123,"ai_score":42,"matched_criteria":["location","budget"],"explanation":"..."}]}';
        if ($lang === 'ar') {
            $system = 'أنت مساعد لمطابقة العقارات. قيّم كل عقار من 0 إلى 50 للملاءمة الدلالية. الشرح يجب أن يكون بالعربية السعودية. أعد فقط JSON بالشكل المحدد: {"results":[{"property_id":123,"ai_score":42,"matched_criteria":["location","budget"],"explanation":"..."}]}';
        }

        $userPayload = [
            'request' => [
                'source' => $req->source,
                'property_type' => $req->propertyType,
                'category_id' => $req->categoryId,
                'purpose' => $req->purpose,
                'location' => [
                    'region' => $req->region,
                    'region_id' => $req->regionId,
                    'city_id' => $req->cityId,
                    'district_id' => $req->districtId,
                    'latitude' => $req->latitude,
                    'longitude' => $req->longitude,
                ],
                'budget' => [
                    'from' => $req->budgetFrom,
                    'to' => $req->budgetTo,
                    'single' => $req->budget,
                    'currency' => $req->currency,
                ],
                'size' => [
                    'area_from' => $req->areaFrom,
                    'area_to' => $req->areaTo,
                    'min_area_sqm' => $req->minAreaSqm,
                    'max_area_sqm' => $req->maxAreaSqm,
                ],
                'features' => [
                    'bedrooms' => $req->bedrooms,
                    'bathrooms' => $req->bathrooms,
                    'furnished' => $req->furnished,
                ],
                'meta' => [
                    'seriousness' => $req->seriousness,
                    'urgency' => $req->urgency,
                    'notes' => $req->notes,
                    'message' => $req->message,
                ],
            ],
            'properties' => array_map(function (Property $p) {
                return [
                    'id' => $p->id,
                    'title' => optional($p->first_content)->title ?? null,
                    'description' => optional($p->first_content)->description ?? null,
                    'category_id' => $p->category_id,
                    'type' => $p->type,
                    'purpose' => $p->purpose,
                    'price' => $p->price,
                    'area' => $p->area,
                    'beds' => $p->beds,
                    'bath' => $p->bath,
                    'region_id' => $p->region_id,
                    'latitude' => $p->latitude,
                    'longitude' => $p->longitude,
                    'status' => $p->status,
                ];
            }, $properties),
            'scoring' => [
                'explain' => true,
                'max_points' => 50,
                'priorities' => ['location', 'budget', 'bedrooms', 'area', 'purpose', 'type'],
                'explain_language' => 'ar-SA',
            ],
        ];

        return [
            'system' => $system,
            'user' => json_encode($userPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ];
    }
}


