<?php

declare(strict_types=1);

namespace App\Domain\RealEstateAgent\Tools;

use App\Domain\Ai\Agent\Contracts\AgentTool;
use App\Models\User\RealestateManagement\Property;
use Illuminate\Support\Facades\Log;

/**
 * Agent tool: fetch full details for a single property by ID.
 *
 * Used when the customer asks follow-up questions about a specific listing
 * already surfaced by search_inventory.
 */
final class GetPropertyDetailsTool implements AgentTool
{
    public function name(): string
    {
        return 'get_property_details';
    }

    public function schema(): array
    {
        return [
            'name'        => 'get_property_details',
            'description' => 'احصل على التفاصيل الكاملة والأسئلة الشائعة (FAQs) لعقار محدد. استخدم هذه الأداة في حالتين: (1) عندما يسأل العميل عن معلومة خاصة بعقار مثل رقم الحارس، أطوال الأرض، الدور، الموقع الدقيق، رقم الاتصال، أو أي تفصيلة لا تتوفر في بيانات البحث الأساسي — فابحث في حقل faqs أولاً، (2) عند طلب تفاصيل إضافية عن عقار ظهر في نتائج البحث.',
            'parameters'  => [
                'type'       => 'object',
                'properties' => [
                    'property_id' => ['type' => 'integer', 'description' => 'رقم العقار (id)'],
                ],
                'required'   => ['property_id'],
            ],
        ];
    }

    public function execute(array $args, int $tenantId): array
    {
        $propertyId = (int) ($args['property_id'] ?? 0);
        if ($propertyId <= 0) {
            return ['error' => 'property_id is required and must be a positive integer'];
        }

        try {
            $property = Property::with([
                'contents',
                'externalLinks' => fn ($q) => $q->where('active', true),
            ])
                ->where('user_id', $tenantId)
                ->find($propertyId);

            if ($property === null) {
                return ['found' => false, 'property_id' => $propertyId];
            }

            $content = $this->pickBestContent($property);

            $externalLinks = ($property->externalLinks ?? collect())->map(fn ($l) => [
                'platform' => $l->platform,
                'url'      => $l->url,
                'label'    => $l->label,
            ])->all();

            $faqs = is_array($property->faqs) ? $property->faqs : [];

            return [
                'found'          => true,
                'id'             => $property->id,
                'title'          => trim((string) ($content?->title ?? '')) ?: ('عقار #' . $property->id),
                'address'        => trim((string) ($content?->address ?? '')),
                'description'    => trim((string) ($content?->description ?? '')),
                'price'          => (float) $property->price,
                'currency'       => 'SAR',
                'purpose'        => $property->purpose,
                'property_type'  => $property->property_type,
                'bedrooms'       => $property->beds,
                'bathrooms'      => $property->bath,
                'area_sqm'       => $property->area,
                'floor'          => $property->floor ?? null,
                'year_built'     => $property->year_built ?? null,
                'furnished'      => $property->furnished ?? null,
                'image_url'      => $property->featured_image_url ?? null,
                'video_url'      => $property->video_url ?? null,
                'virtual_tour'   => $property->virtual_tour ?? null,
                'faqs'           => $faqs,
                'external_links' => $externalLinks,
            ];
        } catch (\Throwable $e) {
            Log::error('agent.tool.get_property_details.error', [
                'property_id' => $propertyId,
                'tenant_id'   => $tenantId,
                'error'       => $e->getMessage(),
            ]);
            return ['error' => 'Failed to load property details'];
        }
    }

    private function pickBestContent(Property $p): mixed
    {
        $contents = $p->contents;
        if ($contents === null || $contents->isEmpty()) {
            return null;
        }
        return $contents->first(fn ($c) => trim((string) ($c->title ?? '')) !== '') ?? $contents->first();
    }
}
