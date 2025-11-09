<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Crm;

use App\Domain\Crm\Models\Lead;
use Illuminate\Support\Str;
use Tests\Feature\Admin\AdminApiTestCase;

class UpdateLeadTest extends AdminApiTestCase
{
    /** @test */
    public function admin_can_update_a_lead(): void
    {
        $stage = \App\Domain\Crm\Models\AdminCrmCard::query()->first();

        if (!$stage) {
            $this->markTestSkipped('No CRM stages available to assign leads.');
        }

        $lead = Lead::factory()->create([
            'name' => 'Original Lead',
            'status' => 'new',
            'source' => 'manual',
            'stage_id' => $stage->id,
        ]);

        $this->signInAdmin();

        $payload = [
            'name' => 'Updated Lead',
            'status' => 'qualified',
            'source' => 'website',
            'notes' => 'Follow-up scheduled',
        ];

        $response = $this->putJson(
            route('admin.api.crm.leads.update', $lead->uuid),
            $payload
        );

        $response->assertOk()
            ->assertJsonPath('data.name', 'Updated Lead')
            ->assertJsonPath('data.status', 'qualified')
            ->assertJsonPath('data.source', 'website')
            ->assertJsonPath('data.notes', 'Follow-up scheduled');

        $this->assertDatabaseHas('leads', [
            'id' => $lead->id,
            'name' => 'Updated Lead',
            'status' => 'qualified',
            'source' => 'website',
            'notes' => 'Follow-up scheduled',
        ]);
    }

    /** @test */
    public function validation_errors_are_returned_for_invalid_payload(): void
    {
        $lead = Lead::factory()->create();

        $this->signInAdmin();

        $response = $this->putJson(
            route('admin.api.crm.leads.update', $lead->uuid),
            ['source' => 'invalid-source']
        );

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['source']);
    }

    /** @test */
    public function unauthenticated_requests_are_rejected(): void
    {
        $lead = Lead::factory()->create();

        $response = $this->putJson(
            route('admin.api.crm.leads.update', $lead->uuid),
            ['name' => 'Attempted Update']
        );

        $response->assertUnauthorized();
    }

    /** @test */
    public function not_found_is_returned_when_lead_does_not_exist(): void
    {
        $this->signInAdmin();

        $response = $this->putJson(
            route('admin.api.crm.leads.update', (string) Str::uuid()),
            ['name' => 'Updated Lead']
        );

        $response->assertNotFound()
            ->assertJsonPath('code', 'NOT_FOUND');
    }
}

