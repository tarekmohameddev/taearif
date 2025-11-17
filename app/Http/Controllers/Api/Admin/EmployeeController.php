<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\BaseController;
use App\Http\Requests\Admin\Employee\StoreEmployeeRequest;
use App\Http\Requests\Admin\Employee\UpdateEmployeeRequest;
use App\Http\Requests\Admin\Employee\UpdatePasswordRequest;
use App\Http\Requests\Admin\Employee\UpdateRoleRequest;
use App\Http\Requests\Admin\Employee\UploadImageRequest;
use App\Http\Resources\Admin\Employee\EmployeeResource;
use App\Http\Resources\Admin\Employee\EmployeeCollection;
use App\Domain\Admin\Services\EmployeeService;
use App\Exceptions\ResourceNotFoundException;
use App\Exceptions\BusinessLogicException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Throwable;

/**
 * Employee Controller
 *
 * Handles admin user/employee management API endpoints
 * Note: "Employees" refers to admin users in this context
 */
class EmployeeController extends BaseController
{
    /**
     * @var EmployeeService
     */
    protected EmployeeService $employeeService;

    /**
     * EmployeeController constructor.
     *
     * @param EmployeeService $employeeService
     */
    public function __construct(EmployeeService $employeeService)
    {
        $this->employeeService = $employeeService;
    }

