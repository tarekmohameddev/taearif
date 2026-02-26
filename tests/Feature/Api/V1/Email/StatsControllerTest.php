<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1\Email;

use App\Models\EmailCampaign;
use App\Models\EmailMessageLog;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class StatsControllerTest extends TestCase
{
    use DatabaseTransactions;

    private function requireEmailTables(): void
    {
        foreach (['email_campaigns', 'email_message_logs'] as $table) {
            if (!Schema::hasTable($table)) {
                $this->markTestSkipped("{$table} table required.");
            }
        }
    }

    private function createTenant(): User
    {
        return User::factory()->create(['account_type' => 'tenant', 'tenant_id' => null]);
    }

    /** @test */
    public function test_it_can_fetch_email_statistics(): void
    {
        $this->requireEmailTables();

        $tenant = $this->createTenant();
        EmailCampaign::create([
            'user_id' => $tenant->id,
            'name' => 'Stats Campaign',
            'subject' => 'S',
            'body_html' => '<p>B</p>',
            'status' => 'draft',
        ]);

        Sanctum::actingAs($tenant);
        $res = $this->getJson('/api/v1/email/stats')->assertOk();

        $res->assertJsonStructure([
            'data' => [
                'total_campaigns',
                'total_sent',
                'total_delivered',
                'total_failed',
                'delivery_rate',
                'this_month_sent',
            ],
        ]);
        $this->assertSame(1, $res->json('data.total_campaigns'));
        $this->assertSame(0, $res->json('data.total_sent'));
        $this->assertSame(0.0, $res->json('data.delivery_rate'));
    }
}
