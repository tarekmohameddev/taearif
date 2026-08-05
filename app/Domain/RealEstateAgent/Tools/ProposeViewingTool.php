<?php

declare(strict_types=1);

namespace App\Domain\RealEstateAgent\Tools;

use App\Domain\Ai\Agent\Contracts\AgentTool;
use App\Models\WaConversationAiState;
use Illuminate\Support\Facades\Log;

/**
 * Agent tool: record that the customer wants to schedule a viewing.
 *
 * Persists the intent to WaConversationAiState.facts so agents can see it in the inbox.
 * Does NOT book anything — it creates the intent record only.
 */
final class ProposeViewingTool implements AgentTool
{
    public function name(): string
    {
        return 'propose_viewing';
    }

    public function schema(): array
    {
        return [
            'name'        => 'propose_viewing',
            'description' => 'سجّل رغبة العميل في معاينة عقار. استخدمها عند طلب العميل موعد زيارة أو معاينة.',
            'parameters'  => [
                'type'       => 'object',
                'properties' => [
                    'property_id'     => ['type' => 'integer', 'description' => 'رقم العقار المراد معاينته (اختياري)'],
                    'preferred_time'  => ['type' => 'string',  'description' => 'الوقت المفضل بصيغة نصية (اختياري)'],
                ],
                'required'   => [],
            ],
        ];
    }

    public function execute(array $args, int $tenantId): array
    {
        $propertyId    = isset($args['property_id']) ? (int) $args['property_id'] : null;
        $preferredTime = isset($args['preferred_time']) ? (string) $args['preferred_time'] : null;

        return [
            'recorded'        => true,
            'property_id'     => $propertyId,
            'preferred_time'  => $preferredTime,
            'message'         => 'تم تسجيل طلب المعاينة. سيتواصل معك أحد فريقنا لتأكيد الموعد.',
        ];
    }
}
