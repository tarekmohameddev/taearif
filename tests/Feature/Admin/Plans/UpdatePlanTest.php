<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Plans;

use App\Domain\Billing\Models\Plan;
use Tests\Feature\Admin\AdminApiTestCase;

class UpdatePlanTest extends AdminApiTestCase
{
    /** @test */
    public function admin_can_update_a_plan(): void
    {
        $plan = $this->getExistingPlan();

        $this->signInAdmin();

        $payload = [
            'title' => 'Updated Plan Title',
            'price' => 199.99,
            'featured' => 1,
        ];

        $response = $this->putJson(
            route('admin.api.plans.update', $plan->id),
            $payload
        );

        $response->assertOk()
            ->assertJsonPath('data.title', 'Updated Plan Title')
            ->assertJsonPath('data.price', 199.99)
            ->assertJsonPath('data.status.featured', true);

        $this->assertDatabaseHas('packages', [
            'id' => $plan->id,
            'title' => 'Updated Plan Title',
            'price' => 199.99,
            'featured' => '1',
        ]);
    }

    /** @test */
    public function validation_errors_are_returned_for_invalid_payload(): void
    {
        $plan = $this->getExistingPlan();

        $this->signInAdmin();

        $response = $this->putJson(
            route('admin.api.plans.update', $plan->id),
            ['price' => -5]
        );

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['price']);
    }

    /** @test */
    public function unauthenticated_requests_are_rejected(): void
    {
        $plan = $this->getExistingPlan();

        $response = $this->putJson(
            route('admin.api.plans.update', $plan->id),
            ['title' => 'Attempted Update']
        );

        $response->assertUnauthorized();
    }

    /** @test */
    public function not_found_is_returned_when_plan_does_not_exist(): void
    {
        $this->signInAdmin();

        $missingId = Plan::query()->max('id') + 1000;

        $response = $this->putJson(
            route('admin.api.plans.update', $missingId),
            ['title' => 'Updated Title']
        );

        $response->assertNotFound();
    }

    private function getExistingPlan(): Plan
    {
        $plan = Plan::query()->first();

        if (!$plan) {
            return Plan::factory()->create();
        }

        return $plan;
    }
}

