<?php

declare(strict_types=1);

namespace App\Domain\Ai\Location\Services;

use App\Domain\Ai\Location\Contracts\LocationRerankService;
use App\Domain\Ai\Contracts\LlmClient;
use App\Domain\Ai\DTOs\LlmMessage;
use App\Domain\Ai\DTOs\LlmRequest;
use App\Domain\Ai\DTOs\LlmResponse;
use App\Domain\Ai\Location\DTOs\LocationCandidate;

final class LocationLlmReranker implements LocationRerankService
{
    /**
     * @param LocationCandidate[] $candidates
     */
    public function rerank(
        LlmClient $driver,
        string $model,
        string $rawLocationText,
        string $normalized,
        bool $hasDistrictMarker,
        array $candidates,
    ): LlmResponse {
        $candidateLines = array_map(static function (LocationCandidate $c): string {
            if ($c->type === 'district') {
                return "- district #{$c->id}: {$c->nameAr} (city: {$c->cityNameAr} / city_id={$c->cityId})";
            }
            return "- {$c->type} #{$c->id}: {$c->nameAr}";
        }, $candidates);

        $rules = implode("\n", [
            'You are a geographic resolver for Saudi Arabia locations.',
            'Pick ONE best match from the provided candidates or ask for clarification.',
            'You MUST use IDs exactly as provided; do not invent IDs.',
            'If the query is a bare city name and there is an exact city candidate, prefer city.',
            'If the user used "حي" explicitly, prefer district candidates.',
            'If multiple candidates are plausible (e.g., same district name across cities), set needs_clarification=true and ask which city.',
            'Return JSON only (no markdown, no extra text).',
        ]);

        $payload = implode("\n", [
            "Query(raw): {$rawLocationText}",
            "Query(normalized): {$normalized}",
            'Hint(has_district_marker): ' . ($hasDistrictMarker ? 'true' : 'false'),
            '',
            "Candidates:\n" . implode("\n", $candidateLines),
            '',
            'Return JSON with this schema:',
            '{',
            '  "type": "city|district|region|none",',
            '  "city_id": number|null,',
            '  "district_id": number|null,',
            '  "region_id": number|null,',
            '  "confidence": number,',
            '  "needs_clarification": boolean,',
            '  "clarification_question": string|null',
            '}',
        ]);

        $request = new LlmRequest(
            messages: [
                LlmMessage::system($rules),
                LlmMessage::user($payload),
            ],
            model: $model,
            maxTokens: 220,
            temperature: 0.0,
            jsonMode: true,
            timeoutSeconds: 15,
        );

        return $driver->complete($request);
    }
}

