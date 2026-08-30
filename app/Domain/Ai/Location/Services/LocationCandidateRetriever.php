<?php

declare(strict_types=1);

namespace App\Domain\Ai\Location\Services;

use App\Domain\Ai\Location\Contracts\LocationCandidateRetrieval;
use App\Domain\Ai\Location\DTOs\LocationCandidate;
use App\Domain\Ai\Location\Support\LocationTextNormalizer;
use App\Models\AiAlias;
use App\Models\User\Region;
use App\Models\User\UserCity;
use App\Models\User\UserDistrict;
use Illuminate\Support\Facades\Cache;

final class LocationCandidateRetriever implements LocationCandidateRetrieval
{
    private const CACHE_TTL = 3600;

    /**
     * @return array{normalized: string, has_district_marker: bool, candidates: LocationCandidate[]}
     */
    public function retrieve(string $rawLocationText): array
    {
        $normalized = LocationTextNormalizer::normalize($rawLocationText);
        if ($normalized === null) {
            return ['normalized' => '', 'has_district_marker' => false, 'candidates' => []];
        }

        $hasDistrictMarker = LocationTextNormalizer::hasDistrictMarker($rawLocationText);

        $aliases = $this->aliasesIndex();
        $cities = $this->citiesIndex();
        $districts = $this->districtsIndex();
        $regions = $this->regionsIndex();

        $queryTokens = array_values(array_filter(preg_split('/\s+/u', $normalized) ?: [], static fn (string $t) => mb_strlen($t) >= 3));

        $candidates = [];

        // Cities
        foreach ($cities as $c) {
            $best = $this->scoreCandidate($normalized, $queryTokens, $c['norms'], $aliases['city'] ?? []);
            if ($best !== null) {
                $candidates[] = new LocationCandidate(
                    type: 'city',
                    id: (int) $c['id'],
                    nameAr: (string) $c['name_ar'],
                    nameEn: $c['name_en'] !== '' ? (string) $c['name_en'] : null,
                    score: $best['score'],
                    reason: $best['reason'],
                );
            }
        }

        // Districts
        foreach ($districts as $d) {
            // For districts, require at least a token/contains relationship to avoid full-table fuzzy cost.
            if (! $this->passesQuickDistrictFilter($normalized, $queryTokens, $d['norms'])) {
                continue;
            }

            $best = $this->scoreCandidate($normalized, $queryTokens, $d['norms'], $aliases['district'] ?? []);
            if ($best !== null) {
                $score = $best['score'];
                $reason = $best['reason'];
                // If the user used حي marker, districts should be prioritized.
                if ($hasDistrictMarker) {
                    $score += 4.0;
                    $reason = $reason !== '' ? ($reason . '+district_marker') : 'district_marker';
                }

                $candidates[] = new LocationCandidate(
                    type: 'district',
                    id: (int) $d['id'],
                    nameAr: (string) $d['name_ar'],
                    nameEn: $d['name_en'] !== '' ? (string) $d['name_en'] : null,
                    cityId: (int) $d['city_id'],
                    cityNameAr: (string) $d['city_name_ar'],
                    score: $score,
                    reason: $reason,
                );
            }
        }

        // Regions
        foreach ($regions as $r) {
            $best = $this->scoreCandidate($normalized, $queryTokens, $r['norms'], $aliases['region'] ?? []);
            if ($best !== null) {
                $candidates[] = new LocationCandidate(
                    type: 'region',
                    id: (int) $r['id'],
                    nameAr: (string) $r['name_ar'],
                    nameEn: $r['name_en'] !== '' ? (string) $r['name_en'] : null,
                    score: $best['score'],
                    reason: $best['reason'],
                );
            }
        }

        usort($candidates, static fn (LocationCandidate $a, LocationCandidate $b) => $b->score <=> $a->score);
        $candidates = array_slice($candidates, 0, 24);

        return [
            'normalized' => $normalized,
            'has_district_marker' => $hasDistrictMarker,
            'candidates' => $candidates,
        ];
    }

