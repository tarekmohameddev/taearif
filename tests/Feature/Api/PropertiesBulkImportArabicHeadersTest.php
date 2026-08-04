<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Membership;
use App\Models\Package;
use App\Models\User;
use App\Models\User\Language;
use App\Models\User\RealestateManagement\Amenity;
use App\Models\User\RealestateManagement\City;
use App\Models\User\RealestateManagement\Property;
use App\Models\User\RealestateManagement\PropertyContent;
use App\Services\MembershipCacheService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\Concerns\EnsuresPropertyStatusColumns;
use Tests\TestCase;

class PropertiesBulkImportArabicHeadersTest extends TestCase
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
            ['title' => 'Bulk Arabic Headers Test Package'],
            [
                'slug' => 'bulk-arabic-headers-test-package',
                'price' => 0,
                'term' => 'monthly',
                'status' => 1,
                'is_active' => 1,
                'project_limit_number' => 100,
                'real_estate_limit_number' => $propertyLimit,
                'serial_number' => 994,
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
        $membership->transaction_id = 'bulk-ar-headers-' . uniqid();
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

    private function actingAsTenantWithCreate(): array
    {
        $tenant = User::factory()->create(['account_type' => 'tenant']);
        $language = $this->seedTenantContext($tenant);
        $this->grantPermissions($tenant, $tenant, ['properties.create']);
        Sanctum::actingAs($tenant);

        return [$tenant, $language];
    }

    /**
     * @param  list<string>  $headers
     * @param  list<list<mixed>>  $rows
     */
    private function makeExcelUpload(array $headers, array $rows): UploadedFile
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        foreach ($headers as $i => $header) {
            $sheet->setCellValueByColumnAndRow($i + 1, 1, $header);
        }

        foreach ($rows as $rIndex => $row) {
            foreach ($row as $cIndex => $value) {
                $sheet->setCellValueByColumnAndRow($cIndex + 1, $rIndex + 2, $value);
            }
        }

        $path = tempnam(sys_get_temp_dir(), 'xlsx');
        (new Xlsx($spreadsheet))->save($path);

        return new UploadedFile(
            $path,
            'arabic-template.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true,
        );
    }

    public function test_arabic_template_headers_and_values_import_via_bulk_import(): void
    {
        $this->skipIfMissingSchema();
        [$tenant, $language] = $this->actingAsTenantWithCreate();

        $cityName = null;
        if (Schema::hasTable('user_cities')) {
            try {
                $city = City::create([
                    'user_id' => $tenant->id,
                    'language_id' => $language->id,
                    'country_id' => null,
                    'state_id' => null,
                    'name' => 'الرياض',
                    'slug' => 'riyadh-ar-import-' . uniqid(),
                    'featured' => 0,
                    'status' => 1,
                    'serial_number' => 1,
                ]);
                $cityName = $city->name;
            } catch (\Throwable $e) {
                $cityName = null;
            }
        }

        if (Schema::hasTable('user_amenities')) {
            try {
                Amenity::firstOrCreate(
                    ['user_id' => $tenant->id, 'name' => 'مصعد'],
                    [
                        'language_id' => $language->id,
                        'slug' => 'msaad-import-' . uniqid(),
                        'icon' => 'elevator',
                        'status' => 1,
                        'serial_number' => 1,
                    ]
                );
            } catch (\Throwable $e) {
                // Amenity seed is optional; header/value mapping is the assertion target.
            }
        }

        $headers = [
            'عنوان الإعلان',
            'السعر',
            'العنوان',
            'الوصف',
            'الغرض',
            'النوع',
            'المساحة',
            'مصعد',
            'أمن',
        ];
        $row = [
            'شقة اختبار عربية',
            750000,
            'حي النرجس الرياض',
            'وصف عقار كامل للاختبار العربي',
            'بيع',
            'سكني',
            150,
            'نعم',
            'لا',
        ];

        if ($cityName !== null) {
            $headers[] = 'المدينة';
            $row[] = $cityName;
        }

        $file = $this->makeExcelUpload($headers, [$row]);

        $response = $this->post('/api/properties/bulk-import', ['file' => $file], [
            'Accept' => 'application/json',
        ]);

        $this->assertContains(
            $response->status(),
            [200, 422],
            'Expected success or partial_success HTTP status, got: ' . $response->status() . ' ' . $response->getContent()
        );

        $failedCount = (int) ($response->json('failed_count') ?? 0);
        $this->assertSame(0, $failedCount, 'Arabic purpose/type/amenities should not fail validation: ' . $response->getContent());

        $imported = (int) ($response->json('imported_count') ?? 0);
        $incomplete = (int) ($response->json('incomplete_count') ?? 0);
        $this->assertGreaterThan(0, $imported + $incomplete, 'Expected at least one created property');

        $property = Property::where('user_id', $tenant->id)
            ->where('import_batch_id', '!=', null)
            ->latest('id')
            ->first();

        $this->assertNotNull($property);
        $this->assertSame('sale', $property->purpose);
        $this->assertSame('residential', $property->property_type);
        $this->assertEquals(750000, (float) $property->price);
        $this->assertEquals(150, (float) $property->area);

        $content = PropertyContent::where('property_id', $property->id)->first();
        $this->assertNotNull($content);
        $this->assertSame('شقة اختبار عربية', $content->title);

        $missing = $property->missing_fields ?? [];
        if (is_string($missing)) {
            $missing = json_decode($missing, true) ?: [];
        }
        $this->assertNotContains('title', $missing);
        $this->assertNotContains('purpose', $missing);
        $this->assertNotContains('type', $missing);
        $this->assertNotContains('price', $missing);
    }

    public function test_arabic_purpose_type_and_amenity_values_do_not_skip_row(): void
    {
        $this->skipIfMissingSchema();
        $this->actingAsTenantWithCreate();

        $file = $this->makeExcelUpload(
            ['عنوان الإعلان', 'الغرض', 'النوع', 'مصعد', 'السعر'],
            [
                ['وحدة إيجار', 'إيجار', 'تجاري', 'نعم', 12000],
            ],
        );

        $response = $this->post('/api/properties/bulk-import', ['file' => $file], [
            'Accept' => 'application/json',
        ]);

        $this->assertContains($response->status(), [200, 422]);
        $this->assertSame(0, (int) ($response->json('failed_count') ?? 0), $response->getContent());
        $this->assertGreaterThan(
            0,
            (int) ($response->json('imported_count') ?? 0) + (int) ($response->json('incomplete_count') ?? 0)
        );

        $property = Property::query()->latest('id')->first();
        $this->assertNotNull($property);
        $this->assertSame('rent', $property->purpose);
        $this->assertSame('commercial', $property->property_type);
    }
}
