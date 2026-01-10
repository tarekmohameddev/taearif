<?php

namespace App\Http\Controllers\Api\CRM;

use App\Http\Controllers\Controller;
use App\Http\Requests\Crm\StoreReminderRequest;
use App\Http\Requests\Crm\UpdateReminderRequest;
use App\Http\Resources\Crm\ReminderResource;
use App\Repositories\Crm\ReminderRepository;
use App\Services\Crm\ReminderService;
use App\Traits\BilingualResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ReminderController extends Controller
{
    use BilingualResponse;

    protected ReminderRepository $repository;
    protected ReminderService $service;

    public function __construct(ReminderRepository $repository, ReminderService $service)
    {
        $this->repository = $repository;
        $this->service = $service;
    }

    /**
     * Display a listing of reminders
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $tenantId = $user->tenantOwnerId();

            if ($tenantId <= 0) {
                return $this->forbiddenResponse(
                    'Invalid tenant. You must be associated with a tenant.',
                    'إيجار غير صالح. يجب أن تكون مرتبطًا بإيجار'
                );
            }

            $perPage = min($request->get('per_page', 50), 100);
            $page = max($request->get('page', 1), 1);

            $filters = [
                'customer_id' => $request->get('customer_id'),
                'reminder_type_id' => $request->get('reminder_type_id'),
                'status' => $request->get('status'),
                'priority' => $request->get('priority'),
                'date_from' => $request->get('date_from'),
                'date_to' => $request->get('date_to'),
                'search' => $request->get('search'),
            ];

            // Remove null filters
            $filters = array_filter($filters, fn($value) => $value !== null);

            // Auto-update overdue reminders before fetching
            $this->service->updateOverdueReminders($tenantId);

            $paginated = $this->repository->paginate($tenantId, $filters, $perPage);

            $reminders = ReminderResource::collection($paginated->items());

            return $this->successResponse([
                'reminders' => $reminders,
                'pagination' => [
                    'current_page' => $paginated->currentPage(),
                    'per_page' => $paginated->perPage(),
                    'total' => $paginated->total(),
                    'last_page' => $paginated->lastPage(),
                    'from' => $paginated->firstItem(),
                    'to' => $paginated->lastItem(),
                ],
            ], 'Reminders retrieved successfully', 'تم استرجاع التذكيرات بنجاح');
        } catch (\Exception $e) {
            return $this->errorResponse(
                'An unexpected error occurred. Please contact support if the problem persists.',
                'حدث خطأ غير متوقع. يرجى الاتصال بالدعم إذا استمرت المشكلة',
                'INTERNAL_SERVER_ERROR',
                500
            );
        }
    }

    /**
     * Store a newly created reminder
     *
     * @param StoreReminderRequest $request
     * @return JsonResponse
     */
    public function store(StoreReminderRequest $request): JsonResponse
    {
        try {
            $user = $request->user();
            $tenantId = $user->tenantOwnerId();

            if ($tenantId <= 0) {
                return $this->forbiddenResponse(
                    'Invalid tenant. You must be associated with a tenant.',
                    'إيجار غير صالح. يجب أن تكون مرتبطًا بإيجار'
                );
            }

            $validated = $request->validated();
            $validated['user_id'] = $tenantId;

            // Set default priority if not provided
            if (!isset($validated['priority'])) {
                $validated['priority'] = 1; // Medium
            }

            $reminder = $this->repository->create($validated);
            
            // Refresh with relationships
            $reminder = $this->repository->findByIdForUser($reminder->id, $tenantId);

            return $this->successResponse(
                new ReminderResource($reminder),
                'Reminder created successfully',
                'تم إنشاء التذكير بنجاح',
                201
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationErrorResponse($e->validator);
        } catch (\Illuminate\Database\QueryException $e) {
            if ($e->getCode() == 23000) {
                return $this->errorResponse(
                    'Cannot perform this action due to existing relationships with other records.',
                    'لا يمكن تنفيذ هذا الإجراء بسبب العلاقات الموجودة مع السجلات الأخرى',
                    'FOREIGN_KEY_CONSTRAINT',
                    422
                );
            }
            return $this->errorResponse(
                'An error occurred while processing your request. Please try again later.',
                'حدث خطأ أثناء معالجة طلبك. يرجى المحاولة مرة أخرى لاحقًا',
                'DATABASE_ERROR',
                500
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'An unexpected error occurred. Please contact support if the problem persists.',
                'حدث خطأ غير متوقع. يرجى الاتصال بالدعم إذا استمرت المشكلة',
                'INTERNAL_SERVER_ERROR',
                500
            );
        }
    }

    /**
     * Display the specified reminder
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function show(Request $request, int $id): JsonResponse
    {
        try {
            $user = $request->user();
            $tenantId = $user->tenantOwnerId();

            if ($tenantId <= 0) {
                return $this->forbiddenResponse(
                    'Invalid tenant. You must be associated with a tenant.',
                    'إيجار غير صالح. يجب أن تكون مرتبطًا بإيجار'
                );
            }

            $reminder = $this->repository->findByIdForUser($id, $tenantId);

            if (!$reminder) {
                return $this->notFoundResponse(
                    'Reminder not found.',
                    'التذكير غير موجود',
                    'REMINDER_NOT_FOUND'
                );
            }

            // Auto-update status if overdue
            $reminder = $this->service->updateReminderStatus($reminder);

            return $this->successResponse(
                new ReminderResource($reminder),
                'Reminder retrieved successfully',
                'تم استرجاع التذكير بنجاح'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'An unexpected error occurred. Please contact support if the problem persists.',
                'حدث خطأ غير متوقع. يرجى الاتصال بالدعم إذا استمرت المشكلة',
                'INTERNAL_SERVER_ERROR',
                500
            );
        }
    }

    /**
     * Update the specified reminder
     *
     * @param UpdateReminderRequest $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(UpdateReminderRequest $request, int $id): JsonResponse
    {
        try {
            $user = $request->user();
            $tenantId = $user->tenantOwnerId();

            if ($tenantId <= 0) {
                return $this->forbiddenResponse(
                    'Invalid tenant. You must be associated with a tenant.',
                    'إيجار غير صالح. يجب أن تكون مرتبطًا بإيجار'
                );
            }

            $reminder = $this->repository->findByIdForUser($id, $tenantId);

            if (!$reminder) {
                return $this->notFoundResponse(
                    'Reminder not found.',
                    'التذكير غير موجود',
                    'REMINDER_NOT_FOUND'
                );
            }

            $validated = $request->validated();

            // If status is being updated to completed, ensure we handle it properly
            if (isset($validated['status']) && $validated['status'] === 'completed') {
                // Status update is handled automatically
            }

            $reminder = $this->repository->update($reminder, $validated);

            // Auto-update status if overdue
            $reminder = $this->service->updateReminderStatus($reminder);

            return $this->successResponse(
                new ReminderResource($reminder),
                'Reminder updated successfully',
                'تم تحديث التذكير بنجاح'
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationErrorResponse($e->validator);
        } catch (\Illuminate\Database\QueryException $e) {
            if ($e->getCode() == 23000) {
                return $this->errorResponse(
                    'Cannot perform this action due to existing relationships with other records.',
                    'لا يمكن تنفيذ هذا الإجراء بسبب العلاقات الموجودة مع السجلات الأخرى',
                    'FOREIGN_KEY_CONSTRAINT',
                    422
                );
            }
            return $this->errorResponse(
                'An error occurred while processing your request. Please try again later.',
                'حدث خطأ أثناء معالجة طلبك. يرجى المحاولة مرة أخرى لاحقًا',
                'DATABASE_ERROR',
                500
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'An unexpected error occurred. Please contact support if the problem persists.',
                'حدث خطأ غير متوقع. يرجى الاتصال بالدعم إذا استمرت المشكلة',
                'INTERNAL_SERVER_ERROR',
                500
            );
        }
    }

    /**
     * Remove the specified reminder
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        try {
            $user = $request->user();
            $tenantId = $user->tenantOwnerId();

            if ($tenantId <= 0) {
                return $this->forbiddenResponse(
                    'Invalid tenant. You must be associated with a tenant.',
                    'إيجار غير صالح. يجب أن تكون مرتبطًا بإيجار'
                );
            }

            $reminder = $this->repository->findByIdForUser($id, $tenantId);

            if (!$reminder) {
                return $this->notFoundResponse(
                    'Reminder not found.',
                    'التذكير غير موجود',
                    'REMINDER_NOT_FOUND'
                );
            }

            $this->repository->delete($reminder);

            return $this->successResponse(
                null,
                'Reminder deleted successfully',
                'تم حذف التذكير بنجاح'
            );
        } catch (\Illuminate\Database\QueryException $e) {
            return $this->errorResponse(
                'An error occurred while processing your request. Please try again later.',
                'حدث خطأ أثناء معالجة طلبك. يرجى المحاولة مرة أخرى لاحقًا',
                'DATABASE_ERROR',
                500
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                'An unexpected error occurred. Please contact support if the problem persists.',
                'حدث خطأ غير متوقع. يرجى الاتصال بالدعم إذا استمرت المشكلة',
                'INTERNAL_SERVER_ERROR',
                500
            );
        }
    }
}
