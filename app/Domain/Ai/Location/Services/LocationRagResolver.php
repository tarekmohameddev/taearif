<?php

declare(strict_types=1);

namespace App\Domain\Ai\Location\Services;

use App\Domain\Ai\Location\DTOs\LocationCandidate;
use App\Domain\Ai\Location\DTOs\LocationResolution;
use App\Domain\Ai\Location\Contracts\LocationCandidateRetrieval;
use App\Domain\Ai\Location\Contracts\LocationRerankService;
use App\Domain\Ai\Contracts\TenantLlmFactory;
use App\Domain\Ai\Services\UsageRecorder;
use Illuminate\Support\Facades\Log;

final class LocationRagResolver
{
    public function __construct(
        private readonly LocationCandidateRetrieval $retriever,
        private readonly LocationRerankService $reranker,
        private readonly TenantLlmFactory $driverFactory,
        private readonly UsageRecorder $usageRecorder,
    ) {}

    public function resolve(int $tenantId, string $rawLocationText, ?int $conversationId = null): LocationResolution
    {
        $retrieved = $this->retriever->retrieve($rawLocationText);
        $normalized = (string) ($retrieved['normalized'] ?? '');
        $hasDistrictMarker = (bool) ($retrieved['has_district_marker'] ?? false);
        /** @var LocationCandidate[] $candidates */
        $candidates = $retrieved['candidates'] ?? [];

        if ($normalized === '' || $candidates === []) {
            return new LocationResolution(
                cityId: null,
                districtId: null,
                regionId: null,
                cityName: null,
                districtName: null,
                confidence: 0,
                needsClarification: true,
                clarificationQuestion: 'في أي مدينة تبحث عن العقار؟',
                source: 'fallback',
                candidates: $candidates,
            );
        }

        $driver = $this->driverFactory->makeForTenant($tenantId);
        $model = (string) config('openai.fast_model', 'gpt-5-nano');

        try {
            $resp = $this->reranker->rerank(
                driver: $driver,
                model: $model,
                rawLocationText: $rawLocationText,
                normalized: $normalized,
                hasDistrictMarker: $hasDistrictMarker,
                candidates: $candidates,
            );

            $this->usageRecorder->record($tenantId, 'location_rerank', $resp, $conversationId);

            $data = json_decode($resp->content, true);
            if (! is_array($data)) {
                return $this->deterministicFallback($candidates, $rawLocationText);
            }

            return $this->validateSelection($data, $candidates, $rawLocationText, source: 'llm');
        } catch (\Throwable $e) {
            Log::warning('ai.location_rag.llm_failed', ['tenant_id' => $tenantId, 'error' => $e->getMessage()]);
            return $this->deterministicFallback($candidates, $rawLocationText);
        }
    }

