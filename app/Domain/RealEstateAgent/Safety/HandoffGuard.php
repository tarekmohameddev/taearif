<?php

declare(strict_types=1);

namespace App\Domain\RealEstateAgent\Safety;

use App\Domain\Communication\WhatsApp\Bot\ComplianceService;
use App\Domain\RealEstateAgent\State\CustomerBrief;

/**
 * Verifies that an escalation request from the model tool has supporting evidence
 * before it is accepted.
 *
 * Without this guard, the model escalates to a human on mundane buyer messages:
 *  - Portal lead templates ("أرغب في التواصل مع المعلن") → interpreted as human request
 *  - Vague questions with low model confidence → escalated instead of searching
 *  - Payment / instalment questions → escalated instead of checking knowledge base
 *
 * Evidence rules per reason:
 *  customer_request    → ComplianceService::isHumanRequestKeyword must be true.
 *                        "أرغب في التواصل مع المعلن/المالك" is explicitly excluded.
 *  model_needs_human   → no tool produced a usable answer AND no search was run.
 *  cannot_answer       → same as model_needs_human.
 *  low_confidence      → same as model_needs_human.
 *  repeated_empty_search → brief->weakSearchTurns >= 3 (already tracked).
 *  regulated_topic     → ComplianceService must also flag the text (pre-loop check agrees).
 *  (anything else)     → allowed — tool-triggered reasons like propose_viewing are fine.
 */
final class HandoffGuard
{
    // Phrases from portal lead templates that look like human requests but are NOT
    private const SELLER_CONTACT_PHRASES = [
        'أرغب في التواصل مع المعلن',
        'في التواصل مع المعلن',
        'التواصل مع المالك',
        'التواصل مع المسوق',
        'التواصل مع صاحب',
    ];

    public function isEvidenced(
        string            $escalationReason,
        string            $inboundText,
        FactLedger        $ledger,
        array             $agentReply,
        CustomerBrief     $brief,
        ComplianceService $compliance,
    ): bool {
        // Portal-lead phrasing is never a human-request escalation
        if ($this->isBuyerContactPhrase($inboundText)) {
            return false;
        }

        return match (true) {
            $escalationReason === 'customer_request' =>
                $compliance->isHumanRequestKeyword($inboundText),

            str_starts_with($escalationReason, 'regulated_topic') =>
                $this->hasRegulatoryEvidence($inboundText),

            $escalationReason === 'repeated_empty_search' =>
                $brief->weakSearchTurns >= 3,

            in_array($escalationReason, ['model_needs_human', 'cannot_answer', 'low_confidence'], true) =>
                $ledger->propertyCount() === 0 && !$ledger->searchWasRun(),

            default => true,
        };
    }

    private function isBuyerContactPhrase(string $text): bool
    {
        foreach (self::SELLER_CONTACT_PHRASES as $phrase) {
            if (str_contains($text, $phrase)) {
                return true;
            }
        }
        return false;
    }

    private function hasRegulatoryEvidence(string $text): bool
    {
        // Short regulated tokens like صك are only genuine advice requests when
        // accompanied by a question word or advice verb (RC5 fix).
        // If it reaches HandoffGuard via tool, apply the same narrow check.
        $advicePattern = '/[؟?]|ينفع|يصير|أقدر|كيف|هل|استفسار|نصيحة|ممكن|ما\s+هو|ماهو/u';
        return (bool) preg_match($advicePattern, $text);
    }
}
