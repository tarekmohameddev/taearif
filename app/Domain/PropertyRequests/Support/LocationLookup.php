<?php

declare(strict_types=1);

namespace App\Domain\PropertyRequests\Support;

use App\Models\User\UserCity;
use App\Models\User\UserDistrict;
use Illuminate\Support\Facades\Cache;

class LocationLookup
{
    private const CITY_CACHE_KEY = 'location_lookup_cities_v1';
    private const DISTRICT_CACHE_KEY = 'location_lookup_districts_v1';
    private const CACHE_TTL = 3600;

    private const FUZZY_THRESHOLD = 82.0;
    private const FUZZY_MIN_LEAD = 3.0;

    public function resolveCityIdByName(?string $name): ?int
    {
        $normalized = $this->normalizeName($name);
        if ($normalized === null) {
            return null;
        }

        return $this->resolve($normalized, $this->cityIndex());
    }

    public function resolveDistrictIdByName(?string $name, ?int $cityId = null): ?int
    {
        $normalized = $this->normalizeName($name);
        if ($normalized === null) {
            return null;
        }

        $districts = $this->districtIndex();
        if ($cityId !== null && $cityId > 0) {
            $scoped = array_values(array_filter(
                $districts,
                static fn ($d) => $d['city_id'] === $cityId
            ));
            if (!empty($scoped)) {
                $districts = $scoped;
            }
        }

        return $this->resolve($normalized, $districts);
    }

    public function normalizeName(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $text = trim($value);
        if ($text === '') {
            return null;
        }

        $text = mb_strtolower($text, 'UTF-8');
        $text = preg_replace('/[\x{0610}-\x{061A}\x{064B}-\x{065F}\x{0670}\x{06D6}-\x{06ED}\x{0640}]/u', '', $text);
        $text = preg_replace('/[\x{0622}\x{0623}\x{0625}\x{0671}]/u', 'ا', $text);
        $text = str_replace(['ى', 'ة'], ['ي', 'ه'], $text);
        $text = str_replace(['ؤ', 'ئ', 'ء'], ['و', 'ي', ''], $text);
        $text = preg_replace('/^(ال|حي\s+|مدينه\s+|منطقه\s+|محافظه\s+)/u', '', $text);
        $text = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $text);
        $text = preg_replace('/\s+/u', ' ', $text);
        $text = trim($text);

        return $text === '' ? null : $text;
    }

    /**
     * @param array<int, array{id:int, norms:array<int,string>}> $candidates
     */
    private function resolve(string $normalized, array $candidates): ?int
    {
        $exact = [];
        foreach ($candidates as $c) {
            if (in_array($normalized, $c['norms'], true)) {
                $exact[$c['id']] = true;
            }
        }
        $exact = array_keys($exact);
        if (count($exact) === 1) {
            return $exact[0];
        }
        if (count($exact) > 1) {
            return null;
        }

        $contains = [];
        foreach ($candidates as $c) {
            foreach ($c['norms'] as $norm) {
                if ($norm !== '' && mb_strlen($norm) >= 3
                    && (mb_strpos($normalized, $norm) !== false || mb_strpos($norm, $normalized) !== false)) {
                    $contains[$c['id']] = true;
                }
            }
        }
        $contains = array_keys($contains);
        if (count($contains) === 1) {
            return $contains[0];
        }

        return $this->bestFuzzyMatch($normalized, $candidates);
    }

    /**
     * @param array<int, array{id:int, norms:array<int,string>}> $candidates
     */
    private function bestFuzzyMatch(string $normalized, array $candidates): ?int
    {
        $bestId = null;
        $bestScore = 0.0;
        $secondScore = 0.0;

        foreach ($candidates as $c) {
            foreach ($c['norms'] as $norm) {
                if ($norm === '') {
                    continue;
                }
                $score = $this->similarity($normalized, $norm);
                if ($score > $bestScore) {
                    $secondScore = $bestScore;
                    $bestScore = $score;
                    $bestId = $c['id'];
                } elseif ($score > $secondScore) {
                    $secondScore = $score;
                }
            }
        }

        if ($bestScore >= self::FUZZY_THRESHOLD && ($bestScore - $secondScore) >= self::FUZZY_MIN_LEAD) {
            return $bestId;
        }

        return null;
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

        return $prev[$bLen];
    }

    /**
     * @return array<int, array{id:int, norms:array<int,string>}>
     */
    private function cityIndex(): array
    {
        return Cache::remember(self::CITY_CACHE_KEY, self::CACHE_TTL, function () {
            return UserCity::query()
                ->get(['id', 'name_ar', 'name_en'])
                ->map(fn ($c) => [
                    'id' => (int) $c->id,
                    'norms' => array_values(array_unique(array_filter([
                        $this->normalizeName($c->name_ar),
                        $this->normalizeName($c->name_en),
                    ]))),
                ])
                ->all();
        });
    }

    /**
     * @return array<int, array{id:int, city_id:int, norms:array<int,string>}>
     */
    private function districtIndex(): array
    {
        return Cache::remember(self::DISTRICT_CACHE_KEY, self::CACHE_TTL, function () {
            return UserDistrict::query()
                ->get(['id', 'city_id', 'name_ar', 'name_en'])
                ->map(fn ($d) => [
                    'id' => (int) $d->id,
                    'city_id' => (int) $d->city_id,
                    'norms' => array_values(array_unique(array_filter([
                        $this->normalizeName($d->name_ar),
                        $this->normalizeName($d->name_en),
                    ]))),
                ])
                ->all();
        });
    }
}
