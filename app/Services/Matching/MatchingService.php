<?php

namespace App\Services\Matching;

use App\Models\PropertyMatch;
use App\Services\Property\PropertyCrmRelationService;
use App\Repositories\PropertyRepository;
use App\Repositories\RequestRepository;
use App\Support\DTO\MatchResult;
use App\Support\DTO\UnifiedRequest;
use App\Support\PhoneNormalizer;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class MatchingService
{
    public function __construct(
        private RequestRepository $requests,
        private PropertyRepository $properties,
        private PropertySearchService $search,
        private MatchingScorer $aiScorer,
        private PropertyCrmRelationService $crmRelationService,
    ) {}

    /**
     * Generate and persist matches for a request.
     * @return MatchResult[]
     */
    public function generateMatchesForRequest(string $source, int $requestId, int $limit = 25, bool $forceAi = true, ?int $expectedUserId = null): array
    {
        $unified = $this->requests->getUnified($source, $requestId);
        if (!$unified) return [];
        if ($expectedUserId !== null && $unified->userId !== $expectedUserId) {
            Log::warning('MatchingService: ownership mismatch, skipping generation', [
                'source' => $source,
                'request_id' => $requestId,
                'expected_user_id' => $expectedUserId,
                'actual_user_id' => $unified->userId,
            ]);
            return [];
        }


        $query = $this->search->buildCandidatesQuery($unified)->limit($limit);
        $candidates = $query->get();

        $count = $candidates->count();
        $usedTextFallback = false;
        
        Log::info('MatchingService: candidate properties fetched (structured)', [
            'source' => $source,
            'request_id' => $requestId,
            'count' => $count,
        ]);
        
        // Fallback to text-based search if structured search returned insufficient results
        $fallbackThreshold = 5;
        if ($count < $fallbackThreshold && $source === 'whatsapp') {
            Log::info('MatchingService: attempting text-based fallback search', [
                'source' => $source,
                'request_id' => $requestId,
                'structured_count' => $count,
            ]);
            
            $textQuery = $this->search->buildTextBasedQuery($unified)->limit($limit);
            $textCandidates = $textQuery->get();
            
            Log::info('MatchingService: text-based search completed', [
                'source' => $source,
                'request_id' => $requestId,
                'text_count' => $textCandidates->count(),
            ]);
            
            // Merge candidates, removing duplicates
            $candidateIds = $candidates->pluck('id')->all();
            $newCandidates = $textCandidates->filter(function($prop) use ($candidateIds) {
                return !in_array($prop->id, $candidateIds);
            });
            
            if ($newCandidates->count() > 0) {
                $candidates = $candidates->merge($newCandidates);
                $usedTextFallback = true;
                
                Log::info('MatchingService: merged text-based candidates', [
                    'source' => $source,
                    'request_id' => $requestId,
                    'new_candidates' => $newCandidates->count(),
                    'total_after_merge' => $candidates->count(),
                ]);
            }
        }
        
        $count = $candidates->count();
        Log::info('MatchingService: final candidate count', [
            'source' => $source,
            'request_id' => $requestId,
            'count' => $count,
            'used_text_fallback' => $usedTextFallback,
        ]);
        
        if ($count === 0) {
            Log::info('MatchingService: no candidate properties found', [
                'source' => $source,
                'request_id' => $requestId,
            ]);
            $this->logNoCandidateDiagnostics($unified);
            return [];
        }

        Log::info('MatchingService: candidate property IDs', [
            'source' => $source,
            'request_id' => $requestId,
            'ids' => $candidates->pluck('id')->take(50)->all(),
        ]);

        // Database score (simple heuristic)
        $results = [];
        foreach ($candidates as $p) {
            $r = new MatchResult($p->id);
            $r->databaseScore = $this->computeDbScore($unified, $p);
            $results[$p->id] = $r;
        }

        // AI scoring
        $aiMap = $forceAi && count($candidates) ? $this->aiScorer->scoreWithAI($unified, $candidates->all()) : [];
        foreach ($results as $pid => $r) {
            $ai = $aiMap[$pid] ?? null;
            $r->aiScore = $ai['ai_score'] ?? null;
            $r->matchedCriteria = $ai['matched_criteria'] ?? null;
            $r->explanation = $ai['explanation'] ?? null;
            $r->matchScore = (int) ($r->databaseScore + ($r->aiScore ?? 0));

            // persist
            PropertyMatch::updateOrCreate(
                [
                    'user_id' => $unified->userId,
                    'request_type' => $source,
                    'request_id' => $requestId,
                    'property_id' => $pid,
                ],
                [
                    'user_id' => $unified->userId,
                    'customer_key' => PhoneNormalizer::normalize($unified->phone),
                    'database_score' => $r->databaseScore,
                    'ai_score' => $r->aiScore ?? 0,
                    'match_score' => $r->matchScore,
                    'matched_criteria' => $r->matchedCriteria,
                    'match_explanation' => $r->explanation,
                    // unread until viewed
                    'is_reviewed' => false,
                ]
            );

            if ($source === 'crm') {
                $this->crmRelationService->recordAiMatch(
                    $pid,
                    $requestId,
                    $unified->userId,
                    $unified->customer_id ?? null,
                );
            }
        }

        Log::info('MatchingService: persisted matches summary', [
            'source' => $source,
            'request_id' => $requestId,
            'total' => count($results),
            'max_score' => max(array_map(fn($m) => $m->matchScore, $results)) ?: 0,
            'min_score' => min(array_map(fn($m) => $m->matchScore, $results)) ?: 0,
        ]);

        return array_values($results);
    }

    private function computeDbScore(UnifiedRequest $u, $p): int
    {
        $score = 0;

        // Location
        if ($u->regionId && $p->region_id == $u->regionId) $score += 10;
        
        // Text-based location matching bonus (for WhatsApp inquiries without structured location)
        if ($u->source === 'whatsapp' && !$u->regionId && !empty($u->message)) {
            $locationKeywords = $this->extractLocationKeywordsForScoring($u);
            if (!empty($locationKeywords)) {
                $textMatchScore = $this->computeTextMatchScore($p, $locationKeywords);
                $score += $textMatchScore;
            }
        }
        
        // Bedrooms
        if ($u->bedrooms && $p->beds >= $u->bedrooms) $score += 10;
        // Area
        if ($u->areaFrom && $p->area >= $u->areaFrom) $score += 5;
        if ($u->areaTo && $p->area <= $u->areaTo) $score += 5;
        if ($u->minAreaSqm && $p->area >= $u->minAreaSqm) $score += 5;
        if ($u->maxAreaSqm && $p->area <= $u->maxAreaSqm) $score += 5;
        // Budget
        if ($u->budgetFrom && $p->price >= $u->budgetFrom) $score += 5;
        if ($u->budgetTo && $p->price <= $u->budgetTo) $score += 5;
        if ($u->budget && abs($p->price - $u->budget) <= ($u->budget * 0.1)) $score += 5;
        // Type/purpose
        if ($u->propertyType && $p->property_type === $u->propertyType) $score += 5;
        if ($u->purpose && $p->purpose === $u->purpose) $score += 5;

        return min(50, $score);
    }

    /**
     * Extract location keywords for scoring purposes.
     */
    private function extractLocationKeywordsForScoring(UnifiedRequest $u): array
    {
        $keywords = [];
        
        // Try city/district names first
        if (!empty($u->cityName)) {
            $keywords[] = $u->cityName;
        }
        if (!empty($u->districtName)) {
            $keywords[] = $u->districtName;
        }
        
        // Extract from message
        if (!empty($u->message)) {
            $message = $u->message;
            $locationMarkers = ['في', 'بـ', 'ب', 'حي', 'شارع', 'منطقة', 'مدينة'];
            
            foreach ($locationMarkers as $marker) {
                if (strpos($message, $marker) !== false) {
                    $parts = explode($marker, $message);
                    if (count($parts) > 1) {
                        $locationPart = trim($parts[1]);
                        $words = preg_split('/[\s،؛.]+/', $locationPart, 3);
                        if (!empty($words)) {
                            // Take first 1-2 words after marker
                            $keywords[] = trim($words[0]);
                            if (isset($words[1]) && mb_strlen($words[1]) > 2) {
                                $keywords[] = trim($words[0] . ' ' . $words[1]);
                            }
                        }
                    }
                }
            }
        }
        
        return array_unique($keywords);
    }

    /**
     * Compute text match score by checking property content for location keywords.
     */
    private function computeTextMatchScore($property, array $keywords): int
    {
        if (empty($keywords)) {
            return 0;
        }
        
        $score = 0;
        $content = $property->contents->first();
        
        if (!$content) {
            return 0;
        }
        
        $title = $content->title ?? '';
        $address = $content->address ?? '';
        
        // Normalize Arabic text for matching
        $normalizedTitle = $this->normalizeArabic($title);
        $normalizedAddress = $this->normalizeArabic($address);
        
        foreach ($keywords as $keyword) {
            $normalizedKeyword = $this->normalizeArabic($keyword);
            
            // Exact phrase match in title (highest priority)
            if (mb_stripos($normalizedTitle, $normalizedKeyword) !== false) {
                $score += 15;
                break; // Don't double count
            }
            
            // Exact phrase match in address
            if (mb_stripos($normalizedAddress, $normalizedKeyword) !== false) {
                $score += 10;
                break;
            }
        }
        
        return min(15, $score); // Cap at 15 points for text matches
    }

    /**
     * Normalize Arabic text (same logic as PropertySearchService).
     */
    private function normalizeArabic(string $text): string
    {
        $replacements = [
            'أ' => 'ا',
            'إ' => 'ا',
            'آ' => 'ا',
            'ى' => 'ي',
            'ئ' => 'ي',
            'ؤ' => 'و',
            'ة' => 'ه',
            'ٱ' => 'ا',
            'گ' => 'ك',
            'چ' => 'ج',
            'ژ' => 'ز',
            'ڤ' => 'ف',
            'پ' => 'ب',
        ];
        
        return strtr($text, $replacements);
    }


    private function logNoCandidateDiagnostics(UnifiedRequest $u): void
    {
        try {
            $base = $this->properties->baseQuery();
            if ($u->userId) {
                $base->where('user_id', $u->userId);
            }
            $diag = [
                'all_for_user' => (clone $base)->count(),
            ];
            if ($u->regionId) $diag['region_id'] = (clone $base)->where('region_id', $u->regionId)->count();
            if ($u->categoryId) $diag['category_id'] = (clone $base)->where('category_id', $u->categoryId)->count();
            if ($u->propertyType) $diag['property_type'] = (clone $base)->where('property_type', $u->propertyType)->count();
            if ($u->purpose) $diag['purpose'] = (clone $base)->where('purpose', $u->purpose)->count();
            if ($u->bedrooms) $diag['beds>='] = (clone $base)->where('beds', '>=', $u->bedrooms)->count();
            if ($u->bathrooms) $diag['bath>='] = (clone $base)->where('bath', '>=', $u->bathrooms)->count();
            if ($u->areaFrom) $diag['area>='] = (clone $base)->where('area', '>=', $u->areaFrom)->count();
            if ($u->areaTo) $diag['area<='] = (clone $base)->where('area', '<=', $u->areaTo)->count();
            if ($u->minAreaSqm) $diag['area>=min_area_sqm'] = (clone $base)->where('area', '>=', $u->minAreaSqm)->count();
            if ($u->maxAreaSqm) $diag['area<=max_area_sqm'] = (clone $base)->where('area', '<=', $u->maxAreaSqm)->count();
            if ($u->budgetFrom) $diag['price>='] = (clone $base)->where('price', '>=', $u->budgetFrom)->count();
            if ($u->budgetTo) $diag['price<='] = (clone $base)->where('price', '<=', $u->budgetTo)->count();
            if ($u->budget && !$u->budgetFrom && !$u->budgetTo) {
                $diag['price_between_budget±10%'] = (clone $base)->whereBetween('price', [$u->budget * 0.9, $u->budget * 1.1])->count();
            }

            Log::info('MatchingService: no-candidate diagnostics', [
                'request' => [
                    'source' => $u->source,
                    'id' => $u->id,
                    'region_id' => $u->regionId,
                    'category_id' => $u->categoryId,
                    'property_type' => $u->propertyType,
                    'purpose' => $u->purpose,
                    'beds' => $u->bedrooms,
                    'bath' => $u->bathrooms,
                    'area_from' => $u->areaFrom,
                    'area_to' => $u->areaTo,
                    'min_area_sqm' => $u->minAreaSqm,
                    'max_area_sqm' => $u->maxAreaSqm,
                    'budget_from' => $u->budgetFrom,
                    'budget_to' => $u->budgetTo,
                    'budget' => $u->budget,
                ],
                'diag' => $diag,
            ]);
        } catch (\Throwable $e) {
            Log::warning('MatchingService: failed diagnostics logging', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}


