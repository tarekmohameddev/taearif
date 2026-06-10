<?php

namespace Tests\Feature\Api\V1\TenantWebsite;

use App\Models\Building;
use App\Models\Property\PropertyDocument;
use App\Models\User;
use App\Models\User\RealestateManagement\Property;
use App\Models\User\RealestateManagement\PropertyContent;
use App\Services\Property\PropertyDocumentService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Schema;
use Tests\Concerns\EnsuresPropertyStatusColumns;
use Tests\TestCase;

class PublicPropertyVisibilityTest extends TestCase
{
    use EnsuresPropertyStatusColumns;
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['users', 'user_properties', 'user_property_contents'] as $table) {
            if (! Schema::hasTable($table)) {
                $this->markTestSkipped("Missing DB table: {$table}.");
            }
        }

        $this->ensurePropertyStatusColumns();
    }

    /** @var list<string> */
    private const SENSITIVE_KEYS = [
        'deed_number',
        'owner_name',
        'owner_phone',
        'water_meter_number',
        'electricity_meter_number',
        'source_broker_name',
        'source_broker_phone',
        'source_broker_type',
        'source_broker_id',
        'owner_number',
        'advertising_license',
        'created_by',
        'creator',
        'import_batch_id',
        'validation_errors',
        'missing_fields',
        'completion_status',
        'deed_image',
        'deed_image_url',
        'is_archived',
        'user_id',
        'internal_notes',
        'documents',
        'attachments',
    ];

    public function test_draft_properties_are_hidden_from_list(): void
    {
        $tenant = User::factory()->create(['account_type' => 'tenant']);
        $published = $this->createProperty($tenant->id, 'published', 'available');
        $this->createProperty($tenant->id, 'draft', 'available');

        $response = $this->getJson("/api/v1/tenant-website/{$tenant->username}/properties");

        $response->assertOk();
        $ids = collect($response->json('properties'))->pluck('id')->map(fn ($id) => (int) $id);
        $this->assertTrue($ids->contains($published->id));
        $this->assertCount(1, $ids);

        $item = collect($response->json('properties'))->firstWhere('id', (string) $published->id);
        $this->assertSame('available', $item['unit_status'] ?? null);
        $this->assertSame('published', $item['publish_status'] ?? null);
    }

    public function test_draft_property_show_returns_404(): void
    {
        $tenant = User::factory()->create(['account_type' => 'tenant']);
        $draft = $this->createProperty($tenant->id, 'draft', 'available');
        $slug = $draft->contents->first()->slug;

        $this->getJson("/api/v1/tenant-website/{$tenant->username}/properties/{$slug}")
            ->assertNotFound();
    }

    public function test_published_units_with_reserved_sold_rented_status_are_visible(): void
    {
        $tenant = User::factory()->create(['account_type' => 'tenant']);
        $reserved = $this->createProperty($tenant->id, 'published', 'reserved');
        $sold = $this->createProperty($tenant->id, 'published', 'sold', 'sale');
        $rented = $this->createProperty($tenant->id, 'published', 'rented', 'rent');

        $response = $this->getJson("/api/v1/tenant-website/{$tenant->username}/properties");
        $response->assertOk();

        $ids = collect($response->json('properties'))->pluck('id')->map(fn ($id) => (int) $id);
        $this->assertTrue($ids->contains($reserved->id));
        $this->assertTrue($ids->contains($sold->id));
        $this->assertTrue($ids->contains($rented->id));

        $byId = collect($response->json('properties'))->keyBy('id');
        $this->assertSame('reserved', $byId[(string) $reserved->id]['unit_status']);
        $this->assertSame('sold', $byId[(string) $sold->id]['unit_status']);
        $this->assertSame('rented', $byId[(string) $rented->id]['unit_status']);
    }

    public function test_property_show_excludes_sensitive_fields(): void
    {
        if (! Schema::hasTable('buildings')) {
            $this->markTestSkipped('buildings table not available');
        }

        $tenant = User::factory()->create(['account_type' => 'tenant']);
        $building = Building::create([
            'user_id' => $tenant->id,
            'name' => 'Sensitive Tower',
            'owner_name' => 'Secret Owner',
            'owner_phone' => '+966500000001',
            'deed_number' => 'DEED-SECRET-123',
            'deed_image' => 'deeds/secret.jpg',
            'is_archived' => false,
        ]);

        $property = $this->createProperty($tenant->id, 'published', 'available', 'sale', [
            'building_id' => $building->id,
            'water_meter_number' => 'WM-999',
            'electricity_meter_number' => 'EM-888',
            'deed_number' => 'PROP-DEED-456',
            'owner_number' => 'OWN-777',
            'source_broker_name' => 'Broker X',
            'source_broker_phone' => '+966500000002',
            'source_broker_type' => 'external',
            'advertising_license' => 'LIC-123',
            'created_by' => $tenant->id,
        ]);

        $slug = $property->contents->first()->slug;
        $response = $this->getJson("/api/v1/tenant-website/{$tenant->username}/properties/{$slug}");
        $response->assertOk();

        $payload = json_encode($response->json('property'));
        foreach (self::SENSITIVE_KEYS as $key) {
            $this->assertStringNotContainsString('"' . $key . '"', $payload, "Sensitive key [{$key}] leaked in show response");
        }

        $response->assertJsonPath('property.unit_status', 'available');
        $response->assertJsonPath('property.listing_purpose', 'sale');
        $response->assertJsonPath('property.publish_status', 'published');
    }

    public function test_property_show_building_is_sanitized(): void
    {
        if (! Schema::hasTable('buildings')) {
            $this->markTestSkipped('buildings table not available');
        }

        $tenant = User::factory()->create(['account_type' => 'tenant']);
        $building = Building::create([
            'user_id' => $tenant->id,
            'name' => 'Public Tower',
            'owner_name' => 'Hidden Owner',
            'deed_number' => 'DEED-HIDDEN',
            'is_archived' => false,
        ]);

        $property = $this->createProperty($tenant->id, 'published', 'available', 'sale', [
            'building_id' => $building->id,
        ]);
        $slug = $property->contents->first()->slug;

        $response = $this->getJson("/api/v1/tenant-website/{$tenant->username}/properties/{$slug}");
        $response->assertOk()
            ->assertJsonPath('property.building.id', $building->id)
            ->assertJsonPath('property.building.name', 'Public Tower')
            ->assertJsonPath('property.building.slug', $building->slug);

        $buildingPayload = $response->json('property.building');
        $this->assertSame(['id', 'name', 'slug'], array_keys($buildingPayload));
    }

    public function test_public_property_responses_do_not_leak_archive_items(): void
    {
        if (! Schema::hasTable('property_documents')) {
            $this->markTestSkipped('property_documents table not available');
        }

        $tenant = User::factory()->create(['account_type' => 'tenant']);
        $property = $this->createProperty($tenant->id, 'published', 'available');

        $secretDeedNumber = 'SECRET_DEED_185_' . $property->id;
        $secretMeterNumber = 'WM-SECRET-185_' . $property->id;
        $secretDocTitle = 'SECRET_DOC_185_' . $property->id;
        $secretDeedFile = 'deed-secret-185.jpg';

        $service = app(PropertyDocumentService::class);

        $service->storeArchiveItem(
            $property,
            'deed',
            'Deed archive',
            null,
            [],
            ['deed_number' => $secretDeedNumber],
            $tenant->id,
        );

        PropertyDocument::query()
            ->where('property_id', $property->id)
            ->where('type', 'deed')
            ->latest('id')
            ->first()
            ?->update([
                'attachments' => [[
                    'path' => 'property-docs/' . $secretDeedFile,
                    'name' => $secretDeedFile,
                    'size' => 512000,
                ]],
            ]);

        $service->storeArchiveItem(
            $property,
            'meter',
            'Water meter',
            null,
            [],
            ['meter_kind' => 'water', 'meter_number' => $secretMeterNumber],
            $tenant->id,
        );

        $service->storeArchiveItem(
            $property,
            'document',
            $secretDocTitle,
            null,
            [],
            null,
            $tenant->id,
        );

        $slug = $property->contents->first()->slug;
        $secrets = [$secretDeedNumber, $secretMeterNumber, $secretDocTitle, $secretDeedFile];

        $listResponse = $this->getJson("/api/v1/tenant-website/{$tenant->username}/properties");
        $listResponse->assertOk();
        $listPayload = json_encode($listResponse->json());
        foreach ($secrets as $secret) {
            $this->assertStringNotContainsString($secret, $listPayload);
        }

        $showResponse = $this->getJson("/api/v1/tenant-website/{$tenant->username}/properties/{$slug}");
        $showResponse->assertOk();

        $showPayload = json_encode($showResponse->json('property'));
        foreach ($secrets as $secret) {
            $this->assertStringNotContainsString($secret, $showPayload);
        }

        foreach (self::SENSITIVE_KEYS as $key) {
            $this->assertStringNotContainsString('"' . $key . '"', $showPayload, "Sensitive key [{$key}] leaked in show response");
        }
    }

    public function test_public_property_responses_do_not_leak_internal_notes(): void
    {
        if (! Schema::hasTable('property_documents')) {
            $this->markTestSkipped('property_documents table not available');
        }

        $tenant = User::factory()->create(['account_type' => 'tenant']);
        $property = $this->createProperty($tenant->id, 'published', 'available');

        $secretContent = 'INTERNAL_NOTE_SECRET_DEV184_' . $property->id;
        $secretFileName = 'secret-offer-dev184.pdf';

        app(PropertyDocumentService::class)->storeNote(
            $property,
            $secretContent,
            [],
            $tenant->id,
        );

        PropertyDocument::query()
            ->where('property_id', $property->id)
            ->where('type', 'note')
            ->latest('id')
            ->first()
            ?->update([
                'attachments' => [[
                    'path' => 'property-docs/' . $secretFileName,
                    'name' => $secretFileName,
                    'size' => 20480,
                ]],
            ]);

        $slug = $property->contents->first()->slug;

        $listResponse = $this->getJson("/api/v1/tenant-website/{$tenant->username}/properties");
        $listResponse->assertOk();
        $listPayload = json_encode($listResponse->json());
        $this->assertStringNotContainsString($secretContent, $listPayload);
        $this->assertStringNotContainsString($secretFileName, $listPayload);

        $showResponse = $this->getJson("/api/v1/tenant-website/{$tenant->username}/properties/{$slug}");
        $showResponse->assertOk();

        $showPayload = json_encode($showResponse->json('property'));
        $this->assertStringNotContainsString($secretContent, $showPayload);
        $this->assertStringNotContainsString($secretFileName, $showPayload);

        foreach (self::SENSITIVE_KEYS as $key) {
            $this->assertStringNotContainsString('"' . $key . '"', $showPayload, "Sensitive key [{$key}] leaked in show response");
        }
    }

    public function test_reservation_on_draft_property_returns_404(): void
    {
        $tenant = User::factory()->create(['account_type' => 'tenant']);
        $draft = $this->createProperty($tenant->id, 'draft', 'available');
        $slug = $draft->contents->first()->slug;

        $payload = [
            'propertySlug' => $slug,
            'customerName' => 'Test User',
            'customerPhone' => '+966500000000',
        ];

        $this->postJson("/api/v1/tenant-website/{$tenant->username}/reservations", $payload)
            ->assertNotFound();
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    private function createProperty(
        int $userId,
        string $publishStatus,
        string $unitStatus,
        string $listingPurpose = 'sale',
        array $extra = []
    ): Property {
        $property = Property::create(array_merge([
            'user_id' => $userId,
            'price' => 100000,
            'purpose' => $listingPurpose,
            'listing_purpose' => $listingPurpose,
            'unit_status' => $unitStatus,
            'publish_status' => $publishStatus,
            'status' => $publishStatus === 'published' ? 1 : 0,
            'featured_image' => 'test.jpg',
            'property_type' => 'apartment',
        ], $extra));

        PropertyContent::create([
            'user_id' => $userId,
            'property_id' => $property->id,
            'language_id' => 1,
            'title' => 'Test ' . $property->id,
            'slug' => 'test-' . $property->id,
            'address' => 'Address',
            'description' => 'Description',
        ]);

        return $property->fresh(['contents']);
    }
}