    /**
     * @param  string[]  $queryTokens
     * @param  string[]  $norms
     * @param  array<string, string>  $aliasesMap normalized_alias => canonical
     * @return array{score: float, reason: string}|null
     */
    private function scoreCandidate(string $normalizedQuery, array $queryTokens, array $norms, array $aliasesMap): ?array
    {
        $bestScore = 0.0;
        $bestReason = '';

        // Alias match (only if this candidate matches the canonical target)
        if (isset($aliasesMap[$normalizedQuery])) {
            $canonical = (string) $aliasesMap[$normalizedQuery];
            $canonicalNorm = LocationTextNormalizer::normalize($canonical);
            if ($canonicalNorm !== null && in_array($canonicalNorm, $norms, true)) {
                $bestScore = 98.0;
                $bestReason = 'alias';
            }
        }

        foreach ($norms as $norm) {
            if ($norm === '') {
                continue;
            }

            if ($norm === $normalizedQuery) {
                return ['score' => 100.0, 'reason' => 'exact'];
            }

            if (mb_strlen($norm) >= 3 && (mb_strpos($normalizedQuery, $norm) !== false || mb_strpos($norm, $normalizedQuery) !== false)) {
                $score = 92.0;
                if ($score > $bestScore) {
                    $bestScore = $score;
                    $bestReason = 'contains';
                }
                continue;
            }

            // Fuzzy only when we have token overlap (keeps district scan cheap)
            if (! $this->hasTokenOverlap($queryTokens, $norm)) {
                continue;
            }

            $score = $this->similarity($normalizedQuery, $norm);
            if ($score > $bestScore) {
                $bestScore = $score;
                $bestReason = 'fuzzy';
            }
        }

        if ($bestScore < 65.0) {
            return null;
        }

        return ['score' => $bestScore, 'reason' => $bestReason];
    }

