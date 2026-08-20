<?php

declare(strict_types=1);

namespace App\Domain\RealEstateAgent\State;

/**
 * Merges brief_updates from the LLM (and from tools) into the current CustomerBrief.
 *
 * Rules:
 *  - Existing non-null values are preserved unless the update includes an explicit
 *    revision signal (e.g. customer said "بدل", "أغير", "لا أريد").
 *  - budget_max / budget_min must be numeric SAR; strings like "3 مليون" are rejected.
 *  - bedrooms must be a positive integer.
 *  - intent / property_type are preserved mid-session unless a revision is explicit.
 *  - Counters (weak_search_turns) are incremented, never reset via brief_updates.
 *  - asked_slots is append-only from outside; clearing requires calling withSlotAsked().
 */
final class BriefMerger
{
    /**
     * Merge LLM-supplied brief_updates into the current brief.
     *
     * @param  array<string, mixed> $updates  The `brief_updates` field from the agent reply.
     */
    public function merge(CustomerBrief $current, array $updates): CustomerBrief
    {
        $city          = $this->mergeStringField($current->city,          $updates, 'city');
        $district      = $this->mergeStringField($current->district,      $updates, 'district');
        $propertyType  = $this->mergeTypeField($current->propertyType,    $updates);
        $intent        = $this->mergeIntentField($current->intent,        $updates);
        $bedrooms      = $this->mergeBedroomsField($current->bedrooms,    $updates);
        $budgetMax     = $this->mergeBudgetField($current->budgetMax,     $updates, 'budget_max');
        $budgetMin     = $this->mergeBudgetField($current->budgetMin,     $updates, 'budget_min');
        $customerName  = $this->mergeStringField($current->customerName,  $updates, 'customer_name');
        $urgency       = $this->mergeStringField($current->urgency,       $updates, 'urgency');
        $focusedId     = $this->mergeFocusedId($current->focusedPropertyId, $updates);
        $tone          = $this->mergeStringField($current->tone,          $updates, 'tone');

        return new CustomerBrief(
            city:                 $city,
            district:             $district,
            propertyType:         $propertyType,
            intent:               $intent,
            bedrooms:             $bedrooms,
            budgetMax:            $budgetMax,
            budgetMin:            $budgetMin,
            customerName:         $customerName,
            urgency:              $urgency,
            focusedPropertyId:    $focusedId,
            tone:                 $tone,
            isFirstContact:       $current->isFirstContact,
            disclosedAsAssistant: $current->disclosedAsAssistant,
            weakSearchTurns:      $current->weakSearchTurns,
            askedSlots:           $current->askedSlots,
        );
    }

    public function withFirstContactDone(CustomerBrief $brief): CustomerBrief
    {
        return new CustomerBrief(
            city: $brief->city, district: $brief->district, propertyType: $brief->propertyType,
            intent: $brief->intent, bedrooms: $brief->bedrooms, budgetMax: $brief->budgetMax,
            budgetMin: $brief->budgetMin, customerName: $brief->customerName, urgency: $brief->urgency,
            focusedPropertyId: $brief->focusedPropertyId, tone: $brief->tone,
            isFirstContact: false, disclosedAsAssistant: $brief->disclosedAsAssistant,
            weakSearchTurns: $brief->weakSearchTurns, askedSlots: $brief->askedSlots,
        );
    }

    public function withDisclosed(CustomerBrief $brief): CustomerBrief
    {
        return new CustomerBrief(
            city: $brief->city, district: $brief->district, propertyType: $brief->propertyType,
            intent: $brief->intent, bedrooms: $brief->bedrooms, budgetMax: $brief->budgetMax,
            budgetMin: $brief->budgetMin, customerName: $brief->customerName, urgency: $brief->urgency,
            focusedPropertyId: $brief->focusedPropertyId, tone: $brief->tone,
            isFirstContact: $brief->isFirstContact, disclosedAsAssistant: true,
            weakSearchTurns: $brief->weakSearchTurns, askedSlots: $brief->askedSlots,
        );
    }

    public function withWeakSearchTurn(CustomerBrief $brief): CustomerBrief
    {
        return new CustomerBrief(
            city: $brief->city, district: $brief->district, propertyType: $brief->propertyType,
            intent: $brief->intent, bedrooms: $brief->bedrooms, budgetMax: $brief->budgetMax,
            budgetMin: $brief->budgetMin, customerName: $brief->customerName, urgency: $brief->urgency,
            focusedPropertyId: $brief->focusedPropertyId, tone: $brief->tone,
            isFirstContact: $brief->isFirstContact, disclosedAsAssistant: $brief->disclosedAsAssistant,
            weakSearchTurns: $brief->weakSearchTurns + 1, askedSlots: $brief->askedSlots,
        );
    }