    /**
     * @param array<string, mixed> $data
     * @param LocationCandidate[] $candidates
     */
    private function validateSelection(array $data, array $candidates, string $rawLocationText, string $source): LocationResolution
    {
        $type = (string) ($data['type'] ?? 'none');

        $confidence = (int) round((float) ($data['confidence'] ?? 0));
        $confidence = max(0, min(100, $confidence));

        $needsClarification = (bool) ($data['needs_clarification'] ?? false);
        $clarificationQuestion = isset($data['clarification_question']) ? (string) $data['clarification_question'] : null;
        $clarificationQuestion = $clarificationQuestion !== '' ? $clarificationQuestion : null;

        $byIdType = $this->indexCandidates($candidates);

        if ($type === 'district') {
            $districtId = isset($data['district_id']) ? (int) $data['district_id'] : 0;
            $key = 'district:' . $districtId;
            $cand = $byIdType[$key] ?? null;
            if (! $cand instanceof LocationCandidate) {
                return $this->deterministicFallback($candidates, $rawLocationText);
            }

            return new LocationResolution(
                cityId: $cand->cityId,
                districtId: $cand->id,
                regionId: null,
                cityName: $cand->cityNameAr,
                districtName: $cand->nameAr,
                confidence: $confidence > 0 ? $confidence : (int) round(min(99.0, $cand->score)),
                needsClarification: $needsClarification,
                clarificationQuestion: $needsClarification ? ($clarificationQuestion ?? ('هل تقصد حي ' . $cand->nameAr . '؟')) : null,
                source: $source,
                candidates: $candidates,
            );
        }

        if ($type === 'city') {
            $cityId = isset($data['city_id']) ? (int) $data['city_id'] : 0;
            $key = 'city:' . $cityId;
            $cand = $byIdType[$key] ?? null;
            if (! $cand instanceof LocationCandidate) {
                return $this->deterministicFallback($candidates, $rawLocationText);
            }

            return new LocationResolution(
                cityId: $cand->id,
                districtId: null,
                regionId: null,
                cityName: $cand->nameAr,
                districtName: null,
                confidence: $confidence > 0 ? $confidence : (int) round(min(99.0, $cand->score)),
                needsClarification: $needsClarification,
                clarificationQuestion: $needsClarification ? ($clarificationQuestion ?? ('هل تقصد مدينة ' . $cand->nameAr . '؟')) : null,
                source: $source,
                candidates: $candidates,
            );
        }

        if ($type === 'region') {
            $regionId = isset($data['region_id']) ? (int) $data['region_id'] : 0;
            $key = 'region:' . $regionId;
            $cand = $byIdType[$key] ?? null;
            if (! $cand instanceof LocationCandidate) {
                return $this->deterministicFallback($candidates, $rawLocationText);
            }

            return new LocationResolution(
                cityId: null,
                districtId: null,
                regionId: $cand->id,
                cityName: $cand->nameAr,
                districtName: null,
                confidence: $confidence > 0 ? $confidence : (int) round(min(99.0, $cand->score)),
                needsClarification: false,
                clarificationQuestion: null,
                source: $source,
                candidates: $candidates,
            );
        }

        // none / unknown
        return new LocationResolution(
            cityId: null,
            districtId: null,
            regionId: null,
            cityName: null,
            districtName: null,
            confidence: $confidence,
            needsClarification: true,
            clarificationQuestion: $clarificationQuestion ?? 'في أي مدينة أو حي تبحث بالضبط؟',
            source: $source,
            candidates: $candidates,
        );
    }

    /**
     * @param LocationCandidate[] $candidates
     * @return array<string, LocationCandidate>
     */
    private function indexCandidates(array $candidates): array
    {
        $out = [];
        foreach ($candidates as $c) {
            $out[$c->type . ':' . $c->id] = $c;
        }
        return $out;
    }

    /**
     * Deterministic degraded mode: only accept a single exact (score 100) hit.
     *
     * @param LocationCandidate[] $candidates
     */
    private function deterministicFallback(array $candidates, string $rawLocationText): LocationResolution
    {
        $exact = array_values(array_filter($candidates, static fn (LocationCandidate $c) => $c->score >= 99.9));
        if (count($exact) === 1) {
            $c = $exact[0];
            if ($c->type === 'city') {
                return new LocationResolution(
                    cityId: $c->id,
                    districtId: null,
                    regionId: null,
                    cityName: $c->nameAr,
                    districtName: null,
                    confidence: 95,
                    needsClarification: false,
                    clarificationQuestion: null,
                    source: 'deterministic',
                    candidates: $candidates,
                );
            }
            if ($c->type === 'district') {
                return new LocationResolution(
                    cityId: $c->cityId,
                    districtId: $c->id,
                    regionId: null,
                    cityName: $c->cityNameAr,
                    districtName: $c->nameAr,
                    confidence: 95,
                    needsClarification: false,
                    clarificationQuestion: null,
                    source: 'deterministic',
                    candidates: $candidates,
                );
            }
            if ($c->type === 'region') {
                return new LocationResolution(
                    cityId: null,
                    districtId: null,
                    regionId: $c->id,
                    cityName: $c->nameAr,
                    districtName: null,
                    confidence: 95,
                    needsClarification: false,
                    clarificationQuestion: null,
                    source: 'deterministic',
                    candidates: $candidates,
                );
            }
        }

        return new LocationResolution(
            cityId: null,
            districtId: null,
            regionId: null,
            cityName: null,
            districtName: null,
            confidence: 0,
            needsClarification: true,
            clarificationQuestion: 'في أي مدينة أو حي تبحث بالضبط؟',
            source: 'fallback',
            candidates: $candidates,
        );
    }
}

