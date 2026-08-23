<?php

declare(strict_types=1);

namespace App\Domain\RealEstateAgent\Safety;

/**
 * Accumulates facts returned by tools during one agent turn.
 *
 * Only data present in the ledger may be cited in the reply via placeholders.
 * This makes hallucinated property details structurally impossible: if the model
 * never called search_inventory or get_property_details, the property ID is not
 * in the ledger and CitationGuard will reject any {{p:ID|...}} reference.
 */
final class FactLedger
{
    /** @var array<int, array<string, mixed>> keyed by property ID */
    private array $properties = [];

    /** @var array<string, array<string, mixed>> keyed by chunk_id */
    private array $knowledgeChunks = [];

    /** @var bool Set by EscalateToHumanTool */
    private bool $escalationRequested = false;

    /** @var string|null */
    private ?string $escalationReason = null;

    /** @var bool Set when search_inventory returned 0 results */
    private bool $searchRunWithNoResults = false;

    /** @var array<string, mixed>[] Recorded customer facts (from RecordCustomerFactTool) */
    private array $recordedFacts = [];

    /** @var bool Set when search_inventory was called at all */
    private bool $searchWasRun = false;

    /** @var bool Set when search returned location_relaxed = true */
    private bool $locationRelaxed = false;

    /** @var string|null The requested location text (for disclosure) */
    private ?string $requestedLocation = null;

    /**
     * Register all properties returned by search_inventory or get_property_details.
     *
     * @param array<int, array<string, mixed>> $properties
     */
    public function addProperties(array $properties): void
    {
        foreach ($properties as $row) {
            if (isset($row['id']) && is_numeric($row['id'])) {
                $this->properties[(int) $row['id']] = $row;
            }
        }
    }

    /**
     * @param array<string, mixed>[] $chunks
     */
    public function addKnowledgeChunks(array $chunks): void
    {
        foreach ($chunks as $chunk) {
            $id = (string) ($chunk['chunk_id'] ?? $chunk['id'] ?? '');
            if ($id !== '') {
                $this->knowledgeChunks[$id] = $chunk;
            }
        }
    }

    public function recordEscalation(string $reason): void
    {
        $this->escalationRequested = true;
        $this->escalationReason    = $reason;
    }

    /** Reset an escalation that HandoffGuard has rejected as unevidenced. */
    public function clearEscalation(): void
    {
        $this->escalationRequested = false;
        $this->escalationReason    = null;
    }

    public function recordSearchRun(bool $hasResults, bool $locationRelaxed = false, ?string $requestedLocation = null): void
    {
        $this->searchWasRun          = true;
        $this->searchRunWithNoResults = !$hasResults;
        $this->locationRelaxed        = $locationRelaxed;
        $this->requestedLocation      = $requestedLocation;
    }

    public function addRecordedFacts(array $facts): void
    {
        $this->recordedFacts[] = $facts;
    }

    // ─── Reads ───────────────────────────────────────────────────────────────

    public function hasProperty(int $id): bool
    {
        return isset($this->properties[$id]);
    }

    /** @return array<string, mixed>|null */
    public function getProperty(int $id): ?array
    {
        return $this->properties[$id] ?? null;
    }

    /** @return array<int, array<string, mixed>> */
    public function allProperties(): array
    {
        return $this->properties;
    }

    public function hasKnowledgeChunk(string $id): bool
    {
        return isset($this->knowledgeChunks[$id]);
    }

    public function escalationRequested(): bool { return $this->escalationRequested; }
    public function escalationReason(): ?string  { return $this->escalationReason; }
    public function searchWasRun(): bool          { return $this->searchWasRun; }
    public function searchReturnedNoResults(): bool { return $this->searchRunWithNoResults; }
    public function locationRelaxed(): bool       { return $this->locationRelaxed; }
    public function requestedLocation(): ?string  { return $this->requestedLocation; }
    public function propertyCount(): int          { return count($this->properties); }

    /** @return array<string, mixed>[] */
    public function recordedFacts(): array { return $this->recordedFacts; }

    /**
     * Merge all recorded facts into a single map for BriefMerger.
     *
     * @return array<string, mixed>
     */
    public function mergedRecordedFacts(): array
    {
        $merged = [];
        foreach ($this->recordedFacts as $set) {
            foreach ($set as $k => $v) {
                if ($v !== null && $v !== '') {
                    $merged[$k] = $v;
                }
            }
        }
        return $merged;
    }
}
