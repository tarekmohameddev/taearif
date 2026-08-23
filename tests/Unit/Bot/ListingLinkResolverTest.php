<?php

declare(strict_types=1);

namespace Tests\Unit\Bot;

use App\Domain\Communication\WhatsApp\Bot\ListingLinkResolver;
use Tests\TestCase;

final class ListingLinkResolverTest extends TestCase
{
    private ListingLinkResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resolver = new ListingLinkResolver();
    }

    public function test_it_matches_exact_faq_question(): void
    {
        $faqs = [
            ['question' => 'ما هي ساعات العمل', 'answer' => 'من 9 صباحاً حتى 6 مساءً'],
            ['question' => 'هل يوجد موقف سيارات', 'answer' => 'نعم يوجد موقف'],
        ];

        $answer = $this->resolver->matchFaq($faqs, 'ما هي ساعات العمل');
        $this->assertSame('من 9 صباحاً حتى 6 مساءً', $answer);
    }

    public function test_it_matches_partial_faq_question(): void
    {
        $faqs = [
            ['question' => 'هل يوجد موقف سيارات للسكان', 'answer' => 'نعم موجود'],
        ];

        $answer = $this->resolver->matchFaq($faqs, 'موقف سيارات');
        $this->assertNotNull($answer);
    }

    public function test_it_returns_null_for_unmatched_query(): void
    {
        $faqs = [
            ['question' => 'هل يوجد موقف سيارات', 'answer' => 'نعم'],
        ];

        $answer = $this->resolver->matchFaq($faqs, 'ما هو السعر');
        $this->assertNull($answer);
    }

    public function test_it_returns_null_for_empty_faqs(): void
    {
        $answer = $this->resolver->matchFaq([], 'أي سؤال');
        $this->assertNull($answer);
    }

    public function test_it_formats_external_links(): void
    {
        $links = [
            ['platform' => 'aqar', 'url' => 'https://aqar.com/p/123', 'label' => null],
            ['platform' => 'bayut', 'url' => 'https://bayut.sa/pm/123', 'label' => 'بيوت'],
        ];

        $text = $this->resolver->formatLinksText($links, 'شقة الرياض');
        $this->assertStringContainsString('عقار', $text);
        $this->assertStringContainsString('aqar.com', $text);
        $this->assertStringContainsString('bayut.sa', $text);
    }

    public function test_it_returns_empty_links_text_for_no_links(): void
    {
        $text = $this->resolver->formatLinksText([]);
        $this->assertSame('', $text);
    }

    public function test_persona_rewrite_prompt_is_non_empty(): void
    {
        $persona = new \App\Domain\Communication\WhatsApp\Bot\PersonaBuilder();
        $prompt = $persona->buildRewritePrompt();
        $this->assertNotEmpty($prompt->content);
    }
}
