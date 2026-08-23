<?php

declare(strict_types=1);

namespace Tests\Unit\Bot;

use App\Domain\Communication\WhatsApp\Bot\ContextBuilder;
use ReflectionClass;
use Tests\TestCase;

final class GreetingIntentHeuristicTest extends TestCase
{
    public function test_greeting_with_kayfakum_is_general_not_pricing(): void
    {
        $intent = $this->invokePrivate('detectIntentHeuristic', ['هل حياك الله كيفكم وش الاخبار ؟']);
        $this->assertSame('general', $intent);
    }

    public function test_explicit_price_question_is_pricing(): void
    {
        $this->assertSame('pricing', $this->invokePrivate('detectIntentHeuristic', ['كم السعر؟']));
        $this->assertSame('pricing', $this->invokePrivate('detectIntentHeuristic', ['وش التكلفة؟']));
        // Property type present → search intent wins (still correct)
        $this->assertSame('property_search', $this->invokePrivate('detectIntentHeuristic', ['كم سعر الشقة؟']));
    }

    public function test_looks_like_greeting_for_small_talk(): void
    {
        $this->assertTrue($this->invokePrivate('looksLikeGreeting', ['هل حياك الله كيفكم وش الاخبار ؟']));
        $this->assertTrue($this->invokePrivate('looksLikeGreeting', ['السلام عليكم']));
        $this->assertFalse($this->invokePrivate('looksLikeGreeting', ['بدور على عمارة بميزانية 7 مليون']));
    }

    public function test_hayyak_does_not_extract_district_location(): void
    {
        $params = $this->invokePrivate('extractPropertyParams', [
            'هل حياك الله كيفكم وش الاخبار ؟',
            null,
        ]);

        $this->assertArrayNotHasKey('location', $params);
    }

    public function test_real_district_still_extracts(): void
    {
        $params = $this->invokePrivate('extractPropertyParams', [
            'بدور على شقة في حي النرجس',
            null,
        ]);

        $this->assertSame('حي النرجس', $params['location'] ?? null);
    }

    private function invokePrivate(string $method, array $args): mixed
    {
        $ref = new ReflectionClass(ContextBuilder::class);
        $m = $ref->getMethod($method);
        $m->setAccessible(true);

        return $m->invoke($this->app->make(ContextBuilder::class), ...$args);
    }
}
