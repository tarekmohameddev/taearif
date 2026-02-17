<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Communication\WhatsApp;

use App\Domain\Communication\WhatsApp\Services\WhatsAppTemplateService;
use App\Models\User;
use App\Models\WaTemplate;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class WhatsAppTemplateRenderingTest extends TestCase
{
    use DatabaseTransactions;

    private function requireTables(): void
    {
        if (! Schema::hasTable('wa_templates')) {
            $this->markTestSkipped('wa_templates table required.');
        }
    }

    /** @test */
    public function render_content_replaces_variables(): void
    {
        $this->requireTables();

        $user = User::factory()->create();
        $template = WaTemplate::create([
            'user_id' => $user->id,
            'name' => 'greeting',
            'content' => 'Hello {{name}}, your code is {{code}}.',
            'category' => 'utility',
            'is_active' => true,
        ]);

        $service = app(WhatsAppTemplateService::class);
        $rendered = $service->renderContent($template, [
            'name' => 'Ahmed',
            'code' => '12345',
        ]);

        $this->assertSame('Hello Ahmed, your code is 12345.', $rendered);
    }

    /** @test */
    public function render_content_returns_original_when_no_variables(): void
    {
        $this->requireTables();

        $user = User::factory()->create();
        $template = WaTemplate::create([
            'user_id' => $user->id,
            'name' => 'static',
            'content' => 'Static message only.',
            'category' => 'utility',
            'is_active' => true,
        ]);

        $service = app(WhatsAppTemplateService::class);
        $rendered = $service->renderContent($template, []);

        $this->assertSame('Static message only.', $rendered);
    }
}
