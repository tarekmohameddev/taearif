<?php

declare(strict_types=1);

namespace Tests\Feature\Agent;

use App\Domain\Ai\Agent\DTOs\AgentMessage;
use App\Domain\Ai\Agent\DTOs\AgentStepRequest;
use App\Domain\Ai\Agent\DTOs\AgentStepResult;
use App\Domain\Ai\Agent\DTOs\ToolCall;
use App\Domain\Ai\Agent\Runtime\AgentLoop;
use App\Domain\Ai\Agent\Runtime\StepBudget;
use App\Domain\Ai\Agent\Runtime\ToolRegistry;
use App\Domain\Ai\Agent\Schema\SchemaValidator;
use App\Domain\Communication\WhatsApp\Bot\ComplianceService;
use App\Domain\RealEstateAgent\Leads\PortalLeadParser;
use App\Domain\RealEstateAgent\Safety\CitationGuard;
use App\Domain\RealEstateAgent\Safety\FactLedger;
use App\Domain\RealEstateAgent\Safety\GroundingPolicy;
use App\Domain\RealEstateAgent\Safety\HandoffGuard;
use App\Domain\RealEstateAgent\Safety\NumberProvenance;
use App\Domain\RealEstateAgent\Safety\ReplyRenderer;
use App\Domain\RealEstateAgent\Safety\RepetitionGuard;
use App\Domain\RealEstateAgent\State\BriefMerger;
use App\Domain\RealEstateAgent\State\CustomerBrief;
use Tests\TestCase;

/**
 * Deterministic unit tests for the agent runtime.
 *
 * All tests use stub transports — zero network.
 */
final class AgentLoopTest extends TestCase
{
    /** Build a valid brief_updates payload with all required fields set to null. */
    private static function emptyBriefUpdates(): array
    {
        return [
            'city' => null, 'district' => null, 'property_type' => null,
            'purpose' => null, 'budget_min' => null, 'budget_max' => null,
            'bedrooms' => null, 'bathrooms' => null, 'area_min' => null,
            'area_max' => null, 'furnished' => null, 'intent' => null,
        ];
    }

    // ── SchemaValidator ────────────────────────────────────────────────────────

    public function test_schema_validator_passes_valid_reply(): void
    {
        $schema = \App\Domain\Ai\Agent\Schema\JsonSchema::agentReplySchema();
        $reply  = [
            'say'               => 'مرحباً!',
            'cited_properties'  => [],
            'cited_knowledge'   => [],
            'brief_updates'     => self::emptyBriefUpdates(),
            'confidence'        => 90,
            'needs_human'       => false,
        ];
        $errors = SchemaValidator::validate($reply, $schema);
        $this->assertEmpty($errors, 'Valid reply should have no schema errors');
    }

    public function test_schema_validator_catches_missing_required_field(): void
    {
        $schema = \App\Domain\Ai\Agent\Schema\JsonSchema::agentReplySchema();
        $reply  = [
            'say'              => 'مرحباً!',
            'cited_properties' => [],
            // 'cited_knowledge' missing
            'brief_updates'    => self::emptyBriefUpdates(),
            'confidence'       => 90,
            'needs_human'      => false,
        ];
        $errors = SchemaValidator::validate($reply, $schema);
        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('cited_knowledge', $errors[0]);
    }

    public function test_schema_validator_catches_wrong_type(): void
    {
        $schema = \App\Domain\Ai\Agent\Schema\JsonSchema::agentReplySchema();
        $reply  = [
            'say'              => 'مرحباً!',
            'cited_properties' => [],
            'cited_knowledge'  => [],
            'brief_updates'    => self::emptyBriefUpdates(),
            'confidence'       => 'high',  // wrong type: string instead of integer
            'needs_human'      => false,
        ];
        $errors = SchemaValidator::validate($reply, $schema);
        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('confidence', $errors[0]);
    }

    // ── CitationGuard ──────────────────────────────────────────────────────────

