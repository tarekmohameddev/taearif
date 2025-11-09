<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Daily;

use App\Models\Api\UserApiCustomerReminder;
use App\Models\Api\UserApiCustomerAppointment;
use App\Models\Api\Rms\RmReminder;
use Illuminate\Support\Carbon;
use Tests\Feature\Admin\AdminApiTestCase;

class ManageDailyTest extends AdminApiTestCase
{
    /** @test */
    public function admin_can_view_daily_dashboard(): void
    {
        $this->signInAdmin();

        $response = $this->getJson(route('admin.api.daily.index'));

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'date',
                    'statistics' => ['reminders', 'appointments', 'rms_reminders', 'subscriptions'],
                    'today_summary',
                    'overdue_count',
                    'upcoming',
                ],
            ]);
    }

    /** @test */
    public function admin_can_list_reminders(): void
    {
        $this->signInAdmin();

        UserApiCustomerReminder::factory()->create(['title' => 'Important Reminder']);

        $response = $this->getJson(route('admin.api.daily.reminders.index'));

        $response->assertOk()
            ->assertJsonPath('data.data.0.title', 'Important Reminder');
    }

    /** @test */
    public function admin_can_view_specific_reminder(): void
    {
        $this->signInAdmin();

        $reminder = UserApiCustomerReminder::factory()->create([
            'title' => 'Call customer',
        ]);

        $this->getJson(route('admin.api.daily.reminders.show', $reminder->id))
            ->assertOk()
            ->assertJsonPath('data.title', 'Call customer');
    }

    /** @test */
    public function reminder_show_returns_not_found_for_missing_record(): void
    {
        $this->signInAdmin();

        $this->getJson(route('admin.api.daily.reminders.show', 999999))
            ->assertNotFound();
    }

    /** @test */
    public function admin_can_list_appointments(): void
    {
        $this->signInAdmin();

        UserApiCustomerAppointment::factory()->create([
            'title' => 'Strategy Meeting',
            'type' => 'meeting',
        ]);

        $this->getJson(route('admin.api.daily.appointments.index'))
            ->assertOk()
            ->assertJsonPath('data.data.0.title', 'Strategy Meeting');
    }

    /** @test */
    public function admin_can_view_appointment_details(): void
    {
        $this->signInAdmin();

        $appointment = UserApiCustomerAppointment::factory()->create([
            'title' => 'Review Call',
        ]);

        $this->getJson(route('admin.api.daily.appointments.show', $appointment->id))
            ->assertOk()
            ->assertJsonPath('data.title', 'Review Call');
    }

    /** @test */
    public function admin_can_view_rms_reminders(): void
    {
        $this->signInAdmin();

        RmReminder::factory()->create([
            'message' => 'Collect rent',
            'status' => 'pending',
        ]);

        $this->getJson(route('admin.api.daily.rms-reminders'))
            ->assertOk()
            ->assertJsonPath('data.data.0.message', 'Collect rent');
    }

    /** @test */
    public function admin_can_view_daily_statistics(): void
    {
        $this->signInAdmin();

        UserApiCustomerReminder::factory()->create([
            'datetime' => Carbon::now(),
        ]);

        $this->getJson(route('admin.api.daily.statistics'))
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'reminders' => ['total'],
                    'appointments',
                    'rms_reminders',
                    'subscriptions',
                ],
            ]);
    }

    /** @test */
    public function admin_can_view_overdue_items(): void
    {
        $this->signInAdmin();

        UserApiCustomerReminder::factory()->create([
            'datetime' => Carbon::now()->subDay(),
            'title' => 'Past reminder',
        ]);

        $this->getJson(route('admin.api.daily.overdue'))
            ->assertOk()
            ->assertJsonPath('data.reminders.0.title', 'Past reminder');
    }

    /** @test */
    public function admin_can_view_todays_tasks(): void
    {
        $this->signInAdmin();

        UserApiCustomerReminder::factory()->create([
            'datetime' => Carbon::now(),
            'title' => 'Today reminder',
        ]);

        $this->getJson(route('admin.api.daily.today'))
            ->assertOk()
            ->assertJsonPath('data.reminders.0.title', 'Today reminder');
    }
}

