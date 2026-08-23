<?php

declare(strict_types=1);

namespace App\Domain\RealEstateAgent\Safety;

/**
 * Detects when the model answered an inventory question without running a search,
 * and signals that a forced search step is needed.
 *
 * RC4 fix: 4 conversations answered buyer inquiries with neither placeholders
 * nor an honest "no results" because search_inventory is optional every step.
 * This policy makes it mandatory when the intent is clearly inventory-related.
 */
final class GroundingPolicy
{
    private const INVENTORY_TRIGGERS = [
        // Arabic inventory question patterns
        'عقارات', 'شقق', 'فلل', 'فيلا', 'شقة', 'أراضي', 'أرض', 'مكاتب', 'مكتب',
        'عمائر', 'عمارة', 'دوبلكس', 'استراحة', 'شاليه',
        // Purpose keywords
        'للإيجار', 'للبيع', 'للاستئجار', 'بالإيجار', 'بالبيع',
        'إيجار', 'بيع', 'اشترى', 'استأجر',
        // Price questions
        'بكم', 'السعر', 'الأسعار', 'الإيجار', 'الثمن', 'التكلفة',
        // Search intent
        'ابحث', 'أبحث', 'ابغى', 'أبغى', 'أريد', 'اريد', 'عندكم', 'عندك',
        'هل يوجد', 'هل عندكم', 'هل لديكم', 'هل فيه',
        // Location-based
        'في الرياض', 'في جدة', 'في الدمام', 'في مكة', 'في المدينة',
        'بالرياض', 'بجدة', 'بالدمام',
        // Viewing
        'معاينة', 'مشاهدة', 'زيارة', 'أشوف', 'اشوف',
    ];

    /**
     * Return true when the inbound message looks like an inventory question
     * but the agent replied without running search_inventory.
     */
    public function needsForcedSearch(string $inboundText, FactLedger $ledger, array $agentReply): bool
    {
        if ($ledger->searchWasRun()) {
            return false;
        }

        // If there are already properties in the ledger (from portal-lead seeding),
        // no forced search needed — the model can cite from the ledger.
        if ($ledger->propertyCount() > 0) {
            return false;
        }

        // Check intent from brief_updates
        $intent = (string) ($agentReply['brief_updates']['intent'] ?? '');
        if (in_array($intent, ['search', 'viewing', 'buy', 'rent'], true)) {
            return true;
        }

        // Check the inbound text for inventory patterns
        return $this->isInventoryQuestion($inboundText);
    }

    private function isInventoryQuestion(string $text): bool
    {
        $lower = mb_strtolower($text);
        foreach (self::INVENTORY_TRIGGERS as $trigger) {
            if (str_contains($lower, mb_strtolower($trigger))) {
                return true;
            }
        }
        return false;
    }
}