    public function test_citation_guard_passes_when_property_in_ledger(): void
    {
        $ledger = new FactLedger();
        $ledger->addProperties([['id' => 1301, 'title' => 'شقة', 'price' => 500000, 'area_sqm' => 120, 'address' => 'الرياض']]);

        $reply  = [
            'say'              => 'شوف {{p:1301|title}} بـ {{p:1301|price}}',
            'cited_properties' => [1301],
            'cited_knowledge'  => [],
            'brief_updates'    => [],
            'confidence'       => 85,
            'needs_human'      => false,
        ];

        $guard      = new CitationGuard();
        $violations = $guard->check($reply, $ledger);
        $this->assertEmpty($violations);
    }

    public function test_citation_guard_fails_when_property_not_in_ledger(): void
    {
        $ledger = new FactLedger();

        $reply  = [
            'say'              => 'عندنا {{p:9999|price}} من أفضل الخيارات',
            'cited_properties' => [9999],
            'cited_knowledge'  => [],
            'brief_updates'    => [],
            'confidence'       => 85,
            'needs_human'      => false,
        ];

        $guard      = new CitationGuard();
        $violations = $guard->check($reply, $ledger);
        $this->assertNotEmpty($violations);
    }

    public function test_citation_guard_fails_on_bare_price(): void
    {
        $ledger = new FactLedger();

        $reply  = [
            'say'              => 'السعر 500000 ريال',
            'cited_properties' => [],
            'cited_knowledge'  => [],
            'brief_updates'    => [],
            'confidence'       => 85,
            'needs_human'      => false,
        ];

        $guard      = new CitationGuard();
        $violations = $guard->check($reply, $ledger);
        $this->assertNotEmpty($violations);
        $this->assertStringContainsString('500000', $violations[0]);
    }

    public function test_citation_guard_allows_bedroom_counts(): void
    {
        $ledger = new FactLedger();
        $ledger->addProperties([['id' => 1, 'price' => 300000, 'area_sqm' => 100, 'title' => 'شقة', 'address' => 'جدة']]);

        $reply  = [
            'say'              => 'الشقة بها 3 غرف نوم وهي في {{p:1|address}}',
            'cited_properties' => [1],
            'cited_knowledge'  => [],
            'brief_updates'    => [],
            'confidence'       => 90,
            'needs_human'      => false,
        ];

        $guard      = new CitationGuard();
        $violations = $guard->check($reply, $ledger);
        // 3 is 1 digit, should not trigger the ≥4-digit rule
        $this->assertEmpty($violations);
    }

    public function test_citation_guard_catches_availability_claim_on_empty_ledger(): void
    {
        $ledger = new FactLedger();
        $ledger->recordSearchRun(hasResults: false);

        $reply  = [
            'say'              => 'عندنا عقارات رائعة في الرياض',
            'cited_properties' => [],
            'cited_knowledge'  => [],
            'brief_updates'    => [],
            'confidence'       => 80,
            'needs_human'      => false,
        ];

        $guard      = new CitationGuard();
        $violations = $guard->check($reply, $ledger);
        $this->assertNotEmpty($violations);
        $this->assertStringContainsString('عندنا', $violations[0]);
    }

    // ── ReplyRenderer ──────────────────────────────────────────────────────────

    public function test_renderer_substitutes_price_placeholder(): void
    {
        $ledger = new FactLedger();
        $ledger->addProperties([['id' => 1301, 'price' => 500000, 'title' => 'شقة جميلة', 'address' => 'جدة', 'area_sqm' => 120]]);

        $renderer = new ReplyRenderer();
        $rendered = $renderer->render('السعر هو {{p:1301|price}} للشقة', $ledger);

        $this->assertStringContainsString('500,000', $rendered);
        $this->assertStringContainsString('ريال', $rendered);
        $this->assertStringNotContainsString('{{', $rendered);
    }

    public function test_renderer_removes_knowledge_placeholders(): void
    {
        $ledger   = new FactLedger();
        $renderer = new ReplyRenderer();
        $rendered = $renderer->render('كما ذكرنا {{k:chunk_abc}} يمكنك التواصل معنا', $ledger);

        $this->assertStringNotContainsString('{{k:', $rendered);
        $this->assertStringContainsString('يمكنك التواصل معنا', $rendered);
    }

    // ── BriefMerger ───────────────────────────────────────────────────────────