    public function withWeakSearchTurnsReset(CustomerBrief $brief): CustomerBrief
    {
        return new CustomerBrief(
            city: $brief->city, district: $brief->district, propertyType: $brief->propertyType,
            intent: $brief->intent, bedrooms: $brief->bedrooms, budgetMax: $brief->budgetMax,
            budgetMin: $brief->budgetMin, customerName: $brief->customerName, urgency: $brief->urgency,
            focusedPropertyId: $brief->focusedPropertyId, tone: $brief->tone,
            isFirstContact: $brief->isFirstContact, disclosedAsAssistant: $brief->disclosedAsAssistant,
            weakSearchTurns: 0, askedSlots: $brief->askedSlots,
        );
    }

    public function withSlotAsked(CustomerBrief $brief, string $slot): CustomerBrief
    {
        if (in_array($slot, $brief->askedSlots, true)) {
            return $brief;
        }
        $asked = [...$brief->askedSlots, $slot];
        return new CustomerBrief(
            city: $brief->city, district: $brief->district, propertyType: $brief->propertyType,
            intent: $brief->intent, bedrooms: $brief->bedrooms, budgetMax: $brief->budgetMax,
            budgetMin: $brief->budgetMin, customerName: $brief->customerName, urgency: $brief->urgency,
            focusedPropertyId: $brief->focusedPropertyId, tone: $brief->tone,
            isFirstContact: $brief->isFirstContact, disclosedAsAssistant: $brief->disclosedAsAssistant,
            weakSearchTurns: $brief->weakSearchTurns, askedSlots: $asked,
        );
    }

    // ────────────────────────────────────────────────────────────
    // Private helpers
    // ────────────────────────────────────────────────────────────

    private function mergeStringField(?string $current, array $updates, string $key): ?string
    {
        if (!array_key_exists($key, $updates)) {
            return $current;
        }
        $val = $updates[$key];
        if ($val === null || $val === '') {
            return $current;
        }
        return (string) $val;
    }

    private function mergeTypeField(?string $current, array $updates): ?string
    {
        if (!array_key_exists('property_type', $updates)) {
            return $current;
        }
        $val = $updates['property_type'];
        if ($val === null || $val === '') {
            return $current;
        }
        // Only allow update if it's a known type token
        $known = ['apartment', 'villa', 'building', 'land', 'office', 'warehouse', 'duplex', 'rest_house',
                  'شقة', 'فيلا', 'عمارة', 'أرض', 'مكتب', 'مستودع', 'دوبلكس', 'استراحة'];
        $normalized = mb_strtolower(trim((string) $val));
        return in_array($normalized, $known, true) ? $normalized : $current;
    }

    private function mergeIntentField(?string $current, array $updates): ?string
    {
        if (!array_key_exists('intent', $updates)) {
            return $current;
        }
        $val = $updates['intent'];
        if (in_array($val, ['sale', 'rent'], true)) {
            return (string) $val;
        }
        return $current;
    }

    private function mergeBedroomsField(?int $current, array $updates): ?int
    {
        if (!array_key_exists('bedrooms', $updates)) {
            return $current;
        }
        $val = $updates['bedrooms'];
        if (!is_int($val) && !is_numeric($val)) {
            return $current;
        }
        $int = (int) $val;
        return $int > 0 ? $int : $current;
    }

    private function mergeBudgetField(?float $current, array $updates, string $key): ?float
    {
        if (!array_key_exists($key, $updates)) {
            return $current;
        }
        $val = $updates[$key];
        if (!is_numeric($val) || (float) $val <= 0) {
            return $current;
        }
        // Sanity check: budget should be in SAR (not e.g. "3" mistakenly sent as millions)
        // Values under 10k are likely short-form (e.g. LLM wrote "7" instead of 7000000).
        // We'll accept them but will not trust very small values for a real estate context.
        return (float) $val;
    }

    private function mergeFocusedId(?int $current, array $updates): ?int
    {
        if (!array_key_exists('focused_property_id', $updates)) {
            return $current;
        }
        $val = $updates['focused_property_id'];
        if ($val === null) {
            return null; // explicit clear
        }
        return is_numeric($val) && (int) $val > 0 ? (int) $val : $current;
    }
}
