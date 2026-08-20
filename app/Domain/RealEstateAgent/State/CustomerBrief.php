<?php

declare(strict_types=1);

namespace App\Domain\RealEstateAgent\State;

/**
 * Typed, immutable snapshot of everything the bot knows about a customer's
 * current search session.
 *
 * Stored in WaConversationAiState.facts as JSON.
 * BriefMerger creates new instances; nothing mutates this object.
 */
final class CustomerBrief
{
    public function __construct(
        // Search criteria
        public readonly ?string $city             = null,
        public readonly ?string $district         = null,
        public readonly ?string $propertyType     = null,   // canonical token, e.g. "apartment", "villa", "building"
        public readonly ?string $intent           = null,   // "sale" | "rent"
        public readonly ?int    $bedrooms         = null,
        public readonly ?float  $budgetMax        = null,
        public readonly ?float  $budgetMin        = null,
        // Customer identity
        public readonly ?string $customerName     = null,
        public readonly ?string $urgency          = null,   // "immediate" | "flexible" | null
        // Session state
        public readonly ?int    $focusedPropertyId = null,  // ID of the property currently in focus
        public readonly ?string $tone             = null,   // "friendly" | "frustrated" | "urgent"
        public readonly bool    $isFirstContact   = true,
        public readonly bool    $disclosedAsAssistant = false,
        // Counters for session health
        public readonly int     $weakSearchTurns  = 0,
        /** @var string[] Slots already asked this session — never re-ask */
        public readonly array   $askedSlots       = [],
    ) {}

    /**
     * Serialize to the array stored in WaConversationAiState.facts.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'city'                   => $this->city,
            'district'               => $this->district,
            'property_type'          => $this->propertyType,
            'intent'                 => $this->intent,
            'bedrooms'               => $this->bedrooms,
            'budget_max'             => $this->budgetMax,
            'budget_min'             => $this->budgetMin,
            'customer_name'          => $this->customerName,
            'urgency'                => $this->urgency,
            'focused_property_id'    => $this->focusedPropertyId,
            'tone'                   => $this->tone,
            'is_first_contact'       => $this->isFirstContact,
            'disclosed_as_assistant' => $this->disclosedAsAssistant,
            'weak_search_turns'      => $this->weakSearchTurns,
            'asked_slots'            => $this->askedSlots,
        ], fn ($v) => $v !== null && $v !== [] && $v !== false);
    }

    /**
     * Hydrate from the stored facts array.
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            city:                 isset($data['city'])             ? (string) $data['city']             : null,
            district:             isset($data['district'])         ? (string) $data['district']         : null,
            propertyType:         isset($data['property_type'])    ? (string) $data['property_type']    : null,
            intent:               isset($data['intent'])           ? (string) $data['intent']           : null,
            bedrooms:             isset($data['bedrooms'])         ? (int)    $data['bedrooms']         : null,
            budgetMax:            isset($data['budget_max'])       ? (float)  $data['budget_max']       : null,
            budgetMin:            isset($data['budget_min'])       ? (float)  $data['budget_min']       : null,
            customerName:         isset($data['customer_name'])    ? (string) $data['customer_name']    : null,
            urgency:              isset($data['urgency'])          ? (string) $data['urgency']          : null,
            focusedPropertyId:    isset($data['focused_property_id']) ? (int) $data['focused_property_id'] : null,
            tone:                 isset($data['tone'])             ? (string) $data['tone']             : null,
            isFirstContact:       (bool) ($data['is_first_contact']       ?? true),
            disclosedAsAssistant: (bool) ($data['disclosed_as_assistant'] ?? false),
            weakSearchTurns:      (int)  ($data['weak_search_turns']      ?? 0),
            askedSlots:           (array) ($data['asked_slots']           ?? []),
        );
    }

    public function hasSearchCriteria(): bool
    {
        return $this->city !== null
            || $this->district !== null
            || $this->propertyType !== null
            || $this->budgetMax !== null;
    }
}
