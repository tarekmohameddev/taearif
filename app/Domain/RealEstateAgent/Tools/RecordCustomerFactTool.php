<?php

declare(strict_types=1);

namespace App\Domain\RealEstateAgent\Tools;

use App\Domain\Ai\Agent\Contracts\AgentTool;

/**
 * Agent tool: record one or more facts learned about the customer.
 *
 * Replaces MessageFactExtractor: the model calls this tool whenever it learns
 * city, budget, property type, etc., from the conversation.  BriefMerger then
 * applies the update to the CustomerBrief with proper type validation.
 *
 * Using a structured tool here — rather than asking the model to put facts in
 * `brief_updates` on the final reply — allows the model to record facts from
 * early turns even before it has enough information to produce a reply.
 */
final class RecordCustomerFactTool implements AgentTool
{
    public function name(): string
    {
        return 'record_customer_fact';
    }

    public function schema(): array
    {
        return [
            'name'        => 'record_customer_fact',
            'description' => 'سجّل معلومة تعلمتها عن العميل (المدينة، الميزانية، نوع العقار، إلخ). استخدم هذه الأداة بمجرد استخراج أي معلومة من كلام العميل.',
            'parameters'  => [
                'type'                 => 'object',
                'additionalProperties' => false,
                'properties'           => [
                    'city'          => ['type' => 'string',  'description' => 'المدينة بالعربية'],
                    'district'      => ['type' => 'string',  'description' => 'الحي أو الشارع'],
                    'property_type' => [
                        'type'        => 'string',
                        'enum'        => ['apartment', 'villa', 'building', 'land', 'office', 'warehouse', 'duplex', 'rest_house'],
                        'description' => 'نوع العقار',
                    ],
                    'intent'        => ['type' => 'string', 'enum' => ['sale', 'rent'], 'description' => 'بيع أو إيجار'],
                    'bedrooms'      => ['type' => 'integer', 'minimum' => 1, 'description' => 'عدد غرف النوم'],
                    'budget_max'    => ['type' => 'number',  'description' => 'أقصى ميزانية بالريال'],
                    'budget_min'    => ['type' => 'number',  'description' => 'أدنى ميزانية بالريال'],
                    'customer_name' => ['type' => 'string',  'description' => 'اسم العميل'],
                    'urgency'       => ['type' => 'string',  'enum' => ['immediate', 'flexible'], 'description' => 'مدى الاستعجال'],
                ],
                'required'             => [],
            ],
        ];
    }

    public function execute(array $args, int $tenantId): array
    {
        // The actual merge is handled by BriefMerger in Employee::runTurn().
        // Here we just return the validated args so FactLedger can relay them.
        return ['facts_recorded' => array_filter($args, fn ($v) => $v !== null && $v !== '')];
    }
}
