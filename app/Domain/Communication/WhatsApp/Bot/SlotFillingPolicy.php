<?php

declare(strict_types=1);

namespace App\Domain\Communication\WhatsApp\Bot;

/**
 * Determines which slot (missing fact) to ask about next, given the known facts
 * extracted so far from the conversation.
 *
 * Fill-rate data from the 128k corpus:
 *   city 47%, district 49%, urgency 66%, budget_max 37%, bedrooms 22%, furnished 9%
 *
 * Priority order (highest-impact first):
 *   city → budget_max → bedrooms → property_type → furnished
 *
 * Returns null when all essential slots are filled or asking more questions would
 * be intrusive (too many consecutive questions).
 */
final class SlotFillingPolicy
{
    private const MAX_CONSECUTIVE_QUESTIONS = 2;

    /**
     * @param  array<string, mixed>  $facts      Currently known facts from WaConversationAiState
     * @return string|null  Arabic next-question text, or null when nothing to ask
     */
    public function nextQuestion(array $facts, string $intent): ?string
    {
        // Only prompt for more info when there is a property-search or pricing intent
        if (! in_array($intent, ['property_search', 'pricing', 'viewing'], true)) {
            return null;
        }

        // Avoid over-questioning
        $questionsAsked = (int) ($facts['_questions_asked'] ?? 0);
        if ($questionsAsked >= self::MAX_CONSECUTIVE_QUESTIONS) {
            return null;
        }

        // Priority 1: city
        if (empty($facts['city']) && empty($facts['district'])) {
            return 'أي مدينة أو حي تفضل؟';
        }

        // Priority 2: budget
        if (empty($facts['budget_max']) && empty($facts['budget_min'])) {
            return 'ما هي ميزانيتك التقريبية؟';
        }

        // Priority 3: bedrooms (skip for non-residential types)
        $type = (string) ($facts['type'] ?? '');
        $skipBedroomsTypes = [
            'office', 'land', 'warehouse', 'building',
            'مكتب', 'أرض', 'ارض', 'مستودع',
            'عمارة', 'عمارة سكنية', 'عمارة تجارية', 'مبنى',
            'محل', 'محل تجاري',
        ];

        if (empty($facts['bedrooms']) && ! in_array($type, $skipBedroomsTypes, true)) {
            return 'كم عدد غرف النوم المطلوب؟';
        }

        // Priority 4: property type
        if (empty($facts['type'])) {
            return 'هل تبحث عن شقة، فيلا، أو نوع آخر؟';
        }

        return null;
    }
}
