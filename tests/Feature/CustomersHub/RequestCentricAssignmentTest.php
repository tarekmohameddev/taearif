<?php

namespace Tests\Feature\CustomersHub;

use App\Domain\CustomersHub\Services\ActionsAggregatorService;
use App\Domain\CustomersHub\Services\AssignmentService;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class RequestCentricAssignmentTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        // This repo's full migration set is currently not runnable from scratch in the test DB.
        // For these tests we create the minimal schema needed for request-centric assignment.

        if (!Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table) {
                $table->id();
                $table->string('first_name')->nullable();
                $table->string('last_name')->nullable();
                $table->string('email')->nullable();
                $table->string('username')->nullable();
                $table->string('phone')->nullable();
                $table->boolean('email_verified')->default(false);
                $table->string('password');
                $table->string('remember_token')->nullable();
                $table->integer('status')->default(1);
                $table->string('account_type')->default('tenant');
                $table->boolean('active')->default(true);
                $table->unsignedBigInteger('tenant_id')->nullable();
                $table->string('referral_code')->nullable();
                $table->unsignedInteger('max_capacity')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('api_customers')) {
            Schema::create('api_customers', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->string('name');
                $table->string('email')->nullable();
                $table->string('phone_number');
                $table->string('password');
                $table->string('remember_token')->nullable();
                $table->unsignedBigInteger('responsible_employee_id')->nullable();
                $table->string('source')->nullable();
                $table->timestamp('deleted_at')->nullable();
                $table->timestamps();
            });
        }
        Schema::table('api_customers', function (Blueprint $table) {
            if (!Schema::hasColumn('api_customers', 'source')) {
                $table->string('source')->nullable()->after('responsible_employee_id');
            }
        });

        if (!Schema::hasTable('users_property_requests')) {
            Schema::create('users_property_requests', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->nullable();
                $table->unsignedBigInteger('customer_id')->nullable();
                $table->unsignedBigInteger('responsible_employee_id')->nullable();
                $table->string('full_name');
                $table->string('phone');
                $table->string('region')->nullable();
                $table->unsignedBigInteger('city_id')->nullable();
                $table->unsignedBigInteger('status_id')->nullable();
                $table->string('customers_hub_stage_id')->nullable();
                $table->boolean('is_read')->default(false);
                $table->boolean('is_archived')->default(false);
                $table->boolean('is_active')->default(true);
                $table->text('notes')->nullable();
                $table->string('property_type')->nullable();
                $table->unsignedBigInteger('category_id')->nullable();
                $table->decimal('budget_from', 15, 2)->nullable();
                $table->decimal('budget_to', 15, 2)->nullable();
                $table->string('seriousness')->nullable();
                $table->string('source')->nullable();
                $table->string('purpose')->nullable();
                $table->timestamps();
            });
        }
        Schema::table('users_property_requests', function (Blueprint $table) {
            if (!Schema::hasColumn('users_property_requests', 'purpose')) {
                $table->string('purpose')->nullable()->after('source');
            }
        });

        if (!Schema::hasTable('api_customer_inquiry')) {
            Schema::create('api_customer_inquiry', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->unsignedBigInteger('customer_id')->nullable();
                $table->unsignedBigInteger('responsible_employee_id')->nullable();
                $table->string('phone_number')->nullable();
                $table->string('inquiry_type')->nullable();
                $table->text('message')->nullable();
                $table->string('urgency')->nullable();
                $table->string('city')->nullable();
                $table->string('region_name')->nullable();
                $table->string('district')->nullable();
                $table->string('property_type')->nullable();
                $table->decimal('budget', 15, 2)->nullable();
                $table->unsignedInteger('bedrooms')->nullable();
                $table->unsignedInteger('bathrooms')->nullable();
                $table->string('stage_id')->nullable();
                $table->boolean('is_archived')->default(false);
                $table->boolean('is_read')->default(false);
                $table->timestamps();
            });
        }
        // Ensure columns exist even if table was created by earlier test run.
        Schema::table('api_customer_inquiry', function (Blueprint $table) {
            if (!Schema::hasColumn('api_customer_inquiry', 'inquiry_type')) {
                $table->string('inquiry_type')->nullable()->after('phone_number');
            }
            if (!Schema::hasColumn('api_customer_inquiry', 'message')) {
                $table->text('message')->nullable()->after('inquiry_type');
            }
            if (!Schema::hasColumn('api_customer_inquiry', 'urgency')) {
                $table->string('urgency')->nullable()->after('message');
            }
            if (!Schema::hasColumn('api_customer_inquiry', 'district')) {
                $table->string('district')->nullable()->after('region_name');
            }
            if (!Schema::hasColumn('api_customer_inquiry', 'bedrooms')) {
                $table->unsignedInteger('bedrooms')->nullable()->after('budget');
            }
            if (!Schema::hasColumn('api_customer_inquiry', 'bathrooms')) {
                $table->unsignedInteger('bathrooms')->nullable()->after('bedrooms');
            }
            if (!Schema::hasColumn('api_customer_inquiry', 'is_read')) {
                $table->boolean('is_read')->default(false)->after('is_archived');
            }
        });

        if (!Schema::hasTable('reminders')) {
            Schema::create('reminders', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->unsignedBigInteger('customer_id')->nullable();
                $table->string('title')->nullable();
                $table->text('description')->nullable();
                $table->string('type')->nullable();
                $table->string('status')->nullable();
                $table->timestamp('datetime')->nullable();
                $table->timestamp('snoozed_until')->nullable();
                $table->unsignedInteger('priority')->default(0);
                $table->string('source')->nullable();
                $table->unsignedBigInteger('reminder_type_id')->nullable();
                $table->text('notes')->nullable();
                $table->timestamp('deleted_at')->nullable();
                $table->timestamps();
            });
        }
        Schema::table('reminders', function (Blueprint $table) {
            if (!Schema::hasColumn('reminders', 'description')) {
                $table->text('description')->nullable()->after('title');
            }
            if (!Schema::hasColumn('reminders', 'snoozed_until')) {
                $table->timestamp('snoozed_until')->nullable()->after('datetime');
            }
            if (!Schema::hasColumn('reminders', 'priority')) {
                $table->unsignedInteger('priority')->default(0)->after('snoozed_until');
            }
            if (!Schema::hasColumn('reminders', 'source')) {
                $table->string('source')->nullable()->after('priority');
            }
            if (!Schema::hasColumn('reminders', 'reminder_type_id')) {
                $table->unsignedBigInteger('reminder_type_id')->nullable()->after('source');
            }
            if (!Schema::hasColumn('reminders', 'notes')) {
                $table->text('notes')->nullable()->after('reminder_type_id');
            }
        });

        if (!Schema::hasTable('user_cities')) {
            Schema::create('user_cities', function (Blueprint $table) {
                $table->id();
                $table->string('name_ar')->nullable();
            });
        }

        if (!Schema::hasTable('property_request_statuses')) {
            Schema::create('property_request_statuses', function (Blueprint $table) {
                $table->id();
                $table->string('slug')->nullable();
                $table->string('name_ar')->nullable();
                $table->string('name_en')->nullable();
            });
        }
        Schema::table('property_request_statuses', function (Blueprint $table) {
            if (!Schema::hasColumn('property_request_statuses', 'name_ar')) {
                $table->string('name_ar')->nullable()->after('slug');
            }
            if (!Schema::hasColumn('property_request_statuses', 'name_en')) {
                $table->string('name_en')->nullable()->after('name_ar');
            }
        });

        if (!Schema::hasTable('customers_hub_stages')) {
            Schema::create('customers_hub_stages', function (Blueprint $table) {
                $table->id();
                $table->string('stage_id')->unique();
                $table->string('stage_name_ar')->nullable();
                $table->string('stage_name_en')->nullable();
                $table->boolean('is_active')->default(true);
                $table->unsignedInteger('order')->default(1);
                $table->timestamps();
            });
        }
    }

    /** @test */
    public function it_assigns_property_request_without_customer_and_does_not_silently_skip(): void
    {
        $tenant = User::factory()->tenant()->create();
        $employee = User::factory()->employee()->create([
            'tenant_id' => $tenant->id,
            'active' => true,
        ]);

        $requestId = DB::table('users_property_requests')->insertGetId([
            'user_id' => $tenant->id,
            'full_name' => 'Test Lead',
            'phone' => '010000000',
            'is_active' => 1,
            'is_archived' => 0,
            'is_read' => 0,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
            'responsible_employee_id' => null,
        ]);

        $service = app(AssignmentService::class);
        $result = $service->manualAssign($tenant->id, ['property_request_' . $requestId], (string) $employee->id);

        $this->assertSame(1, $result['assignedCount']);
        $this->assertCount(1, $result['assignments']);
        $this->assertSame('property_request_' . $requestId, $result['assignments'][0]['requestId']);
        $this->assertNull($result['assignments'][0]['customerId']);
        $this->assertSame((string) $employee->id, $result['assignments'][0]['employeeId']);

        $this->assertSame(
            $employee->id,
            (int) DB::table('users_property_requests')->where('id', $requestId)->value('responsible_employee_id')
        );
    }

    /** @test */
    public function it_syncs_customer_assignment_when_property_request_has_customer(): void
    {
        $tenant = User::factory()->tenant()->create();
        $employee = User::factory()->employee()->create([
            'tenant_id' => $tenant->id,
            'active' => true,
        ]);

        $customerId = DB::table('api_customers')->insertGetId([
            'user_id' => $tenant->id,
            'name' => 'Customer A',
            'email' => null,
            'phone_number' => '010000001',
            'password' => bcrypt('secret'),
            'remember_token' => null,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
            'responsible_employee_id' => null,
        ]);

        $requestId = DB::table('users_property_requests')->insertGetId([
            'user_id' => $tenant->id,
            'customer_id' => $customerId,
            'full_name' => 'Lead With Customer',
            'phone' => '010000001',
            'is_active' => 1,
            'is_archived' => 0,
            'is_read' => 0,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
            'responsible_employee_id' => null,
        ]);

        $service = app(AssignmentService::class);
        $result = $service->manualAssign($tenant->id, ['property_request_' . $requestId], (string) $employee->id);

        $this->assertSame(1, $result['assignedCount']);

        $this->assertSame(
            $employee->id,
            (int) DB::table('users_property_requests')->where('id', $requestId)->value('responsible_employee_id')
        );
        $this->assertSame(
            $employee->id,
            (int) DB::table('api_customers')->where('id', $customerId)->value('responsible_employee_id')
        );
    }

    /** @test */
    public function actions_list_prefers_property_request_assignment_and_filters_by_it(): void
    {
        $tenant = User::factory()->tenant()->create();
        $employee = User::factory()->employee()->create([
            'tenant_id' => $tenant->id,
            'active' => true,
        ]);

        $requestId = DB::table('users_property_requests')->insertGetId([
            'user_id' => $tenant->id,
            'full_name' => 'Assigned Lead',
            'phone' => '010000002',
            'is_active' => 1,
            'is_archived' => 0,
            'is_read' => 0,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
            'responsible_employee_id' => $employee->id,
        ]);

        $aggregator = app(ActionsAggregatorService::class);
        $list = $aggregator->getList($tenant->id, [
            'assignees' => [$employee->id],
        ], 50, 0);

        $this->assertGreaterThanOrEqual(1, $list['total']);

        $matched = collect($list['items'])->firstWhere('id', 'property_request_' . $requestId);
        $this->assertNotNull($matched);
        $assignedTo = is_array($matched) ? ($matched['assignedTo'] ?? null) : ($matched->assignedTo ?? null);
        $this->assertSame($employee->id, (int) ($assignedTo ?? 0));
    }
}