    /**
     * @param string[] $queryTokens
     */
    private function hasTokenOverlap(array $queryTokens, string $candidateNorm): bool
    {
        foreach ($queryTokens as $t) {
            if ($t !== '' && mb_strpos($candidateNorm, $t) !== false) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param string[] $queryTokens
     * @param string[] $districtNorms
     */
    private function passesQuickDistrictFilter(string $normalizedQuery, array $queryTokens, array $districtNorms): bool
    {
        foreach ($districtNorms as $norm) {
            if ($norm === '') {
                continue;
            }
            if (mb_strlen($norm) >= 3 && (mb_strpos($normalizedQuery, $norm) !== false || mb_strpos($norm, $normalizedQuery) !== false)) {
                return true;
            }
            if ($this->hasTokenOverlap($queryTokens, $norm)) {
                return true;
            }
        }
        return false;
    }

    private function similarity(string $a, string $b): float
    {
        if ($a === $b) {
            return 100.0;
        }

        $maxLen = max(mb_strlen($a), mb_strlen($b));
        if ($maxLen === 0) {
            return 0.0;
        }

        return (1.0 - ($this->mbLevenshtein($a, $b) / $maxLen)) * 100.0;
    }

    private function mbLevenshtein(string $a, string $b): int
    {
        $aChars = preg_split('//u', $a, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $bChars = preg_split('//u', $b, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $aLen = count($aChars);
        $bLen = count($bChars);

        if ($aLen === 0) {
            return $bLen;
        }
        if ($bLen === 0) {
            return $aLen;
        }

        $prev = range(0, $bLen);
        for ($i = 1; $i <= $aLen; $i++) {
            $curr = [$i];
            for ($j = 1; $j <= $bLen; $j++) {
                $cost = ($aChars[$i - 1] === $bChars[$j - 1]) ? 0 : 1;
                $curr[$j] = min($prev[$j] + 1, $curr[$j - 1] + 1, $prev[$j - 1] + $cost);
            }
            $prev = $curr;
        }

        return (int) $prev[$bLen];
    }

    /**
     * @return array<int, array{id:int, name_ar:string, name_en:string, norms:string[]}>
     */
    private function citiesIndex(): array
    {
        return Cache::remember('ai.location_rag.cities.v1', self::CACHE_TTL, function () {
            return UserCity::query()
                ->get(['id', 'name_ar', 'name_en'])
                ->map(function (UserCity $c): array {
                    $nameAr = (string) ($c->name_ar ?? '');
                    $nameEn = (string) ($c->name_en ?? '');
                    return [
                        'id' => (int) $c->id,
                        'name_ar' => $nameAr,
                        'name_en' => $nameEn,
                        'norms' => array_values(array_unique(array_filter([
                            LocationTextNormalizer::normalize($nameAr),
                            LocationTextNormalizer::normalize($nameEn),
                        ]))),
                    ];
                })
                ->all();
        });
    }

    /**
     * @return array<int, array{id:int, city_id:int, city_name_ar:string, name_ar:string, name_en:string, norms:string[]}>
     */
    private function districtsIndex(): array
    {
        return Cache::remember('ai.location_rag.districts.v1', self::CACHE_TTL, function () {
            return UserDistrict::query()
                ->get(['id', 'city_id', 'city_name_ar', 'name_ar', 'name_en'])
                ->map(function (UserDistrict $d): array {
                    $nameAr = (string) ($d->name_ar ?? '');
                    $nameEn = (string) ($d->name_en ?? '');
                    return [
                        'id' => (int) $d->id,
                        'city_id' => (int) $d->city_id,
                        'city_name_ar' => (string) ($d->city_name_ar ?? ''),
                        'name_ar' => $nameAr,
                        'name_en' => $nameEn,
                        'norms' => array_values(array_unique(array_filter([
                            LocationTextNormalizer::normalize($nameAr),
                            LocationTextNormalizer::normalize($nameEn),
                        ]))),
                    ];
                })
                ->all();
        });
    }

    /**
     * @return array<int, array{id:int, name_ar:string, name_en:string, norms:string[]}>
     */
    private function regionsIndex(): array
    {
        return Cache::remember('ai.location_rag.regions.v1', self::CACHE_TTL, function () {
            return Region::query()
                ->get(['id', 'name_ar', 'name_en'])
                ->map(function (Region $r): array {
                    $nameAr = (string) ($r->name_ar ?? '');
                    $nameEn = (string) ($r->name_en ?? '');
                    return [
                        'id' => (int) $r->id,
                        'name_ar' => $nameAr,
                        'name_en' => $nameEn,
                        'norms' => array_values(array_unique(array_filter([
                            LocationTextNormalizer::normalize($nameAr),
                            LocationTextNormalizer::normalize($nameEn),
                        ]))),
                    ];
                })
                ->all();
        });
    }

    /**
     * @return array{city: array<string,string>, district: array<string,string>, region: array<string,string>}
     */
    private function aliasesIndex(): array
    {
        return Cache::remember('ai.location_rag.aliases.v1', self::CACHE_TTL, function () {
            $rows = AiAlias::query()
                ->whereIn('alias_type', ['city', 'district', 'region'])
                ->orderByDesc('occurrence_count')
                ->get(['alias_type', 'alias', 'canonical']);

            $out = ['city' => [], 'district' => [], 'region' => []];
            foreach ($rows as $row) {
                $type = (string) ($row->alias_type ?? '');
                if (! isset($out[$type])) {
                    continue;
                }
                $alias = LocationTextNormalizer::normalize((string) ($row->alias ?? ''));
                $canonical = trim((string) ($row->canonical ?? ''));
                if ($alias !== null && $canonical !== '') {
                    $out[$type][$alias] = $canonical;
                }
            }
            return $out;
        });
    }
}

