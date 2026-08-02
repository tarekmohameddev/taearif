<?php

declare(strict_types=1);

namespace App\Domain\Ai\Services;

use App\Domain\Ai\Knowledge\ArabicNormalizer;
use App\Models\User\Region;
use App\Models\User\UserCity;
use App\Models\User\UserDistrict;
use Illuminate\Support\Facades\Cache;

final class LocationResolver
{
    private const CACHE_TTL = 3600; // 1 hour
    private const HIGH_CONFIDENCE = 90;
    private const MEDIUM_CONFIDENCE = 65;
    private const LOW_CONFIDENCE = 40;

    // Known aliases: normalized Arabic → canonical name
    private const CITY_ALIASES = [
        'الرياض'  => 'الرياض',
        'رياض'    => 'الرياض',
        'riyadh'  => 'الرياض',
        'جدة'     => 'جدة',
        'جده'     => 'جدة',
        'جدا'     => 'جدة',
        'jeddah'  => 'جدة',
        'مكة'     => 'مكة المكرمة',
        'مكه'     => 'مكة المكرمة',
        'الدمام'  => 'الدمام',
        'دمام'    => 'الدمام',
        'الخبر'   => 'الخبر',
        'خبر'     => 'الخبر',
        'المدينة' => 'المدينة المنورة',
        'المدينه' => 'المدينة المنورة',
        'الطائف'  => 'الطائف',
        'طائف'    => 'الطائف',
        'القصيم'  => 'القصيم',
        'الاحساء' => 'الأحساء',
        'احساء'   => 'الأحساء',
        'الحساء'  => 'الأحساء',
    ];

    /**
     * Resolve free-text Arabic location to city_id and/or district_id for a tenant.
     *
     * @return array{city_id: int|null, region_id: int|null, district_id: int|null, city_name: string|null, district_name: string|null, confidence: int, needs_clarification: bool, clarification_question: string|null}
     */
    public function resolve(int $tenantId, string $locationText): array
    {
        $normalized = ArabicNormalizer::normalizeForSearch(trim($locationText));

        if ($normalized === '') {
            return $this->noMatch();
        }

        // 1. Try district match first (حي prefix or direct match)
        $districtResult = $this->resolveDistrict($tenantId, $normalized);
        if ($districtResult !== null) {
            return $districtResult;
        }

        // 2. Try city match
        $cityResult = $this->resolveCity($tenantId, $normalized);
        if ($cityResult !== null) {
            return $cityResult;
        }

        // 3. Try region
        $regionResult = $this->resolveRegion($normalized);
        if ($regionResult !== null) {
            return $regionResult;
        }

        // Low confidence: can't resolve
        return array_merge($this->noMatch(), [
            'needs_clarification'    => true,
            'clarification_question' => 'في أي مدينة أو حي تبحث بالضبط؟',
        ]);
    }

    private function resolveDistrict(int $tenantId, string $normalized): ?array
    {
        // Strip حي prefix
        $search = (string) preg_replace('/^حي\s*/u', '', $normalized);
        $search = ArabicNormalizer::normalizeForSearch($search);

        $cacheKey = 'ai.districts.' . $tenantId;
        $districts = Cache::remember($cacheKey, self::CACHE_TTL, function () {
            return UserDistrict::all(['id', 'name_ar', 'name_en', 'city_id', 'city_name_ar'])->toArray();
        });

        $best      = null;
        $bestScore = 0;

        foreach ($districts as $d) {
            $dNorm = ArabicNormalizer::normalizeForSearch((string) ($d['name_ar'] ?? ''));
            similar_text($search, $dNorm, $pct);
            $score = (int) round($pct);
            if ($dNorm === $search) {
                $score = 100;
            }
            if ($score > $bestScore && $score >= self::MEDIUM_CONFIDENCE) {
                $bestScore = $score;
                $best      = $d;
            }
        }

        if ($best === null) {
            return null;
        }

        $cityId   = !empty($best['city_id']) ? (int) $best['city_id'] : null;
        $cityName = $best['city_name_ar'] ?? null;

        return [
            'city_id'                => $cityId,
            'region_id'              => null,
            'district_id'            => (int) $best['id'],
            'city_name'              => $cityName,
            'district_name'          => $best['name_ar'],
            'confidence'             => $bestScore,
            'needs_clarification'    => $bestScore < self::HIGH_CONFIDENCE,
            'clarification_question' => $bestScore < self::HIGH_CONFIDENCE
                ? 'هل تقصد حي ' . $best['name_ar'] . '؟'
                : null,
        ];
    }

