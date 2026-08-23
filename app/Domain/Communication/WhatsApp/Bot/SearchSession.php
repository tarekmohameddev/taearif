<?php

declare(strict_types=1);

namespace App\Domain\Communication\WhatsApp\Bot;

/**
 * Sticky search-session policy.
 *
 * Once the customer has given inventory criteria (budget / type / location / focus),
 * short follow-ups like "الرياض" or "ارسلي التفاصيل" must stay in property_search
 * and keep running PropertySearchTool — Pass-1 "general" classifications are ignored.
 */
final class SearchSession
{
    /**
     * @param  array<string, mixed>  $facts
     */
    public static function isActive(array $facts): bool
    {
        if (! empty($facts['_search_active'])) {
            return true;
        }

        return isset($facts['budget_max'])
            || isset($facts['budget_min'])
            || isset($facts['type'])
            || isset($facts['city'])
            || isset($facts['district'])
            || ! empty($facts['focused_property_id']);
    }

    /**
     * @param  array<string, mixed>  $facts
     * @return array<string, mixed>
     */
    public static function markActive(array $facts): array
    {
        $facts['_search_active'] = true;

        return $facts;
    }

    /**
     * Whether this turn should force property_search and run the search tool.
     *
     * @param  array<string, mixed>  $facts
     */
    public static function shouldContinueSearch(array $facts, string $inboundText, bool $isGreeting): bool
    {
        if (! self::isActive($facts)) {
            return false;
        }

        // Pure greeting mid-session stays conversational; everything else continues search.
        return ! $isGreeting;
    }
}