    public function test_brief_merger_preserves_existing_city(): void
    {
        $brief  = new CustomerBrief(city: 'الرياض');
        $merger = new BriefMerger();

        // Update that doesn't include city — city must be preserved
        $after = $merger->merge($brief, ['bedrooms' => 3]);
        $this->assertSame('الرياض', $after->city);
    }

    public function test_brief_merger_updates_budget_max(): void
    {
        $brief  = new CustomerBrief();
        $merger = new BriefMerger();

        $after = $merger->merge($brief, ['budget_max' => 700000]);
        $this->assertSame(700000.0, $after->budgetMax);
    }

    public function test_brief_merger_rejects_invalid_type(): void
    {
        $brief  = new CustomerBrief();
        $merger = new BriefMerger();

        $after = $merger->merge($brief, ['property_type' => 'castle']);  // not in allowed enum
        $this->assertNull($after->propertyType);
    }

    // ── AgentLoop with stub transport ─────────────────────────────────────────

    public function test_agent_loop_returns_final_reply_without_tool_calls(): void
    {
        $emptyBriefUpdates = self::emptyBriefUpdates();
        $stub = new class($emptyBriefUpdates) implements \App\Domain\Ai\Agent\Contracts\AgentTransport {
            public function __construct(private readonly array $briefUpdates) {}
            public function step(AgentStepRequest $req): AgentStepResult {
                return new AgentStepResult(
                    toolCalls:  null,
                    finalReply: [
                        'say'              => 'مرحباً! كيف أساعدك؟',
                        'cited_properties' => [],
                        'cited_knowledge'  => [],
                        'brief_updates'    => $this->briefUpdates,
                        'confidence'       => 95,
                        'needs_human'      => false,
                    ],
                    tokensIn:  50,
                    tokensOut: 30,
                    latencyMs: 0,
                    model:     'stub',
                    provider:  'stub',
                );
            }
        };

        $registry = new ToolRegistry([]);
        $loop     = new AgentLoop($stub, $registry);
        $budget   = new StepBudget(maxSteps: 4, maxCompletionTokens: PHP_INT_MAX, wallClockMs: 30_000);

        $messages  = [AgentMessage::system('test'), AgentMessage::user('مرحبا')];
        $result    = $loop->run($messages, 1, 'stub', $budget, 100);

        $this->assertTrue($result->succeeded());
        $this->assertSame('مرحباً! كيف أساعدك؟', $result->finalReply['say']);
    }

    public function test_agent_loop_dispatches_tool_then_final_reply(): void
    {
        $emptyBriefUpdates = self::emptyBriefUpdates();
        $callCount = 0;
        $stub = new class($callCount, $emptyBriefUpdates) implements \App\Domain\Ai\Agent\Contracts\AgentTransport {
            public int $count = 0;
            public function __construct(int &$ref, private readonly array $briefUpdates) { $this->count = &$ref; }
            public function step(AgentStepRequest $req): AgentStepResult {
                $this->count++;
                if ($this->count === 1) {
                    return new AgentStepResult(
                        toolCalls: [new ToolCall('call_1', 'search_inventory', ['location' => 'الرياض'])],
                        finalReply: null, tokensIn: 40, tokensOut: 10, latencyMs: 0, model: 'stub', provider: 'stub',
                    );
                }
                return new AgentStepResult(
                    toolCalls: null,
                    finalReply: [
                        'say' => 'وجدت لك {{p:1|title}}', 'cited_properties' => [1],
                        'cited_knowledge' => [], 'brief_updates' => $this->briefUpdates, 'confidence' => 85, 'needs_human' => false,
                    ],
                    tokensIn: 60, tokensOut: 40, latencyMs: 0, model: 'stub', provider: 'stub',
                );
            }
        };

        $searchTool = new class implements \App\Domain\Ai\Agent\Contracts\AgentTool {
            public function name(): string { return 'search_inventory'; }
            public function schema(): array { return ['name' => 'search_inventory', 'description' => '', 'parameters' => ['type' => 'object', 'properties' => []]]; }
            public function execute(array $args, int $tenantId): array {
                return ['results' => [['id' => 1, 'title' => 'شقة الرياض', 'price' => 300000, 'area_sqm' => 100, 'address' => 'الرياض']], 'count' => 1];
            }
        };

        $registry = new ToolRegistry([$searchTool]);
        $loop     = new AgentLoop($stub, $registry);
        $budget   = new StepBudget(maxSteps: 4, maxCompletionTokens: PHP_INT_MAX, wallClockMs: 30_000);
        $messages = [AgentMessage::system('test'), AgentMessage::user('ابي شقة بالرياض')];
        $result   = $loop->run($messages, 1, 'stub', $budget, 100);

        $this->assertTrue($result->succeeded());
        $this->assertCount(1, $result->toolCallLog);
        $this->assertSame('search_inventory', $result->toolCallLog[0]['name']);
    }

