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

    public function test_bulk_import_ignores_id_column_and_creates_instead_of_updating(): void
    {
        $this->skipIfMissingSchema();
        [$tenant] = $this->actingAsTenantWithCreate();

        // Create an initial property via import
        $file = $this->makeExcelUpload(
            ['عنوان الإعلان', 'السعر', 'العنوان', 'الوصف', 'الغرض', 'النوع', 'المساحة'],
            [['شقة أصلية', 500000, 'حي الأصلي الرياض', 'وصف عقار أصلي للاختبار', 'بيع', 'سكني', 100]],
        );
        $response = $this->post('/api/properties/bulk-import', ['file' => $file], ['Accept' => 'application/json']);
        $this->assertSame(0, (int) ($response->json('failed_count') ?? 0), $response->getContent());

        $existing = Property::where('user_id', $tenant->id)->latest('id')->first();
        $this->assertNotNull($existing);
        $existingId = $existing->id;
        $existingUpdatedAt = $existing->updated_at?->toDateTimeString();

        PropertyContent::where('property_id', $existing->id)->update([
            'meta_keyword' => 'شقة مميزة, عقار الرياض',
            'meta_description' => 'وصف ميتا مخصص للسيو',
        ]);

        // File still includes المعرّف (legacy export-for-import) — must be ignored (create-only)
        $createFile = $this->makeExcelUpload(
            ['المعرّف', 'عنوان الإعلان', 'السعر', 'العنوان', 'الوصف', 'الغرض', 'النوع', 'المساحة'],
            [[$existingId, 'شقة جديدة من الاستيراد', 600000, 'حي جديد الرياض', 'وصف عقار جديد للاختبار', 'بيع', 'سكني', 120]],
        );

        $createResponse = $this->post('/api/properties/bulk-import', ['file' => $createFile], [
            'Accept' => 'application/json',
        ]);

        $this->assertContains($createResponse->status(), [200, 422], $createResponse->getContent());
        $this->assertSame(0, (int) ($createResponse->json('failed_count') ?? 0), $createResponse->getContent());
        $this->assertSame(0, (int) ($createResponse->json('updated_count') ?? 0), $createResponse->getContent());
        $this->assertGreaterThan(
            0,
            (int) ($createResponse->json('imported_count') ?? 0) + (int) ($createResponse->json('incomplete_count') ?? 0),
            $createResponse->getContent()
        );

        $existing->refresh();
        $this->assertEquals(500000, (float) $existing->price);
        $this->assertSame($existingUpdatedAt, $existing->updated_at?->toDateTimeString());

        $existingContent = PropertyContent::where('property_id', $existingId)->first();
        $this->assertNotNull($existingContent);
        $this->assertSame('شقة أصلية', $existingContent->title);
        $this->assertSame('شقة مميزة, عقار الرياض', $existingContent->meta_keyword);

        $created = Property::where('user_id', $tenant->id)
            ->where('id', '!=', $existingId)
            ->latest('id')
            ->first();
        $this->assertNotNull($created);
        $this->assertEquals(600000, (float) $created->price);

        $createdContent = PropertyContent::where('property_id', $created->id)->first();
        $this->assertNotNull($createdContent);
        $this->assertSame('شقة جديدة من الاستيراد', $createdContent->title);
    }

    public function test_raw_export_numeric_zero_cells_do_not_fail_string_validation(): void
    {
        $this->skipIfMissingSchema();
        $this->actingAsTenantWithCreate();

        $file = $this->makeExcelUpload(
            [
                'عنوان الإعلان',
                'السعر',
                'العنوان',
                'الوصف',
                'الغرض',
                'النوع',
                'المساحة',
                'مميز',
                'مصعد',
                'بلكونة',
                'غرفة خادمة',
                'غرفة تخزين',
                'مسبح',
            ],
            [
                [
                    'عقار من تصدير خام',
                    100000,
                    'شارع الاختبار',
                    'وصف كافي للاستيراد التجريبي',
                    'بيع',
                    'سكني',
                    120,
                    0,
                    0,
                    0,
                    0,
                    0,
                    0,
                ],
            ],
        );

        $response = $this->post('/api/properties/bulk-import', ['file' => $file], [
            'Accept' => 'application/json',
        ]);

        $this->assertSame(0, (int) ($response->json('failed_count') ?? 0), $response->getContent());
        foreach ($response->json('errors') ?? [] as $error) {
            $this->assertStringNotContainsString(
                'must be a string',
                (string) ($error['error'] ?? ''),
                'Unexpected string validation failure: ' . json_encode($error, JSON_UNESCAPED_UNICODE)
            );
        }
    }

    public function test_user_raw_export_fixture_has_no_string_validation_errors(): void
    {
        $this->skipIfMissingSchema();
        $this->actingAsTenantWithCreate();

        $fixture = base_path('tests/fixtures/properties-export-2026-08-05.xlsx');
        if (! is_file($fixture)) {
            $this->markTestSkipped('Fixture tests/fixtures/properties-export-2026-08-05.xlsx not found.');
        }

        $file = new UploadedFile(
            $fixture,
            'properties-export-2026-08-05.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true,
        );

        $response = $this->post('/api/properties/bulk-import', ['file' => $file], [
            'Accept' => 'application/json',
        ]);

        $errors = $response->json('errors') ?? [];
        $errorMessages = array_map(
            static fn ($error) => (string) ($error['error'] ?? ''),
            $errors
        );
        $combined = implode("\n", $errorMessages);

        $this->assertNotEmpty($errors, 'Raw export fixture should be rejected with errors: ' . $response->getContent());
        $this->assertTrue(
            str_contains($combined, 'raw Export file')
                || str_contains($combined, 'raw export file')
                || str_contains($combined, 'not supported'),
            'Expected raw-export rejection message, got: ' . json_encode($errorMessages, JSON_UNESCAPED_UNICODE)
        );

        $stringErrors = array_filter($errorMessages, static function (string $message) {
            return str_contains($message, 'must be a string');
        });

        $this->assertEmpty(
            $stringErrors,
            'Raw export fixture should not fail string validation: ' . json_encode($stringErrors, JSON_UNESCAPED_UNICODE)
        );
    }

    public function test_import_ready_shaped_file_is_not_rejected_as_raw_export(): void
    {
        $this->skipIfMissingSchema();
        $this->actingAsTenantWithCreate();

        // Same columns as PropertiesImportReadyExport / blank template (no metadata).
        $file = $this->makeExcelUpload(
            [
                'عنوان الإعلان',
                'السعر',
                'العنوان',
                'الوصف',
                'الغرض',
                'النوع',
                'المساحة',
                'الصورة الرئيسية',
            ],
            [[
                'عقار من تصدير للاستيراد',
                250000,
                'حي النرجس الرياض',
                'وصف عقار كافٍ لاستيراد ملف تصدير جاهز',
                'بيع',
                'سكني',
                140,
                'https://example.com/ready.jpg',
            ]],
        );

        $response = $this->post('/api/properties/bulk-import', ['file' => $file], [
            'Accept' => 'application/json',
        ]);

        $errors = $response->json('errors') ?? [];
        foreach ($errors as $error) {
            $message = (string) ($error['error'] ?? '');
            $this->assertStringNotContainsString('raw Export', $message, $response->getContent());
            $this->assertStringNotContainsString('raw export', $message, $response->getContent());
            $this->assertStringNotContainsString('_raw_export', $message, $response->getContent());
        }

        $this->assertSame(0, (int) ($response->json('failed_count') ?? 0), $response->getContent());
        $this->assertGreaterThan(
            0,
            (int) ($response->json('imported_count') ?? 0) + (int) ($response->json('incomplete_count') ?? 0),
            $response->getContent()
        );
    }

    public function test_asterisk_required_headers_map_and_five_fields_create_complete(): void
    {
        $this->skipIfMissingSchema();
        [$tenant] = $this->actingAsTenantWithCreate();

        $file = $this->makeExcelUpload(
            [
                'عنوان الإعلان *',
                'العنوان *',
                'الوصف *',
                'النوع *',
                'الصورة الرئيسية *',
            ],
            [[
                'شقة مكتملة بالحد الأدنى',
                'شارع الاختبار حي العليا',
                'وصف عقار كافٍ لاكتمال الاستيراد بالخمسة',
                'سكني',
                'https://example.com/complete.jpg',
            ]],
        );

        $response = $this->post('/api/properties/bulk-import', ['file' => $file], [
            'Accept' => 'application/json',
        ]);

        $this->assertSame(0, (int) ($response->json('failed_count') ?? 0), $response->getContent());
        $this->assertGreaterThan(0, (int) ($response->json('imported_count') ?? 0), $response->getContent());
        $this->assertSame(0, (int) ($response->json('incomplete_count') ?? 0), $response->getContent());

        $property = Property::where('user_id', $tenant->id)->latest('id')->first();
        $this->assertNotNull($property);
        $this->assertSame('complete', $property->completion_status);
        $this->assertSame('residential', $property->property_type);

        $content = PropertyContent::where('property_id', $property->id)->first();
        $this->assertNotNull($content);
        $this->assertSame('شقة مكتملة بالحد الأدنى', $content->title);
    }

    /**
     * @param  array{title: string, address: string, description: string, featured_image: string, property_type: string}  $identity
     */
    private function seedCompletePeer(User $tenant, Language $language, array $identity): Property
    {
        $property = Property::create([
            'user_id' => $tenant->id,
            'created_by' => $tenant->id,
            'price' => 100000,
            'purpose' => 'sale',
            'listing_purpose' => 'sale',
            'unit_status' => 'available',
            'publish_status' => 'published',
            'property_type' => $identity['property_type'],
            'area' => 100,
            'status' => 1,
            'completion_status' => 'complete',
            'featured' => 0,
            'featured_image' => $identity['featured_image'],
            'completed_at' => now(),
        ]);

        PropertyContent::create([
            'user_id' => $tenant->id,
            'property_id' => $property->id,
            'language_id' => $language->id,
            'title' => $identity['title'],
            'slug' => 'complete-peer-' . $property->id . '-' . uniqid(),
            'address' => $identity['address'],
            'description' => $identity['description'],
        ]);

        return $property->fresh();
    }

    /** @return list<string> */
    private function fiveFieldHeaders(): array
    {
        return [
            'عنوان الإعلان *',
            'العنوان *',
            'الوصف *',
            'النوع *',
            'الصورة الرئيسية *',
        ];
    }

    /**
     * @param  array{title: string, address: string, description: string, property_type: string, featured_image: string}  $identity
     * @return list<mixed>
     */
    private function fiveFieldRow(array $identity): array
    {
        return [
            $identity['title'],
            $identity['address'],
            $identity['description'],
            $identity['property_type'] === 'residential' ? 'سكني' : $identity['property_type'],
            $identity['featured_image'],
        ];
    }

    public function test_import_same_title_address_different_description_creates(): void
    {
        $this->skipIfMissingSchema();
        [$tenant, $language] = $this->actingAsTenantWithCreate();

        $peerIdentity = [
            'title' => 'Villa Dup Test A',
            'address' => 'Street 1 District Test',
            'description' => 'Original description long enough',
            'featured_image' => 'https://example.com/img.jpg',
            'property_type' => 'residential',
        ];
        $this->seedCompletePeer($tenant, $language, $peerIdentity);

        $before = Property::where('user_id', $tenant->id)->count();

        $file = $this->makeExcelUpload(
            $this->fiveFieldHeaders(),
            [$this->fiveFieldRow(array_merge($peerIdentity, [
                'description' => 'Different description long enough',
            ]))],
        );

        $response = $this->post('/api/properties/bulk-import', ['file' => $file], [
            'Accept' => 'application/json',
        ]);

        $this->assertSame(0, (int) ($response->json('failed_count') ?? 0), $response->getContent());
        $this->assertGreaterThan(0, (int) ($response->json('imported_count') ?? 0), $response->getContent());
        $this->assertSame($before + 1, Property::where('user_id', $tenant->id)->count());
    }

    public function test_import_exact_five_field_match_fails_row_but_continues_file(): void
    {
        $this->skipIfMissingSchema();
        [$tenant, $language] = $this->actingAsTenantWithCreate();

        $peerIdentity = [
            'title' => 'Exact Match Peer Villa',
            'address' => 'Match Street Address Here',
            'description' => 'Matching description text here',
            'featured_image' => 'https://example.com/match.jpg',
            'property_type' => 'residential',
        ];
        $this->seedCompletePeer($tenant, $language, $peerIdentity);

        $before = Property::where('user_id', $tenant->id)->count();

        $other = [
            'title' => 'Other Unique Villa Row',
            'address' => 'Other Street Address Here',
            'description' => 'Other description text here ok',
            'featured_image' => 'https://example.com/other.jpg',
            'property_type' => 'residential',
        ];

        $file = $this->makeExcelUpload(
            $this->fiveFieldHeaders(),
            [
                $this->fiveFieldRow($peerIdentity),
                $this->fiveFieldRow($other),
            ],
        );

        $response = $this->post('/api/properties/bulk-import', ['file' => $file], [
            'Accept' => 'application/json',
        ]);

        $this->assertSame(1, (int) ($response->json('failed_count') ?? 0), $response->getContent());
        $this->assertGreaterThan(0, (int) ($response->json('imported_count') ?? 0), $response->getContent());
        $this->assertSame($before + 1, Property::where('user_id', $tenant->id)->count());

        $created = PropertyContent::where('user_id', $tenant->id)
            ->where('title', $other['title'])
            ->first();
        $this->assertNotNull($created);

        $dupTitles = PropertyContent::where('user_id', $tenant->id)
            ->where('title', $peerIdentity['title'])
            ->count();
        $this->assertSame(1, $dupTitles);
    }

    public function test_import_two_identical_five_field_rows_second_fails(): void
    {
        $this->skipIfMissingSchema();
        [$tenant] = $this->actingAsTenantWithCreate();

        $identity = [
            'title' => 'Twin Row Villa Import',
            'address' => 'Twin Street Address Here',
            'description' => 'Twin description text here ok',
            'featured_image' => 'https://example.com/twin.jpg',
            'property_type' => 'residential',
        ];

        $before = Property::where('user_id', $tenant->id)->count();
        $row = $this->fiveFieldRow($identity);

        $file = $this->makeExcelUpload($this->fiveFieldHeaders(), [$row, $row]);

        $response = $this->post('/api/properties/bulk-import', ['file' => $file], [
            'Accept' => 'application/json',
        ]);

        $this->assertSame(1, (int) ($response->json('failed_count') ?? 0), $response->getContent());
        $this->assertSame(1, (int) ($response->json('imported_count') ?? 0), $response->getContent());
        $this->assertSame($before + 1, Property::where('user_id', $tenant->id)->count());
    }

    public function test_import_invalid_featured_image_url_creates_incomplete_not_duplicate(): void
    {
        $this->skipIfMissingSchema();
        [$tenant, $language] = $this->actingAsTenantWithCreate();

        $peerIdentity = [
            'title' => 'Invalid Image Peer Villa',
            'address' => 'Invalid Image Street Addr',
            'description' => 'Invalid image peer description',
            'featured_image' => 'https://example.com/valid.jpg',
            'property_type' => 'residential',
        ];
        $this->seedCompletePeer($tenant, $language, $peerIdentity);

        $before = Property::where('user_id', $tenant->id)->count();

        $file = $this->makeExcelUpload(
            $this->fiveFieldHeaders(),
            [$this->fiveFieldRow(array_merge($peerIdentity, [
                'featured_image' => 'not-a-valid-image-url',
            ]))],
        );

        $response = $this->post('/api/properties/bulk-import', ['file' => $file], [
            'Accept' => 'application/json',
        ]);

        $this->assertSame(0, (int) ($response->json('failed_count') ?? 0), $response->getContent());
        $this->assertGreaterThan(0, (int) ($response->json('incomplete_count') ?? 0), $response->getContent());
        $this->assertSame($before + 1, Property::where('user_id', $tenant->id)->count());

        $created = Property::where('user_id', $tenant->id)->latest('id')->first();
        $this->assertNotNull($created);
        $this->assertSame('incomplete', $created->completion_status);
    }

    public function test_import_casing_whitespace_variant_treated_as_duplicate(): void
    {
        $this->skipIfMissingSchema();
        [$tenant, $language] = $this->actingAsTenantWithCreate();

        $peerIdentity = [
            'title' => 'Villa A',
            'address' => 'Main Street Address',
            'description' => 'Canonical description text',
            'featured_image' => 'https://example.com/case.jpg',
            'property_type' => 'residential',
        ];
        $this->seedCompletePeer($tenant, $language, $peerIdentity);

        $before = Property::where('user_id', $tenant->id)->count();

        $file = $this->makeExcelUpload(
            $this->fiveFieldHeaders(),
            [$this->fiveFieldRow([
                'title' => 'villa a ',
                'address' => '  Main   Street Address  ',
                'description' => 'Canonical description text',
                'featured_image' => 'https://example.com/case.jpg',
                'property_type' => 'residential',
            ])],
        );

        $response = $this->post('/api/properties/bulk-import', ['file' => $file], [
            'Accept' => 'application/json',
        ]);

        $this->assertSame(1, (int) ($response->json('failed_count') ?? 0), $response->getContent());
        $this->assertSame(0, (int) ($response->json('imported_count') ?? 0), $response->getContent());
        $this->assertSame($before, Property::where('user_id', $tenant->id)->count());
    }
}
