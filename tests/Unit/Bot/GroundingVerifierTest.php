<?php

declare(strict_types=1);

namespace Tests\Unit\Bot;

use App\Domain\Communication\WhatsApp\Bot\DTOs\BotReply;
use App\Domain\Communication\WhatsApp\Bot\GroundingVerifier;
use Tests\TestCase;

final class GroundingVerifierTest extends TestCase
{
    private GroundingVerifier $verifier;

    protected function setUp(): void
    {
        parent::setUp();
        $this->verifier = new GroundingVerifier();
    }

    public function test_passes_when_reply_has_no_numeric_claims(): void
    {
        $reply = new BotReply(
            reply: 'يسعدنا خدمتك في أي وقت',
            usedSources: [], confidence: 90, needsHuman: false,
            handoffReason: null, factsUpdate: [], nextQuestion: null
        );
        $result = $this->verifier->verify($reply, '');
        $this->assertTrue($result->passed);
    }

    public function test_passes_when_price_exists_in_context(): void
    {
        $context = 'السعر 850,000 ريال، المساحة 200 م²، 3 غرف نوم';
        $reply = new BotReply(
            reply: 'سعر الوحدة 850,000 ريال ومساحتها 200 م²',
            usedSources: [], confidence: 85, needsHuman: false,
            handoffReason: null, factsUpdate: [], nextQuestion: null
        );
        $result = $this->verifier->verify($reply, $context);
        $this->assertTrue($result->passed);
    }

    public function test_fails_when_invented_price(): void
    {
        $context = 'السعر 500,000 ريال';
        $reply = new BotReply(
            reply: 'سعر الوحدة 750,000 ريال',
            usedSources: [], confidence: 85, needsHuman: false,
            handoffReason: null, factsUpdate: [], nextQuestion: null
        );
        $result = $this->verifier->verify($reply, $context);
        $this->assertFalse($result->passed);
        $this->assertNotEmpty($result->failedClaims);
    }

    public function test_skips_verification_for_handoff_replies(): void
    {
        $reply = BotReply::handoff('low_confidence');
        $result = $this->verifier->verify($reply, '');
        $this->assertTrue($result->passed);
    }

    public function test_style_lint_flags_long_reply(): void
    {
        $longText = str_repeat('هذا نص طويل جداً. ', 40);
        $result = $this->verifier->applyStyleLint($longText);
        $this->assertFalse($result->passed);
        $this->assertContains('reply_too_long', $result->issues);
    }

    public function test_style_lint_flags_markdown_headings(): void
    {
        $text = "## عنوان رئيسي\nمحتوى";
        $result = $this->verifier->applyStyleLint($text);
        $this->assertFalse($result->passed);
        $this->assertContains('markdown_headings_found', $result->issues);
    }

    public function test_style_lint_passes_normal_reply(): void
    {
        $text = 'يسعدنا خدمتك. الوحدة متاحة للإيجار.';
        $result = $this->verifier->applyStyleLint($text);
        $this->assertTrue($result->passed);
    }
}
