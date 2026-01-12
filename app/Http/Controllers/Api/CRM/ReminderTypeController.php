<?php

namespace App\Http\Controllers\Api\CRM;

use App\Http\Controllers\Controller;
use App\Http\Requests\Crm\StoreReminderTypeRequest;
use App\Http\Requests\Crm\UpdateReminderTypeRequest;
use App\Http\Resources\Crm\ReminderTypeResource;
use App\Repositories\Crm\ReminderTypeRepository;
use App\Services\Crm\ReminderTypeService;
use App\Services\Crm\DefaultReminderTypeService;
use App\Traits\BilingualResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;

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

            // Get default types (hardcoded)
            $defaultTypes = collect(DefaultReminderTypeService::getDefaultTypes());
            
            // Filter default types based on filters
            if (isset($filters['search']) && $filters['search']) {
                $search = strtolower($filters['search']);
                $defaultTypes = $defaultTypes->filter(function ($type) use ($search) {
                    return stripos($type['name'], $search) !== false 
                        || stripos($type['name_ar'], $search) !== false;
                });
            }

            if (isset($filters['is_active'])) {
                $defaultTypes = $defaultTypes->filter(function ($type) use ($filters) {
                    return $type['is_active'] === $filters['is_active'];
                });
            }

            // Get database types (excluding default types that may have been auto-created)
            $dbTypes = $this->repository->getAll($tenantId, $filters)
                ->filter(function ($type) {
                    // Exclude auto-created default types from DB results to avoid duplicates
                    return !$type->is_default;
                })
                ->map(function ($type) {
                    // Ensure is_default is false for DB types
                    $type->is_default = false;
                    return $type;
                });

            // Convert defaults to collection of objects for resource compatibility
            $defaultTypesCollection = $defaultTypes->map(function ($type) {
                return (object) $type;
            });

            // Calculate totals
            $defaultCount = $defaultTypesCollection->count();
            $dbCount = $dbTypes->count();
            $total = $defaultCount + $dbCount;

            // Pagination: defaults always appear on first page
            $items = collect();
            if ($page === 1) {
                // Page 1: include all defaults + remaining slots from DB
                $items = $defaultTypesCollection;
                $dbSlots = max(0, $perPage - $defaultCount);
                if ($dbSlots > 0) {
                    $items = $items->merge($dbTypes->take($dbSlots));
                }
            } else {
                // Page > 1: skip defaults, show only DB types
                // Adjust offset: we already showed $defaultCount items on page 1
                $adjustedOffset = ($page - 1) * $perPage - $defaultCount;
                if ($adjustedOffset >= 0 && $adjustedOffset < $dbCount) {
                    $items = $dbTypes->slice($adjustedOffset, $perPage)->values();
                }
            }

            $reminderTypes = ReminderTypeResource::collection($items);

            // Calculate pagination metadata
            $lastPage = max(1, (int) ceil($total / $perPage));

            // Calculate from and to based on the actual items shown
            if ($page === 1) {
                $from = $total > 0 ? 1 : null;
                $to = min($items->count(), $total);
            } else {
                // For page > 1, calculate based on adjusted offset
                $adjustedOffset = ($page - 1) * $perPage - $defaultCount;
                $from = $total > 0 ? ($adjustedOffset + 1) : null;
                $to = min($adjustedOffset + $items->count(), $total);
            }

            return $this->successResponse([
                'reminder_types' => $reminderTypes,
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $perPage,
                    'total' => $total,
                    'last_page' => $lastPage,
                    'from' => $from,
                    'to' => $total > 0 ? $to : null,
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
