<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Membership;
use App\Models\Package;
use App\Models\User;
use App\Models\User\Language;
use App\Models\User\RealestateManagement\Property;
use App\Models\User\RealestateManagement\PropertyContent;
use App\Services\MembershipCacheService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\Concerns\EnsuresPropertyStatusColumns;
use Tests\TestCase;

/**
 * Draft complete must reject when all five completion-identity fields match
 * another complete property on the same owner. Unlike
 * PropertyDraftCompletionRequirementsTest (ORPHAN_USER_ID / read-only), this
 * seeds real DB peers so findExactCompletionDuplicate can match.
 */
class PropertyDraftCompletionIdentityDuplicateTest extends TestCase
{
    use DatabaseTransactions;
    use EnsuresPropertyStatusColumns;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ensurePropertyStatusColumns();
    }

    private function skipIfMissingSchema(): void
    {
        foreach ([
            'users',
            'user_properties',
            'user_property_contents',
            'memberships',
            'packages',
            'user_languages',
            'api_permissions',
            'api_model_has_permissions',
        ] as $table) {
            if (! Schema::hasTable($table)) {
                $this->markTestSkipped("Missing DB table: {$table}.");
            }
        }
    }

    /**
     * @param  list<string>  $permissions
     */
    private function grantPermissions(User $user, User $tenant, array $permissions): void
    {
        if (! Schema::hasTable('api_permissions')) {
            $this->markTestSkipped('api_permissions table not available.');
        }

        $registrar = app(PermissionRegistrar::class);
        $registrar->setPermissionsTeamId((int) $tenant->id);
        $registrar->forgetCachedPermissions();

        foreach ($permissions as $permissionName) {
            try {
                $permission = Permission::findByName($permissionName, 'sanctum');
            } catch (\Throwable $e) {
                $permission = Permission::create([
                    'name' => $permissionName,
                    'guard_name' => 'sanctum',
                    'team_id' => $tenant->id,
                ]);
            }

            $user->givePermissionTo($permission);
        }

        $registrar->forgetCachedPermissions();
    }

    private function seedTenantContext(User $tenant, int $propertyLimit = 100): Language
    {
        $package = Package::firstOrCreate(
            ['title' => 'Draft Completion Identity Dup Package'],
            [
                'slug' => 'draft-completion-identity-dup-package',
                'price' => 0,
                'term' => 'monthly',
                'status' => 1,
                'is_active' => 1,
                'project_limit_number' => 100,
                'real_estate_limit_number' => $propertyLimit,
                'serial_number' => 993,
            ]
        );

        $package->update(['real_estate_limit_number' => $propertyLimit]);

        $membership = Membership::firstOrNew(['user_id' => $tenant->id]);
        $membership->status = 1;
        $membership->start_date = now()->subDay();
        $membership->expire_date = now()->addMonth();
        $membership->package_id = $package->id;
        $membership->price = 0;
        $membership->currency = 'USD';
        $membership->currency_symbol = '$';
        $membership->payment_method = 'test';
        $membership->transaction_id = 'draft-identity-dup-' . uniqid();
        $membership->save();

        MembershipCacheService::clearCache($tenant->id);

        return Language::firstOrCreate(
            ['user_id' => $tenant->id, 'is_default' => 1],
            [
                'name' => 'Arabic',
                'code' => 'ar',
                'rtl' => 1,
            ]
        );
    }

    /**
     * @return array{0: User, 1: Language}
     */
    private function actingAsTenantWithCreate(): array
    {
        $tenant = User::factory()->create(['account_type' => 'tenant']);
        $language = $this->seedTenantContext($tenant);
        $this->grantPermissions($tenant, $tenant, ['properties.create']);
        Sanctum::actingAs($tenant);

        return [$tenant, $language];
    }

    /**
     * @param  array{title: string, address: string, description: string, featured_image: string, property_type: string}  $identity
     * @param  array<string, mixed>  $overrides
     */
    private function createPropertyWithIdentity(
        User $tenant,
        Language $language,
        array $identity,
        string $completionStatus,
        array $overrides = []
    ): Property {
        $property = Property::create(array_merge([
            'user_id' => $tenant->id,
            'created_by' => $tenant->id,
            'price' => 250000,
            'purpose' => 'sale',
            'listing_purpose' => 'sale',
            'unit_status' => 'available',
            'publish_status' => $completionStatus === 'complete' ? 'published' : 'draft',
            'property_type' => $identity['property_type'],
            'area' => 120,
            'status' => $completionStatus === 'complete' ? 1 : 0,
            'completion_status' => $completionStatus,
            'featured' => 0,
            'featured_image' => $identity['featured_image'],
            'completed_at' => $completionStatus === 'complete' ? now() : null,
        ], $overrides));

        PropertyContent::create([
            'user_id' => $tenant->id,
            'property_id' => $property->id,
            'language_id' => $language->id,
            'title' => $identity['title'],
            'slug' => 'draft-identity-' . $property->id . '-' . uniqid(),
            'address' => $identity['address'],
            'description' => $identity['description'],
        ]);

        return $property->fresh();
    }

    /** @return array{title: string, address: string, description: string, featured_image: string, property_type: string} */
    private function identity(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Draft Identity Villa',
            'address' => 'Identity Street Address',
            'description' => 'Identity description long enough',
            'featured_image' => 'https://example.com/img.jpg',
            'property_type' => 'residential',
        ], $overrides);
    }

    public function test_complete_draft_rejects_when_all_five_match_complete_peer(): void
    {
        $this->skipIfMissingSchema();
        [$tenant, $language] = $this->actingAsTenantWithCreate();

        $identity = $this->identity([
            'title' => 'Conflict Peer Villa Exact',
            'address' => 'Conflict Peer Street Exact',
            'description' => 'Conflict peer description exact',
            'featured_image' => 'https://example.com/conflict.jpg',
        ]);

        $this->createPropertyWithIdentity($tenant, $language, $identity, 'complete');
        $draft = $this->createPropertyWithIdentity(
            $tenant,
            $language,
            $this->identity(['title' => 'Incomplete Draft Title']),
            'incomplete',
            ['featured_image' => null, 'property_type' => null]
        );

        $response = $this->postJson("/api/properties/drafts/{$draft->id}/complete", $identity);

        $response->assertStatus(422)
            ->assertJsonPath('status', 'error');

        $conflicts = $response->json('conflicts') ?? [];
        $this->assertNotEmpty($conflicts, $response->getContent());

        $fields = array_column($conflicts, 'field');
        $this->assertContains('completion_identity', $fields, $response->getContent());

        $draft->refresh();
        $this->assertSame('incomplete', $draft->completion_status);
    }

    public function test_complete_draft_succeeds_when_one_identity_field_differs(): void
    {
        $this->skipIfMissingSchema();
        [$tenant, $language] = $this->actingAsTenantWithCreate();

        $peerIdentity = $this->identity([
            'title' => 'Success Peer Villa Exact',
            'address' => 'Success Peer Street Exact',
            'description' => 'Success peer description exact',
            'featured_image' => 'https://example.com/success-peer.jpg',
        ]);
        $this->createPropertyWithIdentity($tenant, $language, $peerIdentity, 'complete');

        $draft = $this->createPropertyWithIdentity(
            $tenant,
            $language,
            $this->identity(['title' => 'Draft Waiting Complete']),
            'incomplete',
            ['featured_image' => null, 'property_type' => null]
        );

        $payload = array_merge($peerIdentity, [
            'description' => 'Different description for draft ok',
        ]);

        $response = $this->postJson("/api/properties/drafts/{$draft->id}/complete", $payload);

        $response->assertOk()
            ->assertJsonPath('status', 'success');

        $draft->refresh();
        $this->assertSame('complete', $draft->completion_status);
        $this->assertSame($payload['featured_image'], $draft->featured_image);
        $this->assertSame('residential', $draft->property_type);

        $content = PropertyContent::where('property_id', $draft->id)->first();
        $this->assertNotNull($content);
        $this->assertSame($payload['title'], $content->title);
        $this->assertSame($payload['description'], $content->description);
    }
}
