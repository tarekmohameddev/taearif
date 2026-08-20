<?php

namespace Tests\Feature\Admin;

use App\Domain\Admin\Models\Admin;
use App\Services\Admin\WhatsappNumberMonitorService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Http\Middleware\VerifyCsrfToken;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Tests\TestCase;

class WhatsappNumberMonitorTest extends TestCase
{
    use RefreshDatabase;

    private WhatsappNumberMonitorService $service;

    private static int $sequence = 0;

    protected function refreshTestDatabase(): void
    {
        if (! RefreshDatabaseState::$migrated) {
            if (! Schema::hasTable('whatsapp_users') || ! Schema::hasTable('users')) {
                $this->markTestSkipped(
                    'taearif_testing needs core tables (users, whatsapp_users). Import the application schema into taearif_testing.'
                );
            }

            RefreshDatabaseState::$migrated = true;
            $this->app->make(Kernel::class)->setArtisan(null);
        }

        $this->beginDatabaseTransaction();
    }

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'communication.whatsapp.monitor.inbound_stale_hours' => 24,
            'communication.whatsapp.monitor.summary_cache_seconds' => 0,
        ]);

        Cache::flush();

        $this->service = app(WhatsappNumberMonitorService::class);
    }

    private function nextSuffix(): string
    {
        return (string) ++self::$sequence;
    }

    private function createTenant(): int
    {
        return (int) DB::table('users')->insertGetId([
            'tenant_id' => null,
            'account_type' => 'tenant',
            'active' => true,
            'email' => 'tenant-' . Str::uuid() . '@example.com',
            'username' => 'tenant-' . Str::uuid(),
            'password' => Hash::make('secret'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createEmployee(int $tenantId): int
    {
        return (int) DB::table('users')->insertGetId([
            'tenant_id' => $tenantId,
            'account_type' => 'employee',
            'active' => true,
            'email' => 'employee-' . Str::uuid() . '@example.com',
            'username' => 'employee-' . Str::uuid(),
            'password' => Hash::make('secret'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createNumber(int $userId, array $overrides = []): int
    {
        $suffix = $this->nextSuffix();

        return (int) DB::table('whatsapp_users')->insertGetId(array_merge([
            'user_id' => $userId,
            'employee_id' => null,
            'number' => '+9665' . str_pad($suffix, 8, '0', STR_PAD_LEFT),
            'name' => 'Number ' . $suffix,
            'status' => 'active',
            'request_status' => 'pending',
            'phone_id' => 'phone-id-' . $suffix,
            'created_at' => now(),
            'updated_at' => now(),
        ], $overrides));
    }

    private function createWaNumber(int $userId, string $phoneNumberId, array $overrides = []): int
    {
        $suffix = $this->nextSuffix();

        return (int) DB::table('wa_numbers')->insertGetId(array_merge([
            'user_id' => $userId,
            'provider' => 'meta',
            'phone_number' => '+96650' . str_pad($suffix, 7, '0', STR_PAD_LEFT),
            'phone_number_id' => $phoneNumberId,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ], $overrides));
    }

    private function createMessage(int $tenantOwnerId, string $direction, Carbon $at): void
    {
        $conversationId = (int) DB::table('conversations')->insertGetId([
            'user_id' => $tenantOwnerId,
            'channel' => 'whatsapp',
            'external_party_identifier' => 'ext-' . Str::uuid(),
            'last_message_at' => $at,
            'created_at' => $at,
            'updated_at' => $at,
        ]);

        DB::table('messages')->insert([
            'conversation_id' => $conversationId,
            'user_id' => $tenantOwnerId,
            'content' => 'Test message',
            'direction' => $direction,
            'status' => 'delivered',
            'created_at' => $at,
            'updated_at' => $at,
        ]);
    }

    private function rowFor(int $whatsappUserId): object
    {
        $row = $this->service->find($whatsappUserId);

        $this->assertNotNull($row);

        return $row;
    }

    private function seedHealthFilterFixtures(): array
    {
        $tenantWorking = $this->createTenant();
        $workingId = $this->createNumber($tenantWorking, ['phone_id' => 'health-working']);
        $this->createMessage($tenantWorking, 'inbound', now()->subHour());

        $tenantStale = $this->createTenant();
        $staleId = $this->createNumber($tenantStale, ['phone_id' => 'health-stale']);
        $this->createMessage($tenantStale, 'inbound', now()->subHours(48));

        $tenantNever = $this->createTenant();
        $neverId = $this->createNumber($tenantNever, ['phone_id' => 'health-never']);

        $tenantNotLinked = $this->createTenant();
        $notLinkedId = $this->createNumber($tenantNotLinked, [
            'status' => 'not_linked',
            'phone_id' => 'health-not-linked',
        ]);
        $this->createMessage($tenantNotLinked, 'inbound', now()->subHour());

        return compact('workingId', 'staleId', 'neverId', 'notLinkedId');
    }

    private function seedSyncFilterFixtures(): array
    {
        $tenantSynced = $this->createTenant();
        $syncedId = $this->createNumber($tenantSynced, ['phone_id' => 'sync-filter-synced']);
        $this->createWaNumber($tenantSynced, 'sync-filter-synced');

        $tenantMissing = $this->createTenant();
        $missingId = $this->createNumber($tenantMissing, ['phone_id' => 'sync-filter-missing']);

        $tenantMismatch = $this->createTenant();
        $otherTenant = $this->createTenant();
        $mismatchId = $this->createNumber($tenantMismatch, ['phone_id' => 'sync-filter-mismatch']);
        $this->createWaNumber($otherTenant, 'sync-filter-mismatch');

        $tenantNa = $this->createTenant();
        $naId = $this->createNumber($tenantNa, ['phone_id' => null]);

        return compact('syncedId', 'missingId', 'mismatchId', 'naId');
    }

    private function seedSummaryHealthFixtures(): void
    {
        $tenantWorking = $this->createTenant();
        $this->createNumber($tenantWorking, ['phone_id' => 'summary-working']);
        $this->createWaNumber($tenantWorking, 'summary-working');
        $this->createMessage($tenantWorking, 'inbound', now()->subHour());

        $tenantStale = $this->createTenant();
        $this->createNumber($tenantStale, ['phone_id' => 'summary-stale']);
        $this->createWaNumber($tenantStale, 'summary-stale');
        $this->createMessage($tenantStale, 'inbound', now()->subHours(48));

        $tenantNever = $this->createTenant();
        $this->createNumber($tenantNever, ['phone_id' => 'summary-never']);

        $tenantNotLinked = $this->createTenant();
        $this->createNumber($tenantNotLinked, [
            'status' => 'not_linked',
            'phone_id' => null,
        ]);
    }

    private function seedSummaryAndFilterAgreementFixtures(): void
    {
        $this->seedHealthFilterFixtures();

        $tenantMissing = $this->createTenant();
        $this->createNumber($tenantMissing, ['phone_id' => 'agreement-missing']);

        $tenantMismatch = $this->createTenant();
        $otherTenant = $this->createTenant();
        $this->createNumber($tenantMismatch, ['phone_id' => 'agreement-mismatch']);
        $this->createWaNumber($otherTenant, 'agreement-mismatch');

        $tenantSynced = $this->createTenant();
        $this->createNumber($tenantSynced, ['phone_id' => 'agreement-synced']);
        $this->createWaNumber($tenantSynced, 'agreement-synced');

        $tenantWorkingMismatch = $this->createTenant();
        $otherForWorkingMismatch = $this->createTenant();
        $this->createNumber($tenantWorkingMismatch, ['phone_id' => 'agreement-working-mismatch']);
        $this->createWaNumber($otherForWorkingMismatch, 'agreement-working-mismatch');
        $this->createMessage($tenantWorkingMismatch, 'inbound', now()->subHour());

        $tenantBlocked = $this->createTenant();
        $this->createNumber($tenantBlocked, [
            'status' => 'blocked',
            'phone_id' => 'agreement-blocked-phone',
        ]);

        $tenantEmptyPhone = $this->createTenant();
        $this->createNumber($tenantEmptyPhone, ['phone_id' => '']);
    }

    /** @test */
    public function it_resolves_working_health_when_linked_and_recent_inbound_exists(): void
    {
        $tenantId = $this->createTenant();
        $numberId = $this->createNumber($tenantId);
        $this->createMessage($tenantId, 'inbound', now()->subHour());

        $row = $this->rowFor($numberId);

        $this->assertSame(WhatsappNumberMonitorService::HEALTH_WORKING, $row->health);
    }

    /** @test */
    public function it_resolves_no_recent_inbound_when_last_inbound_is_stale(): void
    {
        $tenantId = $this->createTenant();
        $numberId = $this->createNumber($tenantId);
        $this->createMessage($tenantId, 'inbound', now()->subHours(48));

        $row = $this->rowFor($numberId);

        $this->assertSame(WhatsappNumberMonitorService::HEALTH_NO_RECENT_INBOUND, $row->health);
    }

    /** @test */
    public function it_resolves_no_inbound_ever_when_linked_without_messages(): void
    {
        $tenantId = $this->createTenant();
        $numberId = $this->createNumber($tenantId);

        $row = $this->rowFor($numberId);

        $this->assertSame(WhatsappNumberMonitorService::HEALTH_NO_INBOUND_EVER, $row->health);
    }

    /** @test */
    public function it_resolves_no_inbound_ever_when_only_outbound_messages_exist(): void
    {
        $tenantId = $this->createTenant();
        $numberId = $this->createNumber($tenantId);
        $this->createMessage($tenantId, 'outbound', now()->subHour());

        $row = $this->rowFor($numberId);

        $this->assertSame(WhatsappNumberMonitorService::HEALTH_NO_INBOUND_EVER, $row->health);
    }

    /**
     * @test
     * @dataProvider unlinkedStatusProvider
     */
    public function it_resolves_not_linked_for_unlinked_statuses_even_with_recent_inbound(string $status): void
    {
        $tenantId = $this->createTenant();
        $numberId = $this->createNumber($tenantId, [
            'status' => $status,
            'phone_id' => 'phone-unlinked-' . $status,
        ]);
        $this->createMessage($tenantId, 'inbound', now()->subHour());

        $row = $this->rowFor($numberId);

        $this->assertSame(WhatsappNumberMonitorService::HEALTH_NOT_LINKED, $row->health);
    }

    public function unlinkedStatusProvider(): array
    {
        return [
            'not_linked' => ['not_linked'],
            'inactive' => ['inactive'],
            'blocked' => ['blocked'],
        ];
    }

    /**
     * @test
     * @dataProvider emptyPhoneIdProvider
     */
    public function it_resolves_not_linked_when_active_but_phone_id_is_empty($phoneId): void
    {
        $tenantId = $this->createTenant();
        $numberId = $this->createNumber($tenantId, [
            'status' => 'active',
            'phone_id' => $phoneId,
        ]);
        $this->createMessage($tenantId, 'inbound', now()->subHour());

        $row = $this->rowFor($numberId);

        $this->assertSame(WhatsappNumberMonitorService::HEALTH_NOT_LINKED, $row->health);
    }

    public function emptyPhoneIdProvider(): array
    {
        return [
            'null' => [null],
            'empty string' => [''],
        ];
    }

    /** @test */
    public function it_resolves_last_inbound_via_the_tenant_owner_for_employee_owned_numbers(): void
    {
        $tenantId = $this->createTenant();
        $employeeId = $this->createEmployee($tenantId);
        $numberId = $this->createNumber($employeeId);
        $this->createMessage($tenantId, 'inbound', now()->subHour());

        $row = $this->rowFor($numberId);

        $this->assertSame($tenantId, (int) $row->tenant_owner_id);
        $this->assertSame(WhatsappNumberMonitorService::HEALTH_WORKING, $row->health);
    }

    /** @test */
    public function it_resolves_synced_when_meta_wa_number_matches_tenant_owner(): void
    {
        $tenantId = $this->createTenant();
        $phoneId = 'phone-synced-' . Str::uuid();
        $numberId = $this->createNumber($tenantId, ['phone_id' => $phoneId]);
        $this->createWaNumber($tenantId, $phoneId);

        $row = $this->rowFor($numberId);

        $this->assertSame(WhatsappNumberMonitorService::SYNC_SYNCED, $row->sync);
    }

    /** @test */
    public function it_resolves_missing_when_phone_id_is_set_but_no_wa_numbers_row_exists(): void
    {
        $tenantId = $this->createTenant();
        $numberId = $this->createNumber($tenantId, ['phone_id' => 'phone-missing-only']);

        $row = $this->rowFor($numberId);

        $this->assertSame(WhatsappNumberMonitorService::SYNC_MISSING, $row->sync);
    }

    /** @test */
    public function it_resolves_owner_mismatch_when_wa_numbers_points_at_another_tenant(): void
    {
        $tenantId = $this->createTenant();
        $otherTenantId = $this->createTenant();
        $phoneId = 'phone-owner-mismatch';
        $numberId = $this->createNumber($tenantId, ['phone_id' => $phoneId]);
        $this->createWaNumber($otherTenantId, $phoneId);

        $row = $this->rowFor($numberId);

        $this->assertSame(WhatsappNumberMonitorService::SYNC_OWNER_MISMATCH, $row->sync);
    }

    /** @test */
    public function it_resolves_sync_as_na_when_phone_id_is_empty(): void
    {
        $tenantId = $this->createTenant();
        $numberId = $this->createNumber($tenantId, ['phone_id' => null]);

        $row = $this->rowFor($numberId);

        $this->assertSame(WhatsappNumberMonitorService::SYNC_NA, $row->sync);
    }

    /** @test */
    public function it_can_report_working_health_and_owner_mismatch_on_the_same_row(): void
    {
        $tenantId = $this->createTenant();
        $otherTenantId = $this->createTenant();
        $phoneId = 'phone-working-mismatch';
        $numberId = $this->createNumber($tenantId, ['phone_id' => $phoneId]);
        $this->createWaNumber($otherTenantId, $phoneId);
        $this->createMessage($tenantId, 'inbound', now()->subHour());

        $row = $this->rowFor($numberId);

        $this->assertSame(WhatsappNumberMonitorService::HEALTH_WORKING, $row->health);
        $this->assertSame(WhatsappNumberMonitorService::SYNC_OWNER_MISMATCH, $row->sync);
    }

    /** @test */
    public function evolution_provider_wa_numbers_do_not_count_as_synced(): void
    {
        $tenantId = $this->createTenant();
        $phoneId = 'phone-evolution-only';
        $numberId = $this->createNumber($tenantId, ['phone_id' => $phoneId]);
        $this->createWaNumber($tenantId, $phoneId, ['provider' => 'evolution']);

        $row = $this->rowFor($numberId);

        $this->assertSame(WhatsappNumberMonitorService::SYNC_MISSING, $row->sync);
    }

    /** @test */
    public function each_health_filter_returns_only_matching_rows(): void
    {
        $seed = $this->seedHealthFilterFixtures();

        $expectations = [
            WhatsappNumberMonitorService::HEALTH_WORKING => [$seed['workingId']],
            WhatsappNumberMonitorService::HEALTH_NO_RECENT_INBOUND => [$seed['staleId']],
            WhatsappNumberMonitorService::HEALTH_NO_INBOUND_EVER => [$seed['neverId']],
            WhatsappNumberMonitorService::HEALTH_NOT_LINKED => [$seed['notLinkedId']],
        ];

        foreach ($expectations as $health => $expectedIds) {
            $result = $this->service->list(['health' => $health]);
            $ids = collect($result->items())->pluck('id')->map(fn ($id) => (int) $id)->all();

            $this->assertSame($expectedIds, $ids, "Health filter [{$health}] returned unexpected rows.");
        }
    }

    /** @test */
    public function each_sync_filter_returns_only_matching_rows(): void
    {
        $seed = $this->seedSyncFilterFixtures();

        $expectations = [
            WhatsappNumberMonitorService::SYNC_SYNCED => [$seed['syncedId']],
            WhatsappNumberMonitorService::SYNC_MISSING => [$seed['missingId']],
            WhatsappNumberMonitorService::SYNC_OWNER_MISMATCH => [$seed['mismatchId']],
            WhatsappNumberMonitorService::SYNC_NA => [$seed['naId']],
        ];

        foreach ($expectations as $sync => $expectedIds) {
            $result = $this->service->list(['sync' => $sync]);
            $ids = collect($result->items())->pluck('id')->map(fn ($id) => (int) $id)->all();
            sort($expectedIds);
            sort($ids);

            $this->assertSame($expectedIds, $ids, "Sync filter [{$sync}] returned unexpected rows.");
        }
    }

    /** @test */
    public function summary_counts_match_filter_totals(): void
    {
        $this->seedSummaryAndFilterAgreementFixtures();

        $summary = $this->service->summary();

        foreach (WhatsappNumberMonitorService::healthOptions() as $health) {
            $filtered = $this->service->list(['health' => $health]);
            $this->assertSame(
                $filtered->total(),
                $summary['counts'][$health],
                "Summary count mismatch for health [{$health}]"
            );
        }

        foreach ([WhatsappNumberMonitorService::SYNC_MISSING, WhatsappNumberMonitorService::SYNC_OWNER_MISMATCH] as $sync) {
            $filtered = $this->service->list(['sync' => $sync]);
            $this->assertSame(
                $filtered->total(),
                $summary['counts'][$sync],
                "Summary count mismatch for sync [{$sync}]"
            );
        }

        $this->assertSame(
            $this->service->list()->total(),
            $summary['counts']['total']
        );
    }

    /** @test */
    public function filtered_rows_carry_the_health_and_sync_value_they_were_filtered_by(): void
    {
        $this->seedSummaryAndFilterAgreementFixtures();

        $allRows = collect($this->service->list()->items());

        foreach (WhatsappNumberMonitorService::healthOptions() as $health) {
            $filtered = $this->service->list(['health' => $health]);

            $this->assertNotEmpty(
                $filtered->items(),
                "Health filter [{$health}] returned no rows."
            );

            foreach ($filtered->items() as $row) {
                $this->assertSame(
                    $health,
                    $row->health,
                    "Health filter [{$health}] included row id [{$row->id}] with health [{$row->health}]."
                );
            }

            $expectedIds = $allRows
                ->filter(fn ($row) => $row->health === $health)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->sort()
                ->values()
                ->all();

            $filteredIds = collect($filtered->items())
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->sort()
                ->values()
                ->all();

            $this->assertSame(
                $expectedIds,
                $filteredIds,
                "Health filter [{$health}] did not return all matching rows."
            );
        }

        foreach (WhatsappNumberMonitorService::syncOptions() as $sync) {
            $filtered = $this->service->list(['sync' => $sync]);

            $this->assertNotEmpty(
                $filtered->items(),
                "Sync filter [{$sync}] returned no rows."
            );

            foreach ($filtered->items() as $row) {
                $this->assertSame(
                    $sync,
                    $row->sync,
                    "Sync filter [{$sync}] included row id [{$row->id}] with sync [{$row->sync}]."
                );
            }

            $expectedIds = $allRows
                ->filter(fn ($row) => $row->sync === $sync)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->sort()
                ->values()
                ->all();

            $filteredIds = collect($filtered->items())
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->sort()
                ->values()
                ->all();

            $this->assertSame(
                $expectedIds,
                $filteredIds,
                "Sync filter [{$sync}] did not return all matching rows."
            );
        }
    }

    /** @test */
    public function q_search_matches_number_phone_id_username_and_email(): void
    {
        $tenantId = $this->createTenant();
        $username = 'search-user-' . Str::uuid();
        $email = 'search-' . Str::uuid() . '@example.test';

        DB::table('users')->where('id', $tenantId)->update([
            'username' => $username,
            'email' => $email,
        ]);

        $number = '+966599887766';
        $phoneId = 'search-phone-id-' . Str::uuid();
        $this->createNumber($tenantId, [
            'number' => $number,
            'phone_id' => $phoneId,
        ]);

        $searches = [
            substr($number, 4, 6) => 'number fragment',
            substr($phoneId, 7, 8) => 'phone id fragment',
            substr($username, 0, 12) => 'username fragment',
            substr($email, 0, 12) => 'email fragment',
        ];

        foreach ($searches as $term => $label) {
            $result = $this->service->list(['q' => $term]);
            $this->assertGreaterThanOrEqual(1, $result->total(), "Search by {$label} failed for term [{$term}]");
        }
    }

    /** @test */
    public function q_search_matches_whatsapp_user_id_and_tenant_owner_id(): void
    {
        $tenantId = $this->createTenant();
        $employeeId = $this->createEmployee($tenantId);
        $tenantNumberId = $this->createNumber($tenantId, ['phone_id' => 'search-by-tenant-id']);
        $employeeNumberId = $this->createNumber($employeeId, ['phone_id' => 'search-by-employee-tenant-id']);

        $otherTenantId = $this->createTenant();
        $this->createNumber($otherTenantId, ['phone_id' => 'search-noise']);

        $result = $this->service->list(['q' => (string) $tenantNumberId]);
        $ids = collect($result->items())->pluck('id')->map(fn ($id) => (int) $id)->all();
        $this->assertSame([$tenantNumberId], $ids);

        $result = $this->service->list(['q' => (string) $tenantId]);
        $ids = collect($result->items())->pluck('id')->map(fn ($id) => (int) $id)->all();
        $expectedIds = [$employeeNumberId, $tenantNumberId];
        sort($expectedIds);
        sort($ids);

        $this->assertSame($expectedIds, $ids);
    }

    /** @test */
    public function list_returns_length_aware_paginator_with_page_size_twenty_five(): void
    {
        $tenantId = $this->createTenant();

        for ($i = 0; $i < 26; $i++) {
            $this->createNumber($tenantId);
        }

        $paginator = $this->service->list();

        $this->assertInstanceOf(LengthAwarePaginator::class, $paginator);
        $this->assertSame(WhatsappNumberMonitorService::PER_PAGE, $paginator->perPage());
        $this->assertCount(25, $paginator->items());
        $this->assertSame(26, $paginator->total());
    }

    /** @test */
    public function list_and_summary_do_not_write_to_the_database(): void
    {
        $tenantId = $this->createTenant();
        $this->createNumber($tenantId);
        $this->createMessage($tenantId, 'inbound', now()->subHour());

        $tables = ['whatsapp_users', 'wa_numbers', 'messages', 'conversations'];
        $before = [];

        foreach ($tables as $table) {
            $before[$table] = DB::table($table)->count();
        }

        $this->service->list();
        $this->service->summary();

        foreach ($tables as $table) {
            $this->assertSame($before[$table], DB::table($table)->count(), "Unexpected writes to {$table}");
        }
    }

    /** @test */
    public function summary_reports_one_row_for_each_health_state(): void
    {
        $this->seedSummaryHealthFixtures();

        $summary = $this->service->summary();

        $this->assertSame(1, $summary['counts'][WhatsappNumberMonitorService::HEALTH_WORKING]);
        $this->assertSame(1, $summary['counts'][WhatsappNumberMonitorService::HEALTH_NO_RECENT_INBOUND]);
        $this->assertSame(1, $summary['counts'][WhatsappNumberMonitorService::HEALTH_NO_INBOUND_EVER]);
        $this->assertSame(1, $summary['counts'][WhatsappNumberMonitorService::HEALTH_NOT_LINKED]);
        $this->assertSame(1, $summary['counts'][WhatsappNumberMonitorService::SYNC_MISSING]);
        $this->assertSame(0, $summary['counts'][WhatsappNumberMonitorService::SYNC_OWNER_MISMATCH]);
        $this->assertSame(4, $summary['counts']['total']);
        $this->assertInstanceOf(Carbon::class, $summary['generated_at']);
    }

    /** @test */
    public function it_sorts_by_last_inbound_at_desc(): void
    {
        $tenantOld = $this->createTenant();
        $oldId = $this->createNumber($tenantOld);
        $this->createMessage($tenantOld, 'inbound', now()->subDays(3));

        $tenantRecent = $this->createTenant();
        $recentId = $this->createNumber($tenantRecent);
        $this->createMessage($tenantRecent, 'inbound', now()->subHour());

        $tenantNever = $this->createTenant();
        $neverId = $this->createNumber($tenantNever);

        $result = $this->service->list(['sort' => 'last_inbound_at', 'order' => 'desc']);
        $ids = collect($result->items())->pluck('id')->map(fn ($id) => (int) $id)->all();

        $this->assertSame([$recentId, $oldId, $neverId], $ids);
    }

    /** @test */
    public function it_sorts_by_last_inbound_at_asc_with_nulls_last(): void
    {
        $tenantOld = $this->createTenant();
        $oldId = $this->createNumber($tenantOld);
        $this->createMessage($tenantOld, 'inbound', now()->subDays(3));

        $tenantRecent = $this->createTenant();
        $recentId = $this->createNumber($tenantRecent);
        $this->createMessage($tenantRecent, 'inbound', now()->subHour());

        $tenantNever = $this->createTenant();
        $neverId = $this->createNumber($tenantNever);

        $result = $this->service->list(['sort' => 'last_inbound_at', 'order' => 'asc']);
        $ids = collect($result->items())->pluck('id')->map(fn ($id) => (int) $id)->all();

        $this->assertSame([$oldId, $recentId, $neverId], $ids);
    }

    /** @test */
    public function monitor_page_renders_for_authenticated_admin(): void
    {
        $this->ensureAdminViewData();

        $tenantId = $this->createTenant();
        $number = '+966511223344';
        $this->createNumber($tenantId, ['number' => $number]);

        $admin = Admin::factory()->create([
            'status' => true,
            'role_id' => null,
        ]);

        $this->withoutMiddleware([
            \App\Http\Middleware\CheckStatus::class,
            \App\Http\Middleware\Demo::class,
            VerifyCsrfToken::class,
        ]);

        $response = $this->actingAs($admin, 'admin')
            ->get(route('admin.whatsapp-numbers.monitor'));

        $response->assertOk();
        $response->assertSee($number, false);
    }

    private function ensureAdminViewData(): void
    {
        if (! Schema::hasTable('languages')) {
            return;
        }

        if (DB::table('languages')->exists()) {
            return;
        }

        $languageId = DB::table('languages')->insertGetId([
            'name' => 'English',
            'code' => 'en',
            'is_default' => 1,
            'rtl' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('basic_settings')->insert([
            'language_id' => $languageId,
            'website_title' => 'Taearif',
            'timezone' => 'UTC',
            'logo' => 'logo.png',
            'favicon' => 'favicon.png',
            'copyright_text' => 'Taearif',
        ]);

        DB::table('basic_extendeds')->insert([
            'language_id' => $languageId,
        ]);

        $currentLang = \App\Models\Language::query()
            ->with(['basic_setting', 'basic_extended'])
            ->where('is_default', 1)
            ->firstOrFail();

        View::share([
            'bs' => $currentLang->basic_setting,
            'be' => $currentLang->basic_extended,
            'currentLang' => $currentLang,
            'menus' => json_encode([]),
            'rtl' => 0,
            'socials' => collect(),
            'langs' => \App\Models\Language::all(),
            'adminLanguages' => \App\Models\Language::orderBy('is_default', 'desc')->get(),
            'admin_rtl' => false,
            'defaultLang' => $currentLang,
            'adminPermissions' => [],
        ]);
    }
}
