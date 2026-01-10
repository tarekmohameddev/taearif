<?php

namespace App\Http\Controllers\Api\Crm;

use App\Http\Controllers\Controller;
use App\Http\Requests\Crm\StoreReminderTypeRequest;
use App\Http\Requests\Crm\UpdateReminderTypeRequest;
use App\Http\Resources\Crm\ReminderTypeResource;
use App\Repositories\Crm\ReminderTypeRepository;
use App\Services\Crm\ReminderTypeService;
use App\Traits\BilingualResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ReminderTypeController extends Controller
{
    use BilingualResponse;

    protected ReminderTypeRepository $repository;
    protected ReminderTypeService $service;

    public function __construct(ReminderTypeRepository $repository, ReminderTypeService $service)
    {
        $this->repository = $repository;
        $this->service = $service;
    }

    /**
     * Display a listing of reminder types
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
                'search' => $request->get('search'),
                'is_active' => $request->has('is_active') ? filter_var($request->get('is_active'), FILTER_VALIDATE_BOOLEAN) : null,
            ];

            $paginated = $this->repository->paginate($tenantId, $filters, $perPage);

            $reminderTypes = ReminderTypeResource::collection($paginated->items());

            return $this->successResponse([
                'reminder_types' => $reminderTypes,
                'pagination' => [
                    'current_page' => $paginated->currentPage(),
                    'per_page' => $paginated->perPage(),
                    'total' => $paginated->total(),
                    'last_page' => $paginated->lastPage(),
                    'from' => $paginated->firstItem(),
                    'to' => $paginated->lastItem(),
                ],
            ], 'Reminder types retrieved successfully', 'تم استرجاع أنواع التذكير بنجاح');
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
     * Store a newly created reminder type
     *
     * @param StoreReminderTypeRequest $request
     * @return JsonResponse
     */
    public function store(StoreReminderTypeRequest $request): JsonResponse
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

            $reminderType = $this->repository->create($validated);

            return $this->successResponse(
                new ReminderTypeResource($reminderType),
                'Reminder type created successfully',
                'تم إنشاء نوع التذكير بنجاح',
                201
            );
        } catch (\Illuminate\Database\QueryException $e) {
            if ($e->getCode() == 23000) {
                return $this->errorResponse(
                    'A reminder type with this name already exists for your account.',
                    'يوجد بالفعل نوع تذكير بهذا الاسم في حسابك',
                    'DUPLICATE_NAME',
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
     * Display the specified reminder type
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

            $reminderType = $this->repository->findByIdForUser($id, $tenantId);

            if (!$reminderType) {
                return $this->notFoundResponse(
                    'Reminder type not found.',
                    'نوع التذكير غير موجود',
                    'REMINDER_TYPE_NOT_FOUND'
                );
            }

            return $this->successResponse(
                new ReminderTypeResource($reminderType),
                'Reminder type retrieved successfully',
                'تم استرجاع نوع التذكير بنجاح'
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
     * Update the specified reminder type
     *
     * @param UpdateReminderTypeRequest $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(UpdateReminderTypeRequest $request, int $id): JsonResponse
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

            $reminderType = $this->repository->findByIdForUser($id, $tenantId);

            if (!$reminderType) {
                return $this->notFoundResponse(
                    'Reminder type not found.',
                    'نوع التذكير غير موجود',
                    'REMINDER_TYPE_NOT_FOUND'
                );
            }

            // Check if trying to deactivate and has active reminders
            if (isset($request->is_active) && $request->is_active === false) {
                $canDeactivate = $this->service->canDeactivate($id);
                if (!$canDeactivate['can_deactivate']) {
                    return $this->errorResponse(
                        $canDeactivate['reason'],
                        $canDeactivate['reason_ar'],
                        'REMINDER_TYPE_IN_USE',
                        422
                    );
                }
            }

            $validated = $request->validated();
            $reminderType = $this->repository->update($reminderType, $validated);

            return $this->successResponse(
                new ReminderTypeResource($reminderType),
                'Reminder type updated successfully',
                'تم تحديث نوع التذكير بنجاح'
            );
        } catch (\Illuminate\Database\QueryException $e) {
            if ($e->getCode() == 23000) {
                return $this->errorResponse(
                    'A reminder type with this name already exists for your account.',
                    'يوجد بالفعل نوع تذكير بهذا الاسم في حسابك',
                    'DUPLICATE_NAME',
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
     * Remove the specified reminder type
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

            $reminderType = $this->repository->findByIdForUser($id, $tenantId);

            if (!$reminderType) {
                return $this->notFoundResponse(
                    'Reminder type not found.',
                    'نوع التذكير غير موجود',
                    'REMINDER_TYPE_NOT_FOUND'
                );
            }

            // Check if can be deleted
            $canDelete = $this->service->canDelete($id);
            if (!$canDelete['can_delete']) {
                return $this->errorResponse(
                    $canDelete['reason'],
                    $canDelete['reason_ar'],
                    'REMINDER_TYPE_IN_USE',
                    422
                );
            }

            $this->repository->delete($reminderType);

            return $this->successResponse(
                null,
                'Reminder type deleted successfully',
                'تم حذف نوع التذكير بنجاح'
            );
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
}
