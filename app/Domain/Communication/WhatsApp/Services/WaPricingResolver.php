<?php

declare(strict_types=1);

namespace App\Domain\Communication\WhatsApp\Services;

use App\Domain\Communication\Exceptions\ChannelPricingNotConfiguredException;
use App\Models\Api\marketing\MarketingChannelPricing;

/**
 * Resolves credit costs for the WhatsApp channel based on message category.
 *
 * Resolution order:
 *   1. Exact (channel_type, message_category) row
 *   2. (channel_type, 'default') fallback row
 *   3. ChannelPricingNotConfiguredException
 *
 * Returns 0 for any category whose pricing row has is_billable = false.
 */
final class WaPricingResolver
{
    private const CHANNEL = 'whatsapp';

    /**
     * Resolve credits for a WaTemplate category string coming from Meta's API
     * (e.g. 'MARKETING', 'UTILITY', 'AUTHENTICATION').
     *
     * Null or unrecognised values fall back to the 'marketing' category,
     * preserving current behaviour for free-text campaigns that have no template.
     */
    public function creditsForTemplateCategory(?string $metaCategory): int
    {
        $category = $this->normaliseMeta($metaCategory);

        $pricing = MarketingChannelPricing::resolveFor(self::CHANNEL, $category);

        if ($pricing === null) {
            throw new ChannelPricingNotConfiguredException(self::CHANNEL);
        }

        return $pricing->is_billable ? $pricing->credits_per_message : 0;
    }

    /**
     * Resolve credits for one AI Bot reply turn.
     * Returns 0 when the ai_bot category row is not billable.
     */
    public function creditsForAiReply(): int
    {
        $pricing = MarketingChannelPricing::resolveFor(self::CHANNEL, MarketingChannelPricing::CATEGORY_AI_BOT);

        if ($pricing === null) {
            throw new ChannelPricingNotConfiguredException(self::CHANNEL . ':ai_bot');
        }

        return $pricing->is_billable ? $pricing->credits_per_message : 0;
    }

    /**
     * Whether AI Bot replies should consume credits at all.
     */
    public function isAiBotBillable(): bool
    {
        $pricing = MarketingChannelPricing::resolveFor(self::CHANNEL, MarketingChannelPricing::CATEGORY_AI_BOT);

        return $pricing !== null && $pricing->is_billable;
    }

    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Map a Meta template category string to our internal category key.
     * 'MARKETING' → 'marketing', 'UTILITY' → 'utility', etc.
     * Unknown / null → 'marketing' (most expensive, safe default).
     */
    private function normaliseMeta(?string $metaCategory): string
    {
        if ($metaCategory === null) {
            return MarketingChannelPricing::CATEGORY_MARKETING;
        }

        $map = [
            'MARKETING'      => MarketingChannelPricing::CATEGORY_MARKETING,
            'UTILITY'        => MarketingChannelPricing::CATEGORY_UTILITY,
            'AUTHENTICATION' => MarketingChannelPricing::CATEGORY_AUTHENTICATION,
            'SERVICE'        => MarketingChannelPricing::CATEGORY_SERVICE,
        ];

        return $map[strtoupper($metaCategory)] ?? MarketingChannelPricing::CATEGORY_MARKETING;
    }
}
