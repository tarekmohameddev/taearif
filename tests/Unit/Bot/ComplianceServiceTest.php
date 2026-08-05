<?php

declare(strict_types=1);

namespace Tests\Unit\Bot;

use App\Domain\Communication\WhatsApp\Bot\ComplianceService;
use App\Models\WaConversationAiState;
use Tests\TestCase;

final class ComplianceServiceTest extends TestCase
{
    private ComplianceService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ComplianceService();
    }

    private function fakeState(array $attrs = []): WaConversationAiState
    {
        $state = new WaConversationAiState();
        $state->disclosed_as_assistant = $attrs['disclosed_as_assistant'] ?? false;
        $state->opt_out_status = $attrs['opt_out_status'] ?? 'active';
        return $state;
    }

    public function test_detects_opt_out_arabic(): void
    {
        $state = $this->fakeState();
        $result = $this->service->check('إيقاف الرسائل من فضلك', $state, false);
        $this->assertSame('opt_out', $result['action']);
    }

    public function test_detects_opt_out_english(): void
    {
        $state = $this->fakeState();
        $result = $this->service->check('STOP', $state, false);
        $this->assertSame('opt_out', $result['action']);
    }

    public function test_detects_regulated_topic(): void
    {
        $state = $this->fakeState();
        $result = $this->service->check('أريد معلومات عن التمويل العقاري', $state, false);
        $this->assertSame('handoff', $result['action']);
    }

    public function test_proceeds_for_normal_message(): void
    {
        $state = $this->fakeState(['disclosed_as_assistant' => true]);
        $result = $this->service->check('أريد شقة في الرياض', $state, false);
        $this->assertSame('proceed', $result['action']);
    }

    public function test_triggers_disclosure_on_first_contact_when_enabled(): void
    {
        $state = $this->fakeState(['disclosed_as_assistant' => false]);
        $result = $this->service->check('مرحبا', $state, true, true);
        $this->assertSame('disclosure', $result['action']);
    }

    public function test_skips_disclosure_when_disabled_in_config(): void
    {
        $state = $this->fakeState(['disclosed_as_assistant' => false]);
        $result = $this->service->check('مرحبا', $state, true, false);
        $this->assertSame('proceed', $result['action']);
    }

    public function test_skips_disclosure_when_not_first_contact(): void
    {
        $state = $this->fakeState(['disclosed_as_assistant' => false]);
        $result = $this->service->check('الرياض', $state, false, true);
        $this->assertSame('proceed', $result['action']);
    }

    public function test_human_request_keyword_detected(): void
    {
        $this->assertTrue($this->service->isHumanRequestKeyword('تحدث مع موظف'));
        $this->assertTrue($this->service->isHumanRequestKeyword('human'));
        $this->assertFalse($this->service->isHumanRequestKeyword('أريد شقة'));
        // "مدير فرع" = customer's branch manager, not a handoff request
        $this->assertFalse($this->service->isHumanRequestKeyword('عندي مدير فرع يبغى غرفه مفروشه لعمال'));
        $this->assertTrue($this->service->isHumanRequestKeyword('ابي اتحدث مع المدير'));
    }
}