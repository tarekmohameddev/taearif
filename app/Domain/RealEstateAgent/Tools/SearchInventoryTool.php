<?php

declare(strict_types=1);

namespace App\Domain\RealEstateAgent\Tools;

use App\Domain\Ai\Agent\Contracts\AgentTool;
use App\Domain\Communication\WhatsApp\Bot\Tools\PropertySearchTool;

/**
 * Agent tool: search the tenant's property inventory.
 *
 * Delegates entirely to the well-tested PropertySearchTool (keeps CATEGORY_MAP,
 * location resolver, junk-row filter, relaxed retries).
 *
 * Returns `insufficient: true` with `need` array when mandatory parameters are
 * missing, letting the model phrase the clarifying question naturally.
 */
final class SearchInventoryTool implements AgentTool
{
    public function __construct(
        private readonly PropertySearchTool $inner,
    ) {}

    public function name(): string
    {
        return 'search_inventory';
    }

    public function schema(): array
    {
        return [
            'name'        => 'search_inventory',
            'description' => 'ابحث في قائمة العقارات المتاحة للبيع أو الإيجار. استخدم هذه الأداة عند رغبة العميل في البحث عن عقار. يمكن البحث بدون جميع المعايير.',
            'parameters'  => [
                'type'       => 'object',
                'properties' => [
                    'location'      => ['type' => 'string',  'description' => 'المدينة أو الحي أو الشارع بالعربية'],
                    'purpose'       => ['type' => 'string',  'enum' => ['sale', 'rent'], 'description' => 'بيع أو إيجار'],
                    'property_type' => ['type' => 'string',  'description' => 'نوع العقار: apartment|villa|building|land|office|warehouse|duplex|rest_house أو بالعربية (شقة، فيلا، عمارة...)'],
                    'bedrooms'      => ['type' => 'integer', 'description' => 'عدد غرف النوم المطلوبة'],
                    'budget_max'    => ['type' => 'number',  'description' => 'الحد الأقصى للميزانية بالريال السعودي'],
                    'budget_min'    => ['type' => 'number',  'description' => 'الحد الأدنى للميزانية بالريال السعودي'],
                ],
                'required'   => [],
            ],
        ];
    }

    public function execute(array $args, int $tenantId): array
    {
        $result = $this->inner->execute($tenantId, $args);

        // Surface clarification as a structured signal the agent can handle
        if (!empty($result['clarification_needed'])) {
            return [
                'insufficient'            => true,
                'need'                    => ['location'],
                'clarification_question'  => $result['clarification_question'] ?? 'في أي مدينة أو حي تبحث؟',
                'results'                 => [],
            ];
        }

        return $result;
    }
}
