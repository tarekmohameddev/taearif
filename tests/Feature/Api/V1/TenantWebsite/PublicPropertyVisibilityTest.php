<?php

namespace Tests\Feature\Api\V1\TenantWebsite;

use App\Models\User;
use App\Models\User\RealestateManagement\Property;
use App\Models\User\RealestateManagement\PropertyContent;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\Concerns\EnsuresPropertyStatusColumns;
use Tests\TestCase;

class PublicPropertyVisibilityTest extends TestCase
{
    use EnsuresPropertyStatusColumns;
    use DatabaseTransactions;

    public function test_draft_properties_are_hidden_and_unit_status_is_returned(): void
    {
        $tenant = User::factory()->create(['account_type' => 'tenant']);
        $published = $this->createProperty($tenant->id, 'published', 'available');
        $this->createProperty($tenant->id, 'draft', 'available');

        $response = $this->getJson("/api/v1/tenant-website/{$tenant->username}/properties");

        $response->assertOk();
        $ids = collect($response->json('data'))->pluck('id')->map(fn ($id) => (int) $id);
        $this->assertTrue($ids->contains($published->id));
        $this->assertCount(1, $ids);

        $item = collect($response->json('data'))->firstWhere('id', (string) $published->id);
        $this->assertSame('available', $item['unit_status'] ?? null);
    }

    private function createProperty(int $userId, string $publishStatus, string $unitStatus): Property
    {
        $property = Property::create([
            'user_id' => $userId,
            'price' => 100000,
            'purpose' => 'sale',
            'listing_purpose' => 'sale',
            'unit_status' => $unitStatus,
            'publish_status' => $publishStatus,
            'status' => $publishStatus === 'published' ? 1 : 0,
            'featured_image' => 'test.jpg',
            'property_type' => 'apartment',
        ]);

        PropertyContent::create([
            'user_id' => $userId,
            'property_id' => $property->id,
            'language_id' => 1,
            'title' => 'Test ' . $property->id,
            'slug' => 'test-' . $property->id,
            'address' => 'Address',
            'description' => 'Description',
        ]);

        return $property;
    }
}
