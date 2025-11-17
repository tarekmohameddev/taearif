<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\BaseController;
use App\Domain\Daily\Services\DailyService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

/**
 * Daily Follow-up Controller
 *
 * Provides unified view of all daily tasks, reminders, and follow-ups
 * across the platform for admin dashboard
 */
class DailyController extends BaseController
{
    /**
     * @var DailyService
     */
    protected DailyService $dailyService;

    /**
     * DailyController constructor.
     *
     * @param DailyService $dailyService
     */
    public function __construct(DailyService $dailyService)
    {
        $this->dailyService = $dailyService;
    }

    /**
     * Get unified daily follow-up dashboard
     * GET /api/v1/admin/daily
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $filters = $request->only([
                'date',
                'type',
                'priority',
                'status',
                'user_id',
            ]);

            $dashboard = $this->dailyService->getDailyDashboard($filters);

            return $this->successResponse(
                $dashboard,
                'Daily dashboard retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                $e->getMessage(),
                'SYS_ERROR',
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Get all reminders with filters
     * GET /api/v1/admin/daily/reminders
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function reminders(Request $request): JsonResponse
    {
        try {
            $filters = $request->only([
                'type',
                'priority',
                'status',
                'user_id',
                'from_date',
                'to_date',
                'search',
            ]);

            $perPage = $request->query('per_page', 20);

            $reminders = $this->dailyService->getAllReminders($filters, $perPage);

            return $this->successResponse(
                $reminders,
                'Reminders retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                $e->getMessage(),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Get reminder by ID
     * GET /api/v1/admin/daily/reminders/{id}
     *
     * @param int $id
     * @return JsonResponse
     */
    public function showReminder(int $id): JsonResponse
    {
        try {
            $reminder = $this->dailyService->getReminderById($id);

            if (!$reminder) {
                return $this->errorResponse(
                    'Reminder not found',
                    'NOT_FOUND',
                    Response::HTTP_NOT_FOUND
                );
            }

            return $this->successResponse(
                $reminder,
                'Reminder retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                $e->getMessage(),
                'SYS_ERROR',
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Get all appointments with filters
     * GET /api/v1/admin/daily/appointments
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function appointments(Request $request): JsonResponse
    {
        try {
            $filters = $request->only([
                'type',
                'priority',
                'user_id',
                'from_date',
                'to_date',
                'search',
            ]);

            $perPage = $request->query('per_page', 20);

            $appointments = $this->dailyService->getAllAppointments($filters, $perPage);

            return $this->successResponse(
                $appointments,
                'Appointments retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                $e->getMessage(),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Get appointment by ID
     * GET /api/v1/admin/daily/appointments/{id}
     *
     * @param int $id
     * @return JsonResponse
     */
    public function showAppointment(int $id): JsonResponse
    {
        try {
            $appointment = $this->dailyService->getAppointmentById($id);

            if (!$appointment) {
                return $this->errorResponse(
                    'Appointment not found',
                    'NOT_FOUND',
                    Response::HTTP_NOT_FOUND
                );
            }

            return $this->successResponse(
                $appointment,
                'Appointment retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                $e->getMessage(),
                'SYS_ERROR',
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Get RMS reminders (rental management)
     * GET /api/v1/admin/daily/rms-reminders
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function rmsReminders(Request $request): JsonResponse
    {
        try {
            $filters = $request->only([
                'type',
                'status',
                'user_id',
                'from_date',
                'to_date',
            ]);

            $perPage = $request->query('per_page', 20);

            $rmsReminders = $this->dailyService->getRmsReminders($filters, $perPage);

            return $this->successResponse(
                $rmsReminders,
                'RMS reminders retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                $e->getMessage(),
                'SYS_ERROR',
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Get daily statistics
     * GET /api/v1/admin/daily/statistics
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function statistics(Request $request): JsonResponse
    {
        try {
            $date = $request->query('date', now()->toDateString());

            $statistics = $this->dailyService->getStatistics($date);

            return $this->successResponse(
                $statistics,
                'Statistics retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                $e->getMessage(),
                'SYS_ERROR',
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Get overdue items
     * GET /api/v1/admin/daily/overdue
     *
     * @return JsonResponse
     */
    public function overdue(): JsonResponse
    {
        try {
            $overdue = $this->dailyService->getOverdueItems();

            return $this->successResponse(
                $overdue,
                'Overdue items retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                $e->getMessage(),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Get today's tasks
     * GET /api/v1/admin/daily/today
     *
     * @return JsonResponse
     */
    public function today(): JsonResponse
    {
        try {
            $today = $this->dailyService->getTodaysTasks();

            return $this->successResponse(
                $today,
                'Today\'s tasks retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                $e->getMessage(),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * GET /api/v1/admin/follow-up
     * Returns counts for the four daily follow-up cards.
     */
    public function followUp(Request $request): JsonResponse
    {
        try {
            $windowDays = (int) $request->query('window_days', 30);
            $tableLimit = (int) $request->query('table_limit', 50);

            $payload = $this->dailyService->getFollowUpOverview($windowDays, $tableLimit);

            return $this->successResponse(
                $payload,
                'Daily follow-up retrieved successfully.'
            );
        } catch (\Throwable $e) {
            return $this->errorResponse(
                config('app.debug') ? $e->getMessage() : 'Failed to retrieve follow-up data.',
                'SYS_ERROR',
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}

