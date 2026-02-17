<?php

declare(strict_types=1);

namespace Tests\Feature\V1\Sms;

use App\Models\SmsCampaign;
use App\Models\SmsMessageLog;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SmsStatsApiTest extends TestCase
{
    use DatabaseTransactions;

    private function requireSmsTables(): void
    {
        foreach (['sms_campaigns', 'sms_message_logs'] as $table) {
            if (!Schema::hasTable($table)) {
                $this->markTestSkipped("{$table} table required.");
            }
        }
    }

    /** @test */
    public function stats_returns_aggregates_tenant_scoped(): void
    {
        $this->requireSmsTables();

        $tenant = User::factory()->create(['account_type' => 'tenant', 'tenant_id' => null]);
        SmsCampaign::create([
            'user_id' => $tenant->id,
            'name' => 'C1',
            'message' => 'M1',
            'status' => 'sent',
        ]);
        SmsMessageLog::create([
            'user_id' => $tenant->id,
            'campaign_id' => null,
            'recipient_phone' => '+966501111111',
            'message' => 'Hi',
            'status' => 'sent',
        ]);
        SmsMessageLog::create([
            'user_id' => $tenant->id,
            'campaign_id' => null,
            'recipient_phone' => '+966502222222',
            'message' => 'Hi',
            'status' => 'delivered',
        ]);
        SmsMessageLog::create([
            'user_id' => $tenant->id,
            'campaign_id' => null,
            'recipient_phone' => '+966503333333',
            'message' => 'Hi',
            'status' => 'failed',
        ]);

        Sanctum::actingAs($tenant);
        $res = $this->getJson('/api/v1/sms/stats')->assertOk()->json('data');

        $this->assertSame(1, (int) $res['total_campaigns']);
        $this->assertSame(2, (int) $res['total_sent']); // sent + delivered
        $this->assertSame(1, (int) $res['total_delivered']);
        $this->assertSame(1, (int) $res['total_failed']);
        $this->assertArrayHasKey('delivery_rate', $res);
        $this->assertArrayHasKey('this_month_sent', $res);
    }
}
