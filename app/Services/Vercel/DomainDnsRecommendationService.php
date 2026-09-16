<?php

namespace App\Services\Vercel;

final class DomainDnsRecommendationService
{
    /**
     * Preserve Vercel's ranked recommendation groups.
     *
     * A group can contain more than one required value (for example, two apex
     * A records at rank 1). Plain legacy lists are treated as independent
     * alternatives because their original grouping information is unavailable.
     *
     * @return list<array{rank: int, values: list<string>}>
     */
    public static function normalizeGroups(mixed $ranked, mixed $legacy = []): array
    {
        $items = is_array($ranked) ? $ranked : [$ranked];
        $groups = [];
        $nextRank = 1;

        foreach ($items as $item) {
            if (is_array($item) && (array_key_exists('values', $item) || array_key_exists('value', $item))) {
                $values = self::normalizeValues($item['values'] ?? $item['value']);
                if ($values === []) {
                    continue;
                }

                $rank = filter_var($item['rank'] ?? null, FILTER_VALIDATE_INT);
                $groups[] = [
                    'rank' => $rank !== false && $rank !== null && $rank > 0 ? (int) $rank : $nextRank,
                    'values' => $values,
                ];
                $nextRank = max($nextRank + 1, ((int) ($groups[array_key_last($groups)]['rank'] ?? 0)) + 1);

                continue;
            }

            // A plain list has lost provider grouping. Treat each value as an
            // accepted alternative instead of incorrectly requiring all values.
            foreach (self::normalizeValues($item) as $value) {
                $groups[] = ['rank' => $nextRank++, 'values' => [$value]];
            }
        }

        if ($groups === [] && $legacy !== $ranked) {
            return self::normalizeGroups($legacy);
        }

        usort($groups, static fn (array $left, array $right): int => $left['rank'] <=> $right['rank']);

        $unique = [];
        foreach ($groups as $group) {
            $key = implode('|', $group['values']);
            if (! isset($unique[$key])) {
                $unique[$key] = $group;
            }
        }

        return array_values($unique);
    }

    /**
     * @param  list<array{rank: int, values: list<string>}>  $groups
     * @return list<array{rank: int, values: list<string>}>
     */
    public static function withFallback(array $groups, mixed $fallback): array
    {
        $values = self::normalizeValues($fallback);
        if ($values === []) {
            return $groups;
        }

        $known = self::flatten($groups);
        foreach ($values as $value) {
            if (in_array($value, $known, true)) {
                continue;
            }

            $groups[] = [
                'rank' => $groups === [] ? 1 : max(array_column($groups, 'rank')) + 1,
                'values' => [$value],
            ];
            $known[] = $value;
        }

        return $groups;
    }

    /**
     * @param  list<array{rank: int, values: list<string>}>  $groups
     * @return list<string>
     */
    public static function flatten(array $groups): array
    {
        $values = [];
        foreach ($groups as $group) {
            foreach (self::normalizeValues($group['values'] ?? []) as $value) {
                $values[] = $value;
            }
        }

        return array_values(array_unique($values));
    }

    /** @return list<string> */
    private static function normalizeValues(mixed $values): array
    {
        if (! is_array($values)) {
            $values = [$values];
        }

        $normalized = [];
        foreach ($values as $value) {
            if (is_array($value)) {
                foreach (self::normalizeValues($value) as $nested) {
                    $normalized[] = $nested;
                }

                continue;
            }

            if (is_string($value) && trim($value) !== '') {
                $normalized[] = strtolower(rtrim(trim($value), '.'));
            }
        }

        return array_values(array_unique($normalized));
    }
}