    public function test_agent_loop_exhausts_budget_gracefully(): void
    {
        $stub = new class implements \App\Domain\Ai\Agent\Contracts\AgentTransport {
            public function step(AgentStepRequest $req): AgentStepResult {
                // Always returns tool calls — never finishes
                return new AgentStepResult(
                    toolCalls: [new ToolCall('call_x', 'search_inventory', [])],
                    finalReply: null, tokensIn: 10, tokensOut: 5, latencyMs: 0, model: 'stub', provider: 'stub',
                );
            }
        };

        $searchTool = new class implements \App\Domain\Ai\Agent\Contracts\AgentTool {
            public function name(): string { return 'search_inventory'; }
            public function schema(): array { return ['name' => 'search_inventory', 'description' => '', 'parameters' => ['type' => 'object', 'properties' => []]]; }
            public function execute(array $args, int $tenantId): array { return ['results' => [], 'count' => 0]; }
        };

        $registry = new ToolRegistry([$searchTool]);
        $loop     = new AgentLoop($stub, $registry);
        $budget   = new StepBudget(maxSteps: 2, maxCompletionTokens: PHP_INT_MAX, wallClockMs: 30_000);
        $messages = [AgentMessage::user('ابي شقة')];
        $result   = $loop->run($messages, 1, 'stub', $budget, 100);

        $this->assertFalse($result->succeeded());
        $this->assertSame('budget_exhausted', $result->failureReason);
    }

    // ── StepBudget ─────────────────────────────────────────────────────────────

    public function test_step_budget_tracks_completion_tokens_only(): void
    {
        // RC2: prompt tokens must NOT count toward exhaustion
        $budget = new StepBudget(maxSteps: 10, maxCompletionTokens: 100, wallClockMs: 60_000);

        // Record a step with large prompt tokens but small completion tokens
        $budget->recordStep(tokensIn: 5000, tokensOut: 50, latencyMs: 100);
        $this->assertFalse($budget->isExhausted(), 'Large prompt tokens must not exhaust budget');
        $this->assertSame(50, $budget->completionTokens());
        $this->assertSame(5000, $budget->promptTokens());

        // One more step that pushes completion tokens over the ceiling
        $budget->recordStep(tokensIn: 100, tokensOut: 55, latencyMs: 100);
        $this->assertTrue($budget->isExhausted(), 'Completion tokens over ceiling should exhaust');
    }

    public function test_step_budget_on_last_step(): void
    {
        $budget = new StepBudget(maxSteps: 3, maxCompletionTokens: PHP_INT_MAX, wallClockMs: 60_000);

        $budget->recordStep(10, 10, 0);
        $this->assertFalse($budget->onLastStep()); // step 1 of 3

        $budget->recordStep(10, 10, 0);
        $this->assertTrue($budget->onLastStep());  // step 2 of 3 (last before max)

        $budget->recordStep(10, 10, 0);
        $this->assertFalse($budget->onLastStep()); // step 3 — already past last
        $this->assertTrue($budget->isExhausted());
    }

    // ── PortalLeadParser ───────────────────────────────────────────────────────

    public function test_portal_lead_parser_detects_aqar_template(): void
    {
        $parser = new PortalLeadParser();
        $msg    = 'السلام عليكم أرغب في التواصل مع المعلن على تطبيق عقار بخصوص الإعلان: شقة للإيجار في شارع الموازيني, حي الواحة, مدينة جدة, منطقة مكة المكرمة بسعر 32000.00 ريال https://sa.aqar.fm/ad/6633737/ar?a_id=7e8';

        $result = $parser->parse($msg);

        $this->assertTrue($result['is_portal_lead']);
        $this->assertSame('aqar', $result['platform']);
        $this->assertSame('6633737', $result['ad_id']);
        $this->assertSame('rent', $result['purpose']);
        $this->assertSame('شقة', $result['property_type_ar']);
        $this->assertSame('جدة', $result['city']);
        $this->assertSame('الواحة', $result['district']);
        $this->assertEqualsWithDelta(32000.0, $result['price'], 0.01);
    }

