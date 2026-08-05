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
        $next = $this->nextSlot($facts, $intent);

        return $next['question'] ?? null;
    }

    /**
     * Next missing slot to ask, or null when we should search / stop asking.
     *
     * Never re-asks a slot already recorded in `_asked_slots` (e.g. city after a
     * budget-only turn where the customer still didn't name a city — search instead).
     *
     * @param  array<string, mixed>  $facts
     * @return array{slot: string, question: string}|null
     */
    public function nextSlot(array $facts, string $intent): ?array
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

        /** @var list<string> $askedSlots */
        $askedSlots = array_values(array_filter(
            (array) ($facts['_asked_slots'] ?? []),
            static fn ($s) => is_string($s) && $s !== ''
        ));

        // Priority 1: city — skip if already asked once this session
        if (
            empty($facts['city'])
            && empty($facts['district'])
            && ! in_array('city', $askedSlots, true)
        ) {
            return ['slot' => 'city', 'question' => 'تبي في أي مدينة أو حي؟'];
        }

        // Priority 2: budget
        if (
            empty($facts['budget_max'])
            && empty($facts['budget_min'])
            && ! in_array('budget', $askedSlots, true)
        ) {
            return ['slot' => 'budget', 'question' => 'وش ميزانيتك تقريباً؟'];
        }

        // Priority 3: bedrooms — allow-list only (ask solely for apartment/villa-like types).
        // Investment / building / unknown type → search with city+budget; don't interrogate.
        $type = (string) ($facts['type'] ?? '');
        $bedroomTypes = [
            'apartment', 'villa', 'townhouse', 'duplex',
            'شقة', 'شقه', 'شقة في برج', 'شقة في عمارة',
            'فيلا', 'فله', 'فلة', 'تاون هاوس', 'دوبلكس',
        ];
        if (
            $type !== ''
            && empty($facts['bedrooms'])
            && in_array($type, $bedroomTypes, true)
            && ! in_array('bedrooms', $askedSlots, true)
        ) {
            return ['slot' => 'bedrooms', 'question' => 'كم غرفة نوم تبي؟'];
        }

        // Priority 4: property type — only when we have almost nothing yet.
        // If city/budget already known, search broadly rather than interrogating.
        if (
            empty($facts['type'])
            && empty($facts['city']) && empty($facts['district'])
            && empty($facts['budget_max']) && empty($facts['budget_min'])
            && ! in_array('type', $askedSlots, true)
        ) {
            return ['slot' => 'type', 'question' => 'تدور على شقة ولا فيلا ولا شي ثاني؟'];
        }

        return null;
    }
}
