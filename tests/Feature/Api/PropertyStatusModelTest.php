<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\User\Language;
use App\Models\User\RealestateManagement\Property;
use App\Models\User\RealestateManagement\PropertyContent;
use App\Rules\ValidListingPurposeUnitStatusCombination;
use App\Services\Property\PropertyStatusSyncService;
use App\Services\PropertyFilterService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Tests\Concerns\EnsuresPropertyStatusColumns;
use Tests\TestCase;

class PropertyStatusModelTest extends TestCase
{
    use DatabaseTransactions;
    use EnsuresPropertyStatusColumns;

    /** @test */
    public function backfill_mapper_resolves_listing_purpose_from_legacy_values(): void
    {
        $this->assertSame('sale', PropertyStatusSyncService::resolveListingPurposeFromLegacy('sale', null));
        $this->assertSame('rent', PropertyStatusSyncService::resolveListingPurposeFromLegacy('rent', null));
        $this->assertSame('sale', PropertyStatusSyncService::resolveListingPurposeFromLegacy('sold', null));
        $this->assertSame('rent', PropertyStatusSyncService::resolveListingPurposeFromLegacy('rented', null));
        $this->assertSame('sale', PropertyStatusSyncService::resolveListingPurposeFromLegacy(null, 'for_sale'));
        $this->assertSame('rent', PropertyStatusSyncService::resolveListingPurposeFromLegacy(null, 'for_rent'));
        $this->assertNull(PropertyStatusSyncService::resolveListingPurposeFromLegacy(null, 'available'));
    }

    /** @test */
    public function backfill_mapper_resolves_unit_status_from_legacy_values(): void
    {
        $this->assertSame('sold', PropertyStatusSyncService::resolveUnitStatusFromLegacy('sold', null));
        $this->assertSame('rented', PropertyStatusSyncService::resolveUnitStatusFromLegacy('rented', null));
        $this->assertSame('rented', PropertyStatusSyncService::resolveUnitStatusFromLegacy(null, 'rented'));
        $this->assertSame('available', PropertyStatusSyncService::resolveUnitStatusFromLegacy(null, 'for_sale'));
        $this->assertSame('available', PropertyStatusSyncService::resolveUnitStatusFromLegacy(null, null));
    }

    /** @test */
    public function backfill_mapper_resolves_publish_status_from_legacy_values(): void
    {
        $this->assertSame(
            'published',
            PropertyStatusSyncService::resolvePublishStatusFromLegacy('complete', 1)
        );
        $this->assertSame(
            'draft',
            PropertyStatusSyncService::resolvePublishStatusFromLegacy('complete', 0)
        );
        $this->assertSame(
            'draft',
            PropertyStatusSyncService::resolvePublishStatusFromLegacy('incomplete', 1)
        );
    }

    /** @test */
    public function model_sync_populates_new_fields_from_legacy_on_create(): void
    {
        $user = $this->createTenant();

        $property = Property::create([
            'user_id' => $user->id,
            'created_by' => $user->id,
            'price' => 500000,
            'purpose' => 'sale',
            'property_type' => 'residential',
            'area' => 120,
            'status' => 1,
            'property_status' => 'available',
            'completion_status' => 'complete',
            'featured' => 0,
        ]);

        $property->refresh();

        $this->assertSame('sale', $property->listing_purpose);
        $this->assertSame('available', $property->unit_status);
        $this->assertSame('published', $property->publish_status);
    }

    /** @test */
    public function model_sync_normalizes_legacy_purpose_sold_to_sale_and_unit_status_sold(): void
    {
        $user = $this->createTenant();

        $property = Property::create([
            'user_id' => $user->id,
            'created_by' => $user->id,
            'price' => 500000,
            'purpose' => 'sold',
            'property_type' => 'residential',
            'area' => 120,
            'status' => 1,
            'completion_status' => 'complete',
            'featured' => 0,
        ]);

        $property->refresh();

        $this->assertSame('sale', $property->purpose);
        $this->assertSame('sale', $property->listing_purpose);
        $this->assertSame('sold', $property->unit_status);
        $this->assertSame('sale', $property->property_status);
    }

    /** @test */
    public function model_sync_new_fields_to_legacy_on_update(): void
    {
        $user = $this->createTenant();

        $property = Property::create([
            'user_id' => $user->id,
            'created_by' => $user->id,
            'price' => 500000,
            'purpose' => 'rent',
            'property_type' => 'residential',
            'area' => 120,
            'status' => 0,
            'property_status' => 'available',
            'completion_status' => 'complete',
            'featured' => 0,
        ]);

        $property->update([
            'listing_purpose' => 'sale',
            'unit_status' => 'sold',
            'publish_status' => 'published',
        ]);

        $property->refresh();

        $this->assertSame('sale', $property->purpose);
        $this->assertSame('sale', $property->property_status);
        $this->assertSame(1, (int) $property->status);
    }

