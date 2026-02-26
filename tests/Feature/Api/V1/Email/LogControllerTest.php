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

class LogControllerTest extends TestCase
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
    public function test_it_can_fetch_email_logs(): void
    {
        $this->requireEmailTables();

        $tenant = $this->createTenant();
        $campaign = EmailCampaign::create([
            'user_id' => $tenant->id,
            'name' => 'Log Campaign',
            'subject' => 'S',
            'body_html' => '<p>B</p>',
            'status' => 'draft',
        ]);
        EmailMessageLog::create([
            'user_id' => $tenant->id,
            'campaign_id' => $campaign->id,
            'recipient_email' => 'log@example.com',
            'subject' => 'S',
            'body_html' => '<p>B</p>',
            'status' => 'pending',
        ]);

        Sanctum::actingAs($tenant);
        $res = $this->getJson('/api/v1/email/logs')->assertOk();

        $res->assertJsonStructure(['data' => ['data', 'current_page', 'per_page', 'total']]);
        $items = $res->json('data.data');
        $this->assertNotNull($items);
        $this->assertGreaterThanOrEqual(1, count($items));
        $first = collect($items)->firstWhere('recipient_email', 'log@example.com');
        $this->assertNotNull($first);
        $this->assertSame('pending', $first['status']);
    }

    /** @test */
    public function test_it_filters_logs_by_campaign_id(): void
    {
        $this->requireEmailTables();

        $tenant = $this->createTenant();
        $campaign1 = EmailCampaign::create([
            'user_id' => $tenant->id,
            'name' => 'C1',
            'subject' => 'S1',
            'body_html' => '<p>B1</p>',
            'status' => 'draft',
        ]);
        $campaign2 = EmailCampaign::create([
            'user_id' => $tenant->id,
            'name' => 'C2',
            'subject' => 'S2',
            'body_html' => '<p>B2</p>',
            'status' => 'draft',
        ]);
        EmailMessageLog::create([
            'user_id' => $tenant->id,
            'campaign_id' => $campaign1->id,
            'recipient_email' => 'c1@example.com',
            'subject' => 'S1',
            'body_html' => '<p>B1</p>',
            'status' => 'sent',
        ]);
        EmailMessageLog::create([
            'user_id' => $tenant->id,
            'campaign_id' => $campaign2->id,
            'recipient_email' => 'c2@example.com',
            'subject' => 'S2',
            'body_html' => '<p>B2</p>',
            'status' => 'pending',
        ]);

        Sanctum::actingAs($tenant);
        $res = $this->getJson('/api/v1/email/logs?campaign_id=' . $campaign1->id)->assertOk();
        $items = $res->json('data.data');
        $this->assertNotNull($items);
        foreach ($items as $item) {
            $this->assertSame((int) $campaign1->id, (int) $item['campaign_id']);
        }
    }

    /** @test */
    public function test_it_filters_logs_by_status(): void
    {
        $this->requireEmailTables();

        $tenant = $this->createTenant();
        $campaign = EmailCampaign::create([
            'user_id' => $tenant->id,
            'name' => 'Status Filter',
            'subject' => 'S',
            'body_html' => '<p>B</p>',
            'status' => 'draft',
        ]);
        EmailMessageLog::create([
            'user_id' => $tenant->id,
            'campaign_id' => $campaign->id,
            'recipient_email' => 'pending@example.com',
            'subject' => 'S',
            'body_html' => '<p>B</p>',
            'status' => 'pending',
        ]);
        EmailMessageLog::create([
            'user_id' => $tenant->id,
            'campaign_id' => $campaign->id,
            'recipient_email' => 'sent@example.com',
            'subject' => 'S',
            'body_html' => '<p>B</p>',
            'status' => 'sent',
        ]);

        Sanctum::actingAs($tenant);
        $res = $this->getJson('/api/v1/email/logs?status=pending')->assertOk();
        $items = $res->json('data.data');
        $this->assertNotNull($items);
        foreach ($items as $item) {
            $this->assertSame('pending', $item['status']);
        }
    }
}