    private function resolveCity(int $tenantId, string $normalized): ?array
    {
        // Check aliases
        $canonical = self::CITY_ALIASES[$normalized] ?? null;

        // Cities in `user_cities` are global (not tenant-scoped), so the cache
        // key doesn't need to vary per tenant, but we keep the parameter for
        // API consistency with the other resolve* methods.
        $cities = Cache::remember('ai.cities.all', self::CACHE_TTL, function () {
            return UserCity::all(['id', 'name_ar', 'name_en'])->toArray();
        });

        $best      = null;
        $bestScore = 0;

        foreach ($cities as $c) {
            $cNorm = ArabicNormalizer::normalizeForSearch((string) ($c['name_ar'] ?? ''));
            if ($canonical !== null && ArabicNormalizer::normalizeForSearch($canonical) === $cNorm) {
                $best      = $c;
                $bestScore = self::HIGH_CONFIDENCE;
                break;
            }
            similar_text($normalized, $cNorm, $pct);
            $score = (int) round($pct);
            if ($cNorm === $normalized) {
                $score = 100;
            }
            if ($score > $bestScore && $score >= self::MEDIUM_CONFIDENCE) {
                $bestScore = $score;
                $best      = $c;
            }
        }

        if ($best === null) {
            return null;
        }

        return [
            'city_id'                => (int) $best['id'],
            'region_id'              => null,
            'district_id'            => null,
            'city_name'              => $best['name_ar'],
            'district_name'          => null,
            'confidence'             => $bestScore,
            'needs_clarification'    => $bestScore < self::HIGH_CONFIDENCE,
            'clarification_question' => $bestScore < self::HIGH_CONFIDENCE
                ? 'هل تقصد مدينة ' . $best['name_ar'] . '؟'
                : null,
        ];
    }

    private function resolveRegion(string $normalized): ?array
    {
        $regions = Cache::remember('ai.regions.all', self::CACHE_TTL, function () {
            return Region::all(['id', 'name_ar', 'name_en'])->toArray();
        });

        $best      = null;
        $bestScore = 0;

        foreach ($regions as $r) {
            $rNorm = ArabicNormalizer::normalizeForSearch((string) ($r['name_ar'] ?? ''));
            similar_text($normalized, $rNorm, $pct);
            $score = (int) round($pct);
            if ($rNorm === $normalized) {
                $score = 100;
            }
            if ($score > $bestScore && $score >= self::MEDIUM_CONFIDENCE) {
                $bestScore = $score;
                $best      = $r;
            }
        }

        if ($best === null) {
            return null;
        }

        return [
            'city_id'                => null,
            'region_id'              => (int) $best['id'],
            'district_id'            => null,
            'city_name'              => $best['name_ar'],
            'district_name'          => null,
            'confidence'             => $bestScore,
            'needs_clarification'    => false,
            'clarification_question' => null,
        ];
    }

    private function noMatch(): array
    {
        return [
            'city_id'                => null,
            'region_id'              => null,
            'district_id'            => null,
            'city_name'              => null,
            'district_name'          => null,
            'confidence'             => 0,
            'needs_clarification'    => true,
            'clarification_question' => 'في أي مدينة تبحث عن العقار؟',
        ];
    }
}
