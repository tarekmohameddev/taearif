<?php

declare(strict_types=1);

namespace App\Domain\Communication\WhatsApp\Bot;

use App\Domain\Ai\Knowledge\ArabicNormalizer;
use App\Models\PropertyExternalLink;
use App\Models\User\RealestateManagement\Property;
use Illuminate\Support\Facades\Cache;

/**
 * Resolves external listing links and per-property FAQs for a property.
 *
 * Used by ContextBuilder to enrich bot context when the customer's query is
 * focused on a specific property.
 */
final class ListingLinkResolver
{
    private const LINKS_CACHE_TTL = 600;  // 10 minutes
    private const FAQS_CACHE_TTL  = 600;

    /**
     * Return active external links for a property.
     *
     * @return array{platform: string, url: string, label: string|null}[]
     */
    public function getExternalLinks(int $propertyId): array
    {
        $cacheKey = 'listing.links.' . $propertyId;
        return Cache::remember($cacheKey, self::LINKS_CACHE_TTL, function () use ($propertyId) {
            return PropertyExternalLink::where('property_id', $propertyId)
                ->where('active', true)
                ->orderBy('platform')
                ->get(['platform', 'url', 'label'])
                ->map(fn ($row) => [
                    'platform' => $row->platform,
                    'url'      => $row->url,
                    'label'    => $row->label,
                ])
                ->all();
        });
    }

    /**
     * Find the best-matching FAQ answer for a query from a property's FAQ list.
     *
     * @param  array{question: string, answer: string}[]  $faqs
     */
    public function matchFaq(array $faqs, string $query): ?string
    {
        if (empty($faqs) || trim($query) === '') {
            return null;
        }

        $normalizedQuery = ArabicNormalizer::normalizeForSearch($query);
        $bestScore       = 0;
        $bestAnswer      = null;

        foreach ($faqs as $faq) {
            $q = ArabicNormalizer::normalizeForSearch((string) ($faq['question'] ?? ''));
            if ($q === '') {
                continue;
            }
            // Token overlap score
            $qTokens     = array_filter(explode(' ', $q));
            $queryTokens = array_filter(explode(' ', $normalizedQuery));
            if (empty($queryTokens)) {
                continue;
            }
            $overlap = count(array_intersect($qTokens, $queryTokens));
            $score   = $overlap / max(count($queryTokens), 1);

            if ($score > $bestScore) {
                $bestScore  = $score;
                $bestAnswer = $faq['answer'] ?? null;
            }
        }

        // Require at least 40% token overlap to consider a match
        return $bestScore >= 0.4 ? $bestAnswer : null;
    }

    /**
     * Format external links as a concise Arabic text for injection into the bot reply.
     *
     * @param  array{platform: string, url: string, label: string|null}[]  $links
     */
    public function formatLinksText(array $links, string $propertyTitle = ''): string
    {
        if (empty($links)) {
            return '';
        }

        $platformNames = [
            'aqar'             => 'عقار',
            'bayut'            => 'بيوت',
            'property_finder'  => 'بروبرتي فايندر',
            'opensooq'         => 'أوبن سوق',
            'wasalt'           => 'وصلت',
            'custom'           => 'موقع خارجي',
        ];

        $title   = $propertyTitle !== '' ? "للعقار \"{$propertyTitle}\"" : 'للعقار';
        $lines   = ["روابط {$title} على المنصات:"];
        foreach ($links as $link) {
            $name   = $platformNames[$link['platform']] ?? ucfirst($link['platform']);
            $label  = $link['label'] ?? $name;
            $lines[] = "• {$label}: {$link['url']}";
        }

        return implode("\n", $lines);
    }

    /**
     * Resolve external links and FAQ for a specific property and query.
     * Returns enrichment data ready to be merged into BotContext.
     *
     * @return array{external_links: array, faq_answer: string|null, links_text: string}
     */
    public function resolve(int $propertyId, string $query, string $propertyTitle = ''): array
    {
        $property = Property::with(['externalLinks' => fn ($q) => $q->where('active', true)])
            ->find($propertyId);

        if ($property === null) {
            return ['external_links' => [], 'faq_answer' => null, 'links_text' => ''];
        }

        $links    = $this->getExternalLinks($propertyId);
        $faqs     = is_array($property->faqs) ? $property->faqs : [];
        $faqMatch = $this->matchFaq($faqs, $query);

        return [
            'external_links' => $links,
            'faq_answer'     => $faqMatch,
            'links_text'     => $this->formatLinksText($links, $propertyTitle),
        ];
    }
}
