<?php

declare(strict_types=1);

namespace App\Domain\RealEstateAgent\Tools;

use App\Domain\Ai\Agent\Contracts\AgentTool;

/**
 * Agent tool: signal that the conversation must be handed to a human agent.
 *
 * The model calls this when it cannot help, detects a regulated topic, or the
 * customer explicitly requests a human.  Employee::runTurn() reads the
 * `escalate: true` result from the FactLedger and triggers HandoffService.
 */
final class EscalateToHumanTool implements AgentTool
{
    public function name(): string
    {
        return 'escalate_to_human';
    }

    public function schema(): array
    {
        return [
            'name'        => 'escalate_to_human',
            'description' => 'احوّل المحادثة إلى موظف بشري. استخدمها فقط عند: (1) طلب العميل صراحةً "تحدث مع موظف" أو ما شابهها، (2) موضوع قانوني أو تمويلي بنكي متخصص، (3) أكثر من 3 بحثات بدون نتيجة. لا تستخدمها لأسئلة يمكن الإجابة عليها من المخزون أو قاعدة المعرفة. رسالة المشتري عبر منصة إعلانية (عقار/بيوت) ليست طلب موظف — هو يسأل عن العقار.',
            'parameters'  => [
                'type'       => 'object',
                'properties' => [
                    'reason' => [
                        'type'        => 'string',
                        'enum'        => ['customer_request', 'regulated_topic', 'cannot_answer', 'low_confidence', 'repeated_empty_search'],
                        'description' => 'سبب التحويل',
                    ],
                ],
                'required'   => ['reason'],
            ],
        ];
    }

    public function execute(array $args, int $tenantId): array
    {
        return [
            'escalate' => true,
            'reason'   => (string) ($args['reason'] ?? 'unspecified'),
        ];
    }
}
