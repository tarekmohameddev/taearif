<?php

declare(strict_types=1);

namespace App\Domain\RealEstateAgent\Safety;

use NumberFormatter;

/**
 * Substitutes citation placeholders in the model's `say` field with actual
 * database values from the FactLedger.
 *
 * Supported placeholder formats:
 *   {{p:ID|field}}  — property field (title, price, area, city, address, bedrooms, purpose)
 *   {{k:chunk_id}}  — knowledge chunk inline marker (removed; text already in context)
 *
 * This is the ONLY place customer-facing numbers are formatted.
 * The model never types a number; it types a placeholder.
 */
final class ReplyRenderer
{
    /** Format SAR amounts with Arabic-style comma thousands separators */
    private function formatSar(float $amount): string
    {
        return number_format($amount, 0, '.', ',') . ' ريال';
    }

    /** Format area with unit */
    private function formatArea(mixed $sqm): string
    {
        if (!is_numeric($sqm) || (float) $sqm <= 0) {
            return '';
        }
        return number_format((float) $sqm, 0, '.', ',') . ' م²';
    }

    /**
     * Render the reply by substituting all placeholders.
     *
     * Location-relax disclosure is prepended ONLY when the model's reply actually
     * references properties via placeholders (i.e., at least one {{p:ID|field}} was
     * successfully rendered). If the model asked a clarifying question instead of
     * listing properties, no prefix is added — appending "nearby results:" before
     * a question produces a contradictory, confusing reply.
     */
    public function render(string $say, FactLedger $ledger): string
    {
        $propertiesRendered = false;

        // Property placeholders
        $rendered = preg_replace_callback(
            '/\{\{p:(\d+)\|([^}]+)\}\}/',
            function (array $m) use ($ledger, &$propertiesRendered): string {
                $id    = (int) $m[1];
                $field = (string) $m[2];
                $row   = $ledger->getProperty($id);
                if ($row === null) {
                    return "[عقار #{$id}]";
                }
                $propertiesRendered = true;
                return $this->renderPropertyField($row, $field, $id);
            },
            $say
        ) ?? $say;

        // Knowledge chunk markers — strip (chunk text is already in context)
        $rendered = preg_replace('/\{\{k:[^}]+\}\}/', '', $rendered) ?? $rendered;

        // Final safety sweep: if ANY {{...}} pattern survived to this point,
        // strip it rather than sending raw template syntax to the customer.
        if (str_contains($rendered, '{{')) {
            $rendered = preg_replace('/\{\{[^}]*\}\}/', '', $rendered) ?? $rendered;
            $rendered = trim((string) preg_replace('/\s{2,}/u', ' ', $rendered));
        }

        // Location-relax disclosure — only when the reply actually lists properties.
        // Without this guard, the prefix "ما لقيت في X، لكن هذي نتائج قريبة" gets
        // prepended to questions/follow-ups that have nothing to do with nearby results.
        if ($ledger->locationRelaxed() && $propertiesRendered) {
            $location = $ledger->requestedLocation() ?? '';
            if ($location !== '') {
                $rendered = "ما لقيت في {$location} حالياً، لكن هذي نتائج قريبة قد تناسبك:\n\n" . $rendered;
            }
        }

        return trim($rendered);
    }

    /** @param array<string, mixed> $row */
    private function renderPropertyField(array $row, string $field, int $id): string
    {
        return match ($field) {
            'title'    => (string) ($row['title'] ?? "عقار #{$id}"),
            'price'    => $this->formatSar((float) ($row['price'] ?? 0)),
            'area'     => $this->formatArea($row['area_sqm'] ?? null),
            'city'     => (string) ($row['city'] ?? $row['address'] ?? ''),
            'address'  => (string) ($row['address'] ?? ''),
            'bedrooms' => isset($row['bedrooms']) && (int) $row['bedrooms'] > 0
                ? (string) $row['bedrooms'] . ' غرف'
                : '',
            'purpose'  => match ($row['purpose'] ?? '') {
                'rent'  => 'إيجار',
                'sale'  => 'بيع',
                default => '',
            },
            'type'     => (string) ($row['property_type'] ?? ''),
            default    => (string) ($row[$field] ?? "#{$id}"),
        };
    }
}
