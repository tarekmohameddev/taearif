<?php

declare(strict_types=1);

namespace Tests\Feature\Bot;

use App\Models\PropertyExternalLink;
use App\Models\User\RealestateManagement\Property;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class PropertyExternalLinkTest extends TestCase
{
    use RefreshDatabase;

    private \App\Models\User $user;
    private Property $property;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = \App\Models\User::factory()->create();
        $this->actingAs($this->user, 'sanctum');

        $this->property = Property::factory()->create([
            'user_id' => $this->user->id,
        ]);
    }

    public function test_it_lists_external_links_for_property(): void
    {
        PropertyExternalLink::create([
            'property_id' => $this->property->id,
            'user_id'     => $this->user->id,
            'platform'    => 'aqar',
            'url'         => 'https://aqar.com/p/123',
            'active'      => true,
        ]);

        $response = $this->getJson('/api/whatsapp/properties/' . $this->property->id . '/external-links');
        $response->assertOk();
        $response->assertJsonStructure(['data' => [['id', 'platform', 'url', 'active']]]);
    }

    public function test_it_creates_an_external_link(): void
    {
        $response = $this->postJson('/api/whatsapp/properties/' . $this->property->id . '/external-links', [
            'platform' => 'bayut',
            'url'      => 'https://bayut.sa/pm/12345/villa-riyadh',
            'label'    => 'فيلا الرياض - بيوت',
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('property_external_links', [
            'property_id' => $this->property->id,
            'platform'    => 'bayut',
        ]);
    }

    public function test_it_rejects_invalid_url(): void
    {
        $response = $this->postJson('/api/whatsapp/properties/' . $this->property->id . '/external-links', [
            'platform' => 'aqar',
            'url'      => 'not-a-url',
        ]);

        $response->assertUnprocessable();
    }

    public function test_it_deletes_an_external_link(): void
    {
        $link = PropertyExternalLink::create([
            'property_id' => $this->property->id,
            'user_id'     => $this->user->id,
            'platform'    => 'aqar',
            'url'         => 'https://aqar.com/p/999',
            'active'      => true,
        ]);

        $response = $this->deleteJson('/api/whatsapp/properties/' . $this->property->id . '/external-links/' . $link->id);
        $response->assertOk();
        $this->assertDatabaseMissing('property_external_links', ['id' => $link->id]);
    }

    public function test_it_cannot_manage_other_tenants_property_links(): void
    {
        $other         = \App\Models\User::factory()->create();
        $otherProperty = Property::factory()->create(['user_id' => $other->id]);

        $response = $this->getJson('/api/whatsapp/properties/' . $otherProperty->id . '/external-links');
        $response->assertNotFound();
    }
}
