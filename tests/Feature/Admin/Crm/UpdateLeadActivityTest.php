<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Crm;

use App\Domain\Crm\Models\Lead;
use App\Domain\Crm\Models\LeadActivity;
use Tests\Feature\Admin\AdminApiTestCase;

class UpdateLeadActivityTest extends AdminApiTestCase
{
    /** @test */
    public function admin_can_update_a_lead_activity(): void
    {
        $lead = Lead::factory()->create();
        $activity = LeadActivity::factory()->create([
            'lead_id' => $lead->id,
            'type' => 'note',
            'description' => 'Initial note',
        ]);

        $this->signInAdmin();

        $payload = [
            'type' => 'call',
            'description' => 'Follow-up call scheduled',
        ];

        $response = $this->putJson(
            route('admin.api.crm.leads.activities.update', [$lead->uuid, $activity->id]),
            $payload
        );

        $response->assertOk()
            ->assertJsonPath('data.type', 'call')
            ->assertJsonPath('data.description', 'Follow-up call scheduled')
            ->assertJsonPath('data.is_completed', false);

        $this->assertDatabaseHas('lead_activities', [
            'id' => $activity->id,
            'type' => 'call',
            'description' => 'Follow-up call scheduled',
        ]);
    }

    /** @test */
    public function validation_errors_are_returned_for_invalid_activity_payload(): void
    {
        $lead = Lead::factory()->create();
        $activity = LeadActivity::factory()->create([
            'lead_id' => $lead->id,
        ]);

        $this->signInAdmin();

        $response = $this->putJson(
            route('admin.api.crm.leads.activities.update', [$lead->uuid, $activity->id]),
            [
                'type' => 'invalid',
            ]
        );

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['type']);
    }

    /** @test */
    public function unauthenticated_requests_are_rejected(): void
    {
        $lead = Lead::factory()->create();
        $activity = LeadActivity::factory()->create([
            'lead_id' => $lead->id,
        ]);

        $this->putJson(
            route('admin.api.crm.leads.activities.update', [$lead->uuid, $activity->id]),
            [
                'description' => 'Unauthorized update',
            ]
        )->assertUnauthorized();
    }

    /** @test */
    public function not_found_is_returned_when_activity_does_not_exist(): void
    {
        $lead = Lead::factory()->create();

        $this->signInAdmin();

        $response = $this->putJson(
            route('admin.api.crm.leads.activities.update', [$lead->uuid, 999999]),
            [
                'description' => 'Missing activity',
            ]
        );

        $response->assertNotFound()
            ->assertJsonPath('code', 'NOT_FOUND');
    }
}

