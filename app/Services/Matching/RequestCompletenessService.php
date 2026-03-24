<?php

namespace App\Services\Matching;

use App\Repositories\RequestRepository;
use App\Support\DTO\UnifiedRequest;

class RequestCompletenessService
{
    public function __construct(
        private RequestRepository $requests,
    ) {}

    /**
     * Check only city/location and property type (minimal bar for starting matching).
     *
     * @return string[] list of missing minimal field keys
     */
    public function getMinimalMissingFields(UnifiedRequest $request): array
    {
        $missing = [];
        if (!$this->hasLocation($request)) {
            $missing[] = 'location';
        }
        if (!$this->hasCategory($request)) {
            $missing[] = 'category';
        }
        return $missing;
    }

    public function hasMinimalData(UnifiedRequest $request): bool
    {
        return count($this->getMinimalMissingFields($request)) === 0;
    }

    /**
     * Convenience method for controllers: returns both minimal and full completeness info.
     *
     * @return array{unified:?UnifiedRequest,has_minimal_data:bool,minimal_missing_fields:string[],is_complete:bool,missing_fields:string[]}
     */
    public function validateMinimal(string $source, int $id): array
    {
        $unified = $this->requests->getUnified($source, $id);
        if (!$unified) {
            return [
                'unified' => null,
                'has_minimal_data' => false,
                'minimal_missing_fields' => ['not_found'],
                'is_complete' => false,
                'missing_fields' => ['not_found'],
            ];
        }
        $minimalMissing = $this->getMinimalMissingFields($unified);
        $allMissing = $this->getMissingFields($unified);
        return [
            'unified' => $unified,
            'has_minimal_data' => count($minimalMissing) === 0,
            'minimal_missing_fields' => $minimalMissing,
            'is_complete' => count($allMissing) === 0,
            'missing_fields' => $allMissing,
        ];
    }

    /**
     * Convenience method for controllers/observers.
     *
     * @return array{unified:?UnifiedRequest,is_complete:bool,missing_fields:string[]}
     */
    public function validate(string $source, int $id): array
    {
        $unified = $this->requests->getUnified($source, $id);
        if (!$unified) {
            return [
                'unified' => null,
                'is_complete' => false,
                'missing_fields' => ['not_found'],
            ];
        }

        $missing = $this->getMissingFields($unified);

        return [
            'unified' => $unified,
            'is_complete' => count($missing) === 0,
            'missing_fields' => $missing,
        ];
    }

    public function isComplete(UnifiedRequest $request): bool
    {
        return count($this->getMissingFields($request)) === 0;
    }

    /**
     * @return string[] list of missing field keys
     */
    public function getMissingFields(UnifiedRequest $request): array
    {
        $missing = [];

        $purpose = $this->inferPurpose($request);
        if (!$purpose) {
            $missing[] = 'purpose';
        }

        if (!$this->hasBudget($request)) {
            $missing[] = 'budget';
        }

        if (!$this->hasArea($request)) {
            $missing[] = 'area';
        }

        if (!$this->hasCategory($request)) {
            $missing[] = 'category';
        }

        if (!$this->hasLocation($request)) {
            $missing[] = 'location';
        }

        return $missing;
    }

    public function inferPurpose(UnifiedRequest $request): ?string
    {
        $raw = $request->purpose;
        if (is_string($raw)) {
            $rawLower = mb_strtolower(trim($raw));
            if (in_array($rawLower, ['rent', 'sale'], true)) {
                return $rawLower;
            }
        }

        // Best-effort mapping for common values
        $candidate = trim((string) ($raw ?? ''));
        if ($candidate === '') {
            return null;
        }

        $candidateLower = mb_strtolower($candidate);

        // English
        if (str_contains($candidateLower, 'rent') || str_contains($candidateLower, 'rental') || str_contains($candidateLower, 'lease')) {
            return 'rent';
        }
        if (str_contains($candidateLower, 'sale') || str_contains($candidateLower, 'buy') || str_contains($candidateLower, 'purchase')) {
            return 'sale';
        }

        // Arabic (common forms)
        $candidateAr = str_replace(['أ', 'إ', 'آ', 'ٱ'], 'ا', $candidate);
        if (mb_stripos($candidateAr, 'ايجار') !== false || mb_stripos($candidateAr, 'اجار') !== false || mb_stripos($candidateAr, 'استئجار') !== false) {
            return 'rent';
        }
        if (mb_stripos($candidateAr, 'بيع') !== false || mb_stripos($candidateAr, 'شراء') !== false || mb_stripos($candidateAr, 'تملك') !== false) {
            return 'sale';
        }

        return null;
    }

    private function hasBudget(UnifiedRequest $request): bool
    {
        if ($request->budget !== null) {
            return true;
        }

        return $request->budgetFrom !== null || $request->budgetTo !== null;
    }

    private function hasArea(UnifiedRequest $request): bool
    {
        return $request->areaFrom !== null
            || $request->areaTo !== null
            || $request->minAreaSqm !== null
            || $request->maxAreaSqm !== null;
    }

    private function hasCategory(UnifiedRequest $request): bool
    {
        return $request->categoryId !== null || $request->propertyType !== null;
    }

    private function hasLocation(UnifiedRequest $request): bool
    {
        // Web
        if (!empty($request->region) || $request->regionId !== null || $request->cityId !== null || $request->districtId !== null) {
            return true;
        }

        // WhatsApp
        if (!empty($request->cityName) || !empty($request->districtName)) {
            return true;
        }

        if ($request->latitude !== null && $request->longitude !== null) {
            return true;
        }

        return false;
    }
}