    public function test_portal_lead_parser_rejects_normal_message(): void
    {
        $parser = new PortalLeadParser();
        $result = $parser->parse('ابغى شقة في الرياض');

        $this->assertFalse($result['is_portal_lead']);
    }

    public function test_portal_lead_parser_is_portal_lead_shortcut(): void
    {
        $parser = new PortalLeadParser();
        $this->assertTrue($parser->isPortalLead('أرغب في التواصل مع المعلن على تطبيق بيوت'));
        $this->assertFalse($parser->isPortalLead('كم الإيجار للشقة؟'));
    }

    // ── NumberProvenance ───────────────────────────────────────────────────────

    public function test_number_provenance_allows_customer_supplied_numbers(): void
    {
        $ledger   = new FactLedger();
        $history  = [
            AgentMessage::user('ميزانيتي 400,000 ريال'),
        ];

        $provenance = new NumberProvenance();
        $allowed    = $provenance->buildAllowedSet($ledger, $history);

        $this->assertContains('400000', $allowed);
    }

    public function test_number_provenance_excludes_ledger_prices(): void
    {
        // Ledger values must NOT be in the allowed set — the model is required to
        // cite them via {{p:ID|field}} placeholders so the CitationGuard enforces
        // the placeholder protocol. Including ledger values here lets the model
        // bypass placeholders entirely (the bug seen in conv #224 turn 2).
        $ledger = new FactLedger();
        $ledger->addProperties([['id' => 1, 'price' => 500000.0, 'area_sqm' => 120, 'bedrooms' => 3]]);

        $provenance = new NumberProvenance();
        $allowed    = $provenance->buildAllowedSet($ledger, []);

        $this->assertNotContains('500000', $allowed);
        $this->assertNotContains('120', $allowed);
        $this->assertEmpty($allowed, 'No ledger values should be in the allowed set when history has no customer messages');
    }

    public function test_number_provenance_normalises_arabic_indic(): void
    {
        $provenance = new NumberProvenance();
        $this->assertSame('32000', $provenance->normaliseArabicIndic('٣٢٠٠٠'));
    }

    // ── CitationGuard (updated) ────────────────────────────────────────────────

    public function test_citation_guard_allows_customer_number_in_reply(): void
    {
        $ledger  = new FactLedger();
        $guard   = new CitationGuard();
        $reply   = ['say' => 'نعم، ميزانية 400000 معقولة', 'cited_properties' => [], 'cited_knowledge' => [], 'brief_updates' => [], 'confidence' => 80, 'needs_human' => false];
        $allowed = ['400000']; // customer supplied this number

        $violations = $guard->check($reply, $ledger, $allowed);

        $this->assertEmpty($violations, 'Customer-supplied number must not be flagged');
    }

    public function test_citation_guard_flags_non_numeric_placeholder(): void
    {
        $ledger = new FactLedger();
        $guard  = new CitationGuard();
        $reply  = ['say' => 'السعر {{p:ID|price}} ريال', 'cited_properties' => [], 'cited_knowledge' => [], 'brief_updates' => [], 'confidence' => 80, 'needs_human' => false];

        $violations = $guard->check($reply, $ledger, []);

        $this->assertNotEmpty($violations);
        $this->assertStringContainsString('Non-numeric placeholder ID', $violations[0]);
    }

    public function test_citation_guard_allows_ref_number_prefix(): void
    {
        $ledger = new FactLedger();
        $guard  = new CitationGuard();
        $reply  = ['say' => 'رقم الإعلان 7200918504 متاح', 'cited_properties' => [], 'cited_knowledge' => [], 'brief_updates' => [], 'confidence' => 80, 'needs_human' => false];

        $violations = $guard->check($reply, $ledger, []);

        $this->assertEmpty($violations, 'Reference number with proper prefix must not be flagged');
    }