    /** @test */
    public function validation_rejects_sale_with_rented_and_rent_with_sold(): void
    {
        $saleRented = new ValidListingPurposeUnitStatusCombination([
            'listing_purpose' => 'sale',
            'unit_status' => 'rented',
        ]);
        $rentSold = new ValidListingPurposeUnitStatusCombination([
            'listing_purpose' => 'rent',
            'unit_status' => 'sold',
        ]);

        $this->assertFalse($saleRented->passes('unit_status', 'rented'));
        $this->assertFalse($rentSold->passes('unit_status', 'sold'));
    }

    /** @test */
    public function validation_accepts_valid_combinations(): void
    {
        $valid = new ValidListingPurposeUnitStatusCombination([
            'listing_purpose' => 'sale',
            'unit_status' => 'sold',
        ]);

        $this->assertTrue($valid->passes('unit_status', 'sold'));
    }

    /** @test */
    public function project_property_request_rejects_legacy_purpose_sold(): void
    {
        $validator = Validator::make(
            [
                'title' => 'Test Unit',
                'description' => 'Desc',
                'featured_image' => 'properties/test.jpg',
                'purpose' => 'sold',
            ],
            (new \App\Http\Requests\Api\Project\Properties\StoreProjectPropertyRequest())->rules()
        );

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('purpose', $validator->errors()->toArray());
    }

    /** @test */
    public function featured_query_excludes_draft_publish_status(): void
    {
        $user = $this->createTenant();
        $language = $this->createArabicLanguage($user);

        $publishedFeatured = $this->createPropertyWithContent($user, $language->id, [
            'featured' => 1,
            'status' => 1,
            'publish_status' => 'published',
            'listing_purpose' => 'sale',
            'unit_status' => 'available',
        ]);

        $draftFeatured = $this->createPropertyWithContent($user, $language->id, [
            'featured' => 1,
            'status' => 1,
            'publish_status' => 'draft',
            'listing_purpose' => 'sale',
            'unit_status' => 'available',
        ]);

        $request = Request::create('/', 'GET', ['sort' => 'new']);
        $query = (new PropertyFilterService())->buildQuery($user->id, $request, $language);
        $ids = $query->pluck('user_properties.id')->all();

        $this->assertContains($publishedFeatured->id, $ids);
        $this->assertNotContains($draftFeatured->id, $ids);
    }

    /** @test */
    public function featured_query_includes_null_publish_status_during_transition(): void
    {
        $user = $this->createTenant();
        $language = $this->createArabicLanguage($user);

        config(['properties.backfill_complete' => false]);

        $legacyFeatured = $this->createPropertyWithContent($user, $language->id, [
            'featured' => 1,
            'status' => 1,
            'publish_status' => null,
            'listing_purpose' => null,
            'unit_status' => null,
        ]);

        $request = Request::create('/', 'GET', ['sort' => 'new']);
        $query = (new PropertyFilterService())->buildQuery($user->id, $request, $language);
        $ids = $query->pluck('user_properties.id')->all();

        $this->assertContains($legacyFeatured->id, $ids);
    }

    /** @test */
    public function update_property_status_syncs_unit_status_to_rented(): void
    {
        $user = $this->createTenant();

        $property = Property::create([
            'user_id' => $user->id,
            'created_by' => $user->id,
            'price' => 500000,
            'purpose' => 'rent',
            'property_type' => 'residential',
            'area' => 120,
            'status' => 1,
            'property_status' => 'available',
            'completion_status' => 'complete',
            'featured' => 0,
        ]);

        $property->update(['property_status' => 'rented']);
        $property->refresh();

        $this->assertSame('rented', $property->property_status);
        $this->assertSame('rented', $property->unit_status);
    }

    private function createTenant(): User
    {
        return User::factory()->create([
            'account_type' => 'tenant',
            'active' => true,
            'status' => 1,
        ]);
    }

    private function createArabicLanguage(User $user): Language
    {
        return Language::firstOrCreate(
            ['user_id' => $user->id, 'code' => 'ar'],
            ['name' => 'Arabic', 'rtl' => 1, 'is_default' => 1]
        );
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createPropertyWithContent(User $user, int $languageId, array $overrides = []): Property
    {
        $property = Property::create(array_merge([
            'user_id' => $user->id,
            'created_by' => $user->id,
            'price' => 1000000,
            'purpose' => 'sale',
            'property_type' => 'residential',
            'area' => 500,
            'status' => 1,
            'property_status' => 'available',
            'completion_status' => 'complete',
            'featured' => 0,
        ], $overrides));

        PropertyContent::create([
            'user_id' => $user->id,
            'property_id' => $property->id,
            'language_id' => $languageId,
            'category_id' => null,
            'country_id' => null,
            'state_id' => null,
            'city_id' => null,
            'title' => 'Unit ' . Str::random(6),
            'slug' => 'unit-' . Str::lower(Str::random(10)),
            'address' => 'Riyadh',
            'description' => 'Test description',
            'meta_keyword' => null,
            'meta_description' => null,
        ]);

        return $property->fresh();
    }
}
