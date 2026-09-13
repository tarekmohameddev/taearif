<?php

declare(strict_types=1);

namespace Tests\Feature\Rms;

use App\Exceptions\Rms\RentalException;
use App\Models\User;
use App\Models\User\RealestateManagement\Property;
use App\Services\Rms\RentalService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class RentalIncompleteUnitTest extends TestCase
{
    use DatabaseTransactions;

    private function skipIfMissingSchema(): void
    {
        foreach (['users', 'user_properties', 'rm_rentals'] as $table) {
            if (! Schema::hasTable($table)) {
                $this->markTestSkipped("Missing DB table: {$table}.");
            }
        }
    }

    private function rentalPayload(int $unitId): array
    {
        return [
            'tenant_full_name' => 'Incomplete Unit Tenant',
            'tenant_phone' => '+966501234567',
            'unit_id' => $unitId,
            'rental_type' => 'monthly',
            'rental_duration' => 12,
            'paying_plan' => 'monthly',
            'total_rental_amount' => 12000,
            'currency' => 'SAR',
        ];
    }

    public function test_create_rental_rejects_incomplete_unit(): void
    {
        $this->skipIfMissingSchema();

        $tenant = User::factory()->create([
            'account_type' => 'tenant',
            'username' => 'rmsinc' . Str::random(4),
        ]);

        $property = Property::query()->create([
            'user_id' => $tenant->id,
            'featured_image' => 'properties/test.jpg',
            'purpose' => 'rent',
            'property_status' => 'available',
            'area' => 100,
            'completion_status' => 'incomplete',
            'missing_fields' => ['title', 'address'],
            'status' => 1,
        ]);

        $service = app(RentalService::class);

        try {
            $service->createRental($tenant->id, $this->rentalPayload($property->id));
            $this->fail('Expected RentalException for incomplete unit');
        } catch (RentalException $e) {
            $this->assertSame('RMS_UNIT_INCOMPLETE', $e->getErrorCode());
            $this->assertSame(400, $e->getStatusCode());
            $this->assertStringContainsString((string) $property->id, $e->getMessage());

            $details = $e->getDetails();
            $this->assertSame($property->id, $details['unit_id']);
            $this->assertSame('incomplete', $details['completion_status']);
            $this->assertSame(['title', 'address'], $details['missing_fields']);
        }
    }

    public function test_create_rental_allows_complete_draft_publish_status_unit(): void
    {
        $this->skipIfMissingSchema();

        if (! Schema::hasColumn('user_properties', 'publish_status')) {
            $this->markTestSkipped('Missing publish_status column.');
        }

        $tenant = User::factory()->create([
            'account_type' => 'tenant',
            'username' => 'rmsdraft' . Str::random(4),
        ]);

        $property = Property::query()->create([
            'user_id' => $tenant->id,
            'featured_image' => 'properties/test.jpg',
            'purpose' => 'rent',
            'property_status' => 'available',
            'area' => 100,
            'completion_status' => 'complete',
            'publish_status' => 'draft',
            'status' => 1,
        ]);

        $service = app(RentalService::class);
        $rental = $service->createRental($tenant->id, $this->rentalPayload($property->id));

        $this->assertNotNull($rental->id);
        $this->assertSame($property->id, (int) $rental->unit_id);
    }
}