    /**
     * Display a listing of employees.
     * GET /api/v1/admin/employees
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $filters = $request->only([
                'search', 'status', 'role_id', 'order_by', 'order_dir'
            ]);
            $perPage = $request->query('per_page', 20);

            $employees = $this->employeeService->getAllEmployees($filters, $perPage);

            return $this->successResponse(
                new EmployeeCollection($employees),
                'Employees retrieved successfully.'
            );
        } catch (Throwable $e) {
            return $this->handleException($e, 'Failed to retrieve employees.');
        }
    }

    /**
     * Store a newly created employee.
     * POST /api/v1/admin/employees
     *
     * @param StoreEmployeeRequest $request
     * @return JsonResponse
     */
    public function store(StoreEmployeeRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();
            $image = $request->hasFile('image') ? $request->file('image') : null;
            $employee = $this->employeeService->createEmployee($data, $image);

            return $this->successResponse(
                new EmployeeResource($employee),
                'Employee created successfully.',
                Response::HTTP_CREATED
            );
        } catch (Throwable $e) {
            return $this->handleException($e, 'Failed to create employee.');
        }
    }

    /**
     * Upload employee profile image.
     * POST /api/v1/admin/employees/upload-image
     *
     * @param UploadImageRequest $request
     * @return JsonResponse
     */
    public function uploadImage(UploadImageRequest $request): JsonResponse
    {
        try {
            $image = $request->file('image');
            $filename = $this->employeeService->uploadImage($image);

            return $this->successResponse(
                [
                    'filename' => $filename,
                    'url' => asset('assets/admin/img/propics/' . $filename),
                ],
                'Image uploaded successfully.'
            );
        } catch (Throwable $e) {
            return $this->handleException($e, 'Failed to upload image.');
        }
    }

    /**
     * Display the specified employee.
     * GET /api/v1/admin/employees/{id}
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        try {
            $employee = $this->employeeService->getEmployeeById($id);

            return $this->successResponse(
                new EmployeeResource($employee),
                'Employee retrieved successfully.'
            );
        } catch (Throwable $e) {
            return $this->handleException($e, 'Failed to retrieve employee.');
        }
    }

    /**
     * Update the specified employee.
     * PUT /api/v1/admin/employees/{id}
     *
     * @param UpdateEmployeeRequest $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(UpdateEmployeeRequest $request, int $id): JsonResponse
    {
        try {
            $data = $request->validated();
            
            // Handle permissions: check if it's a string (form-data) or array (JSON)
            if ($request->has('permissions')) {
                $permissions = $request->input('permissions');
                
                // If it's a string, try to decode it as JSON
                if (is_string($permissions)) {
                    $decoded = json_decode($permissions, true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                        $data['permissions'] = $decoded;
                    } elseif (preg_match('/^\[.*\]$/', $permissions)) {
                        // If it looks like JSON array string, try to decode
                        $data['permissions'] = json_decode($permissions, true) ?: [];
                    }
                } elseif (is_array($permissions)) {
                    // Already an array, use it directly
                    $data['permissions'] = $permissions;
                }
            }
            
            // Handle image: either file upload OR string path
            $image = null;
            if ($request->hasFile('image')) {
                // Image is a file upload - pass as $image parameter
                $image = $request->file('image');
                // Remove from data array since we'll handle it via $image parameter
                unset($data['image']);
            } elseif (isset($data['image']) && is_string($data['image']) && !empty($data['image'])) {
                // Image is a string path - already in validated data, keep it there
                // Don't pass to service as $image parameter (will be null)
            }
            
            $employee = $this->employeeService->updateEmployeeById($id, $data, $image);

            return $this->successResponse(
                new EmployeeResource($employee),
                'Employee updated successfully.'
            );
        } catch (Throwable $e) {
            return $this->handleException($e, 'Failed to update employee.');
        }
    }

    /**
     * Remove the specified employee.
     * DELETE /api/v1/admin/employees/{id}
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->employeeService->deleteEmployeeById($id);

            return $this->successResponse(
                null,
                'Employee deleted successfully.'
            );
        } catch (Throwable $e) {
            return $this->handleException($e, 'Failed to delete employee.');
        }
    }

    /**
     * Update employee password.
     * PUT /api/v1/admin/employees/{id}/password
     *
     * @param UpdatePasswordRequest $request
     * @param int $id
     * @return JsonResponse
     */
    public function updatePassword(UpdatePasswordRequest $request, int $id): JsonResponse
    {
        try {
            $employee = $this->employeeService->updatePasswordById($id, $request->validated()['password']);

            return $this->successResponse(
                new EmployeeResource($employee),
                'Password updated successfully.'
            );
        } catch (Throwable $e) {
            return $this->handleException($e, 'Failed to update employee password.');
        }
    }

    /**
     * Toggle employee status (activate/deactivate).
     * POST /api/v1/admin/employees/{id}/toggle-status
     *
     * @param int $id
     * @return JsonResponse
     */
    public function toggleStatus(int $id): JsonResponse
    {
        try {
            $employee = $this->employeeService->toggleStatusById($id);

            return $this->successResponse(
                new EmployeeResource($employee),
                'Employee status updated successfully.'
            );
        } catch (Throwable $e) {
            return $this->handleException($e, 'Failed to toggle employee status.');
        }
    }

    /**
     * Update employee role.
     * PUT /api/v1/admin/employees/{id}/role
     *
     * @param UpdateRoleRequest $request
     * @param int $id
     * @return JsonResponse
     */
    public function updateRole(UpdateRoleRequest $request, int $id): JsonResponse
    {
        try {
            $employee = $this->employeeService->updateRoleById($id, $request->validated()['role_id']);

            return $this->successResponse(
                new EmployeeResource($employee),
                'Employee role updated successfully.'
            );
        } catch (Throwable $e) {
            return $this->handleException($e, 'Failed to update employee role.');
        }
    }

    /**
     * Get available roles.
     * GET /api/v1/admin/employees/roles
     *
     * @return JsonResponse
     */
    public function roles(): JsonResponse
    {
        try {
            $roles = $this->employeeService->getRoles();

            return $this->successResponse(
                $roles,
                'Roles retrieved successfully.'
            );
        } catch (Throwable $e) {
            return $this->handleException($e, 'Failed to retrieve roles.');
        }
    }

    /**
     * Get employee statistics.
     * GET /api/v1/admin/employees/statistics
     *
     * @return JsonResponse
     */
    public function statistics(): JsonResponse
    {
        try {
            $statistics = $this->employeeService->getStatistics();

            return $this->successResponse(
                $statistics,
                'Statistics retrieved successfully.'
            );
        } catch (Throwable $e) {
            return $this->handleException($e, 'Failed to retrieve employee statistics.');
        }
    }

    /**
     * Centralized error handling for employee endpoints.
     */
    protected function handleException(Throwable $e, string $fallbackMessage): JsonResponse
    {
        if ($e instanceof \Illuminate\Validation\ValidationException) {
            throw $e;
        }

        if ($e instanceof ResourceNotFoundException) {
            return $this->errorResponse(
                $e->getMessage(),
                'NOT_FOUND',
                Response::HTTP_NOT_FOUND
            );
        }

        if ($e instanceof BusinessLogicException) {
            return $this->errorResponse(
                $e->getMessage(),
                $e->getErrorCode(),
                $e->getCode() ?: Response::HTTP_UNPROCESSABLE_ENTITY,
                ['error_code' => $e->getErrorCode()]
            );
        }

        return $this->errorResponse(
            $fallbackMessage,
            'EMPLOYEE_ERROR',
            Response::HTTP_INTERNAL_SERVER_ERROR,
            ['error' => $e->getMessage()]
        );
    }
}