    // ── HandoffGuard ───────────────────────────────────────────────────────────

    public function test_handoff_guard_rejects_portal_lead_escalation(): void
    {
        $guard      = new HandoffGuard();
        $ledger     = new FactLedger();
        $brief      = new CustomerBrief();
        $compliance = new ComplianceService();
        $portalText = 'السلام عليكم أرغب في التواصل مع المعلن على تطبيق عقار بخصوص الإعلان: شقة للإيجار';

        $evidenced = $guard->isEvidenced('customer_request', $portalText, $ledger, [], $brief, $compliance);

        $this->assertFalse($evidenced, 'Portal lead template must not be treated as human request');
    }

    public function test_handoff_guard_accepts_explicit_human_keyword(): void
    {
        $guard      = new HandoffGuard();
        $ledger     = new FactLedger();
        $brief      = new CustomerBrief();
        $compliance = new ComplianceService();

        $evidenced = $guard->isEvidenced('customer_request', 'ابغى أتكلم مع موظف حقيقي', $ledger, [], $brief, $compliance);

        $this->assertTrue($evidenced, 'Explicit human request keyword must pass HandoffGuard');
    }

    // ── GroundingPolicy ────────────────────────────────────────────────────────

    public function test_grounding_policy_triggers_when_no_search_and_inventory_intent(): void
    {
        $policy = new GroundingPolicy();
        $ledger = new FactLedger();
        $reply  = ['brief_updates' => ['intent' => 'search']];

        $this->assertTrue($policy->needsForcedSearch('ابي شقة في الرياض', $ledger, $reply));
    }

    public function test_grounding_policy_skips_when_search_already_ran(): void
    {
        $policy = new GroundingPolicy();
        $ledger = new FactLedger();
        $ledger->recordSearchRun(false);
        $reply  = ['brief_updates' => ['intent' => 'search']];

        $this->assertFalse($policy->needsForcedSearch('ابي شقة', $ledger, $reply));
    }

    public function test_grounding_policy_skips_when_ledger_has_properties(): void
    {
        $policy = new GroundingPolicy();
        $ledger = new FactLedger();
        $ledger->addProperties([['id' => 1, 'price' => 100000]]);

        $this->assertFalse($policy->needsForcedSearch('بكم الشقة؟', $ledger, []));
    }

    // ── RepetitionGuard ────────────────────────────────────────────────────────

    public function test_repetition_guard_detects_high_similarity(): void
    {
        $guard   = new RepetitionGuard();
        $history = [
            AgentMessage::assistant('أهلاً! كيف أقدر أساعدك اليوم؟'),
            AgentMessage::user('مرحبا'),
            AgentMessage::assistant('أهلاً! كيف أقدر أساعدك اليوم؟'),
        ];

        $this->assertTrue($guard->isTooSimilar('أهلاً! كيف أقدر أساعدك اليوم؟', $history));
    }

    public function test_repetition_guard_allows_different_reply(): void
    {
        $guard   = new RepetitionGuard();
        $history = [
            AgentMessage::assistant('أهلاً! كيف أقدر أساعدك اليوم؟'),
        ];

        $this->assertFalse($guard->isTooSimilar('وجدت لك عدة خيارات في حي النرجس.', $history));
    }

    public function test_repetition_guard_strips_boilerplate(): void
    {
        $guard = new RepetitionGuard();
        $text  = 'يمكنك الاطلاع على الخيارات المتاحة. أنا هنا للمساعدة!';
        $stripped = $guard->stripBoilerplate($text);

        $this->assertStringNotContainsString('أنا هنا للمساعدة', $stripped);
        $this->assertStringContainsString('يمكنك الاطلاع على الخيارات المتاحة.', $stripped);
    }

    // ── ReplyRenderer placeholder sweep ───────────────────────────────────────

    public function test_reply_renderer_strips_orphaned_placeholders(): void
    {
        $ledger   = new FactLedger();
        $renderer = new ReplyRenderer();
        // Placeholder for a property NOT in the ledger should be removed by the sweep
        $rendered = $renderer->render('السعر {{p:999|price}} ريال فقط', $ledger);
        // The sweep should remove the unresolved {{...}}
        $this->assertStringNotContainsString('{{', $rendered);
    }
}
