<?php

declare(strict_types=1);

namespace Tests\Unit\Ai;

use App\Domain\Ai\Contracts\LlmClient;
use App\Domain\Ai\Contracts\TenantLlmFactory;
use App\Domain\Ai\DTOs\LlmRequest;
use App\Domain\Ai\DTOs\LlmResponse;
use App\Domain\Ai\Location\DTOs\LocationCandidate;
use App\Domain\Ai\Location\Contracts\LocationCandidateRetrieval;
use App\Domain\Ai\Location\Contracts\LocationRerankService;
use App\Domain\Ai\Location\Services\LocationRagResolver;
use App\Domain\Ai\Services\LocationResolver;
use App\Domain\Ai\Services\UsageRecorder;
use Tests\TestCase;

final class LocationRagResolverPureUnitTest extends TestCase
{
    public function test_bare_city_prefers_city_over_similarly_named_district(): void
    {
        $fakeRetriever = new class implements LocationCandidateRetrieval {
            public function retrieve(string $rawLocationText): array
            {
                return [
                    'normalized' => 'الرياض',
                    'has_district_marker' => false,
                    'candidates' => [
                        new LocationCandidate(type: 'district', id: 10200018013, nameAr: 'حي الرياض', nameEn: null, cityId: 18, cityNameAr: 'جدة', score: 92.0, reason: 'contains'),
                        new LocationCandidate(type: 'city', id: 3, nameAr: 'الرياض', nameEn: 'Riyadh', score: 100.0, reason: 'exact'),
                    ],
                ];
            }
        };

        $fakeReranker = new class implements LocationRerankService {
            public function rerank(
                \App\Domain\Ai\Contracts\LlmClient $driver,
                string $model,
                string $rawLocationText,
                string $normalized,
                bool $hasDistrictMarker,
                array $candidates,
            ): LlmResponse {
                return new LlmResponse(
                    content: json_encode([
                        'type' => 'city',
                        'city_id' => 3,
                        'district_id' => null,
                        'region_id' => null,
                        'confidence' => 96,
                        'needs_clarification' => false,
                        'clarification_question' => null,
                    ], JSON_UNESCAPED_UNICODE),
                    tokensIn: 0,
                    tokensOut: 0,
                    latencyMs: 1,
                    model: 'test',
                    provider: 'test',
                    success: true,
                );
            }
        };

        $fakeTenantFactory = new class implements TenantLlmFactory {
            public function makeForTenant(int $tenantId, string $tier = 'chat'): LlmClient
            {
                return new class implements LlmClient {
                    public function complete(LlmRequest $request): LlmResponse
                    {
                        return new LlmResponse('{}', 0, 0, 1, 'test', 'test');
                    }
                };
            }
        };

        // Note: UsageRecorder writes to DB in real app; for pure unit test we don't need it,
        // so we pass a real instance but avoid calling it by ensuring rerank succeeds quickly.
        $usage = $this->app->make(UsageRecorder::class);

        $rag = new LocationRagResolver($fakeRetriever, $fakeReranker, $fakeTenantFactory, $usage);
        $this->app->instance(\App\Domain\Ai\Location\Services\LocationRagResolver::class, $rag);

        $resolver = $this->app->make(LocationResolver::class);
        $res = $resolver->resolve(1, 'الرياض');

        $this->assertSame(3, $res['city_id']);
        $this->assertNull($res['district_id']);
        $this->assertSame('الرياض', $res['city_name']);
        $this->assertFalse((bool) $res['needs_clarification']);
    }

    public function test_explicit_district_marker_prefers_district(): void
    {
        $fakeRetriever = new class implements LocationCandidateRetrieval {
            public function retrieve(string $rawLocationText): array
            {
                return [
                    'normalized' => 'الرياض',
                    'has_district_marker' => true,
                    'candidates' => [
                        new LocationCandidate(type: 'district', id: 10200018013, nameAr: 'حي الرياض', nameEn: null, cityId: 18, cityNameAr: 'جدة', score: 96.0, reason: 'exact+district_marker'),
                        new LocationCandidate(type: 'city', id: 3, nameAr: 'الرياض', nameEn: 'Riyadh', score: 92.0, reason: 'contains'),
                    ],
                ];
            }
        };

        $fakeReranker = new class implements LocationRerankService {
            public function rerank(
                \App\Domain\Ai\Contracts\LlmClient $driver,
                string $model,
                string $rawLocationText,
                string $normalized,
                bool $hasDistrictMarker,
                array $candidates,
            ): LlmResponse {
                return new LlmResponse(
                    content: json_encode([
                        'type' => 'district',
                        'city_id' => 18,
                        'district_id' => 10200018013,
                        'region_id' => null,
                        'confidence' => 93,
                        'needs_clarification' => false,
                        'clarification_question' => null,
                    ], JSON_UNESCAPED_UNICODE),
                    tokensIn: 0,
                    tokensOut: 0,
                    latencyMs: 1,
                    model: 'test',
                    provider: 'test',
                    success: true,
                );
            }
        };

        $fakeTenantFactory = new class implements TenantLlmFactory {
            public function makeForTenant(int $tenantId, string $tier = 'chat'): LlmClient
            {
                return new class implements LlmClient {
                    public function complete(LlmRequest $request): LlmResponse
                    {
                        return new LlmResponse('{}', 0, 0, 1, 'test', 'test');
                    }
                };
            }
        };

        $usage = $this->app->make(UsageRecorder::class);

        $rag = new LocationRagResolver($fakeRetriever, $fakeReranker, $fakeTenantFactory, $usage);
        $this->app->instance(\App\Domain\Ai\Location\Services\LocationRagResolver::class, $rag);

        $resolver = $this->app->make(LocationResolver::class);
        $res = $resolver->resolve(1, 'حي الرياض');

        $this->assertSame(18, $res['city_id']);
        $this->assertSame(10200018013, $res['district_id']);
        $this->assertSame('جدة', $res['city_name']);
        $this->assertSame('حي الرياض', $res['district_name']);
    }
}

