<?php

declare(strict_types=1);

namespace Tests\Unit\Bot;

use App\Domain\Communication\WhatsApp\Bot\BotOrchestrator;
use ReflectionClass;
use Tests\TestCase;

final class HasResultsGuardTest extends TestCase
{
    public function test_detects_arabic_no_results_denial(): void
    {
        $method = (new ReflectionClass(BotOrchestrator::class))->getMethod('replyDeniesPropertyResults');
        $method->setAccessible(true);
        $orchestrator = $this->app->make(BotOrchestrator::class);

        $this->assertTrue($method->invoke($orchestrator, 'ما لقيت نتائج مطابقة الآن. ممكن نوسع البحث؟'));
        $this->assertTrue($method->invoke($orchestrator, 'للأسف ما لقيت شي مناسب حالياً'));
        $this->assertFalse($method->invoke($orchestrator, 'لقيت عقار مناسب في جدة بسعر 7,000,000 ريال'));
    }

    public function test_builds_single_property_reply_with_title_and_price(): void
    {
        $method = (new ReflectionClass(BotOrchestrator::class))->getMethod('buildFoundPropertiesReply');
        $method->setAccessible(true);
        $orchestrator = $this->app->make(BotOrchestrator::class);

        $reply = $method->invoke($orchestrator, [[
            'id'       => 1301,
            'title'    => 'عمارة للبيع في جدة',
            'price'    => 7000000,
            'area_sqm' => '524.00',
            'address'  => 'حي الفيصلية',
        ]]);

        $this->assertStringContainsString('عمارة للبيع في جدة', $reply);
        $this->assertStringContainsString('7,000,000', $reply);
        $this->assertStringContainsString('524.00', $reply);
        $this->assertStringNotContainsString('ما لقيت نتائج', $reply);
    }

    public function test_builds_location_relaxed_reply_with_disclosure(): void
    {
        $method = (new ReflectionClass(BotOrchestrator::class))->getMethod('buildFoundPropertiesReply');
        $method->setAccessible(true);
        $orchestrator = $this->app->make(BotOrchestrator::class);

        $reply = $method->invoke(
            $orchestrator,
            [[
                'id'       => 1301,
                'title'    => 'عمارة للبيع في جدة',
                'price'    => 7000000,
                'area_sqm' => '524.00',
                'address'  => 'حي الفيصلية، جدة',
            ]],
            [
                'location_relaxed'   => true,
                'requested_city'     => 'الرياض',
                'requested_location' => 'الرياض',
            ]
        );

        $this->assertStringContainsString('ما لقيت في الرياض', $reply);
        $this->assertStringContainsString('عمارة للبيع في جدة', $reply);
        $this->assertStringContainsString('7,000,000', $reply);
    }

    public function test_prefixes_location_relax_when_llm_omits_disclosure(): void
    {
        $disclose = (new ReflectionClass(BotOrchestrator::class))->getMethod('replyDisclosesLocationRelax');
        $disclose->setAccessible(true);
        $prefix = (new ReflectionClass(BotOrchestrator::class))->getMethod('prefixLocationRelaxDisclosure');
        $prefix->setAccessible(true);
        $orchestrator = $this->app->make(BotOrchestrator::class);

        $this->assertFalse($disclose->invoke($orchestrator, 'لقيت عقار مناسب في جدة بسعر 7,000,000 ريال'));
        $this->assertTrue($disclose->invoke($orchestrator, 'ما لقيت في الرياض الحين، لكن عندي خيار في جدة'));

        $labeled = $prefix->invoke($orchestrator, 'لقيت عقار في جدة.', [
            'requested_location' => 'الرياض',
        ]);
        $this->assertStringStartsWith('ما لقيت في الرياض', $labeled);
        $this->assertStringContainsString('لقيت عقار في جدة', $labeled);
    }
}
