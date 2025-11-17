<?php

namespace App\Domain\Admin\Services;

use App\Domain\Admin\Models\Admin;
use App\Domain\Admin\Models\Role;
use App\Domain\Admin\Repositories\AdminRepositoryInterface;
use App\Domain\Shared\Services\BaseService;
use App\Exceptions\ResourceNotFoundException;
use App\Exceptions\BusinessLogicException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Employee Service
 *
 * Handles admin user/employee management business logic
 * Note: "Employees" in admin context refers to admin users
 */
class EmployeeService extends BaseService
{
    /**
     * @var AdminRepositoryInterface
     */
    protected AdminRepositoryInterface $adminRepository;

    /**
     * EmployeeService constructor.
     *
     * @param AdminRepositoryInterface $adminRepository
     */
    public function __construct(AdminRepositoryInterface $adminRepository)
    {
        $this->adminRepository = $adminRepository;
    }

    /**
     * Get all admin users with filters
     *
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getAllEmployees(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Admin::with('role')
            ->whereNotNull('role_id');

        // Status filter
        if (isset($filters['status'])) {
            if ($filters['status'] === 'active') {
                $query->where('status', true);
            } elseif ($filters['status'] === 'inactive') {
                $query->where('status', false);
            }
        }

        // Role filter
        if (isset($filters['role_id'])) {
            $query->where('role_id', $filters['role_id']);
        }

        // Search filter
        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function($q) use ($search) {
                $q->where('username', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%");
            });
        }

        // Sorting
        $orderBy = $filters['order_by'] ?? 'created_at';
        $orderDir = $filters['order_dir'] ?? 'desc';
        $query->orderBy($orderBy, $orderDir);

        return $query->paginate($perPage);
    }

    /**
     * Get employee by ID
     *
     * @param int $id
     * @return Admin
     * @throws ResourceNotFoundException
     */
    public function getEmployeeById(int $id): Admin
    {
        $admin = Admin::with('role')->find($id);

        return $this->ensureFound($admin, 'Employee not found');
    }

    /**
     * Create new admin user
     *
     * @param array $data
     * @param string|null $image
     * @return Admin
     * @throws BusinessLogicException
     */
    public function createEmployee(array $data, $image = null): Admin
    {
        // Check if email already exists
        if ($this->adminRepository->findByEmail($data['email'])) {
            $this->fail('Email already exists', 'EMPLOYEE_EMAIL_EXISTS', 422);
        }

        // Check if username already exists
        if (Admin::where('username', $data['username'])->exists()) {
            $this->fail('Username already exists', 'EMPLOYEE_USERNAME_EXISTS', 422);
        }

        // Hash password
        $data['password'] = Hash::make($data['password']);

        // Set default status if not provided
        $data['status'] = $data['status'] ?? true;

        // Handle permissions: normalize to array if provided
        if (isset($data['permissions'])) {
            if ($data['permissions'] === null) {
                $data['permissions'] = null;
            } elseif (is_array($data['permissions'])) {
                // Remove empty values and re-index array
                $data['permissions'] = array_values(array_filter($data['permissions']));
            } else {
                // Invalid format, set to empty array
                $data['permissions'] = [];
            }
        }

        // Handle image upload
        if ($image) {
            $data['image'] = $this->handleImageUpload($image);
        }

        return $this->executeInTransaction(function () use ($data) {
            return Admin::create($data);
        });
    }

    /**
     * Update admin user
     *
     * @param string $uuid
     * @param array $data
     * @param string|null $image
     * @return Admin
     * @throws ResourceNotFoundException
     * @throws BusinessLogicException
     */
    public function updateEmployee(int $id, array $data, $image = null): Admin
    {
        $admin = $this->getEmployeeById($id);

        // Prevent deleting owner (ID 1)
        if ($admin->id == 1 && isset($data['status']) && $data['status'] == false) {
            $this->fail('Cannot deactivate owner account', 'EMPLOYEE_OWNER_PROTECTED', 422);
        }

        // Check if email is being changed and already exists
        if (isset($data['email']) && $data['email'] !== $admin->email) {
            $existingAdmin = $this->adminRepository->findByEmail($data['email']);
            if ($existingAdmin && $existingAdmin->id !== $id) {
                $this->fail('Email already exists', 'EMPLOYEE_EMAIL_EXISTS', 422);
            }
        }

        // Check if username is being changed and already exists
        if (isset($data['username']) && $data['username'] !== $admin->username) {
            if (Admin::where('username', $data['username'])->where('id', '!=', $admin->id)->exists()) {
                $this->fail('Username already exists', 'EMPLOYEE_USERNAME_EXISTS', 422);
            }
        }

        // Don't allow changing password through update (use separate endpoint)
        unset($data['password']);

        // Handle image upload
        if ($image) {
            // Delete old image if exists
            if ($admin->image) {
                $oldImagePath = public_path('assets/admin/img/propics/' . $admin->image);
                if (file_exists($oldImagePath)) {
                    @unlink($oldImagePath);
                }
            }
            $data['image'] = $this->handleImageUpload($image);
        }

        return $this->executeInTransaction(function () use ($admin, $data) {
            $admin->update($data);
            return $admin->fresh(['role']);
        });
    }

    /**
     * Update admin user by ID
     *
     * @param int $id
     * @param array $data
     * @param \Illuminate\Http\UploadedFile|string|null $image
     * @return Admin
     * @throws ResourceNotFoundException
     * @throws BusinessLogicException
     */
    public function updateEmployeeById(int $id, array $data, $image = null): Admin
    {
        $admin = $this->getEmployeeById($id);

        // Prevent deleting owner (ID 1)
        if ($admin->id == 1 && isset($data['status']) && $data['status'] == false) {
            $this->fail('Cannot deactivate owner account', 'EMPLOYEE_OWNER_PROTECTED', 422);
        }

        // Check if email is being changed and already exists
        if (isset($data['email']) && $data['email'] !== $admin->email) {
            $existingAdmin = $this->adminRepository->findByEmail($data['email']);
            if ($existingAdmin && (int)$existingAdmin->id !== (int)$admin->id) {
                $this->fail('Email already exists', 'EMPLOYEE_EMAIL_EXISTS', 422);
            }
        }

        // Check if username is being changed and already exists
        if (isset($data['username']) && $data['username'] !== $admin->username) {
            if (Admin::where('username', $data['username'])->where('id', '!=', $admin->id)->exists()) {
                $this->fail('Username already exists', 'EMPLOYEE_USERNAME_EXISTS', 422);
            }
        }

        // Don't allow changing password through update (use separate endpoint)
        unset($data['password']);

        // Handle permissions: normalize to array if provided
        if (isset($data['permissions'])) {
            // Allow null to clear custom permissions (use role defaults)
            if ($data['permissions'] === null) {
                $data['permissions'] = null;
            } elseif (is_array($data['permissions'])) {
                // Remove empty values and re-index array
                $data['permissions'] = array_values(array_filter($data['permissions']));
            } else {
                // Invalid format, set to empty array
                $data['permissions'] = [];
            }
        }

        // Handle image: either file upload OR string path
        if ($image) {
            // Image is a file - upload it
            // Delete old image if exists
            if ($admin->image) {
                $oldImagePath = public_path('assets/admin/img/propics/' . $admin->image);
                if (file_exists($oldImagePath)) {
                    @unlink($oldImagePath);
                }
            }
            $data['image'] = $this->handleImageUpload($image);
        } elseif (isset($data['image']) && is_string($data['image']) && !empty($data['image'])) {
            // Image is a string path - delete old image if exists and different
            if ($admin->image && $admin->image !== $data['image']) {
                $oldImagePath = public_path('assets/admin/img/propics/' . $admin->image);
                if (file_exists($oldImagePath)) {
                    @unlink($oldImagePath);
                }
            }
            // $data['image'] is already set, no need to modify it
        }
        
        return $this->executeInTransaction(function () use ($admin, $data) {
            $result = $admin->update($data);            
            return $admin->fresh(['role']);
        });
    }

    /**
     * Handle image upload
     *
     * @param \Illuminate\Http\UploadedFile $image
     * @return string
     */
    protected function handleImageUpload($image): string
    {
        $name = uniqid() . '.' . $image->getClientOriginalExtension();
        $directory = public_path('assets/admin/img/propics/');
        
        // Ensure directory exists
        if (!file_exists($directory)) {
            mkdir($directory, 0775, true);
        }
        
        $image->move($directory, $name);
        return $name;
    }

    /**
     * Upload employee profile image (public method for API endpoint)
     *
     * @param \Illuminate\Http\UploadedFile $image
     * @return string Filename of uploaded image
     */
    public function uploadImage($image): string
    {
        return $this->handleImageUpload($image);
    }

    /**
     * Delete admin user
     *
     * @param int $id
     * @return bool
     * @throws ResourceNotFoundException
     * @throws BusinessLogicException
     */
    public function deleteEmployee(int $id): bool
    {
        $admin = $this->getEmployeeById($id);

        // Prevent deleting owner (ID 1)
        if ($admin->id == 1) {
            $this->fail('Cannot delete owner account', 'EMPLOYEE_OWNER_PROTECTED', 422);
        }

        return $this->executeInTransaction(function () use ($admin) {
            // Delete profile image if exists
            if ($admin->image) {
                $imagePath = public_path('assets/admin/img/propics/' . $admin->image);
                if (file_exists($imagePath)) {
                    @unlink($imagePath);
                }
            }

            // Revoke all tokens
            $admin->tokens()->delete();

            return $admin->delete();
        });
    }

    /**
     * Delete admin user by ID
     *
     * @param int $id
     * @return bool
     * @throws ResourceNotFoundException
     * @throws BusinessLogicException
     */
    public function deleteEmployeeById(int $id): bool
    {
        $admin = $this->getEmployeeById($id);

        // Prevent deleting owner (ID 1)
        if ($admin->id == 1) {
            $this->fail('Cannot delete owner account', 'EMPLOYEE_OWNER_PROTECTED', 422);
        }

        return $this->executeInTransaction(function () use ($admin) {
            // Delete profile image if exists
            if ($admin->image) {
                $imagePath = public_path('assets/admin/img/propics/' . $admin->image);
                if (file_exists($imagePath)) {
                    @unlink($imagePath);
                }
            }

            // Revoke all tokens
            $admin->tokens()->delete();

            return $admin->delete();
        });
    }

    /**
     * Update employee password
     *
     * @param int $id
     * @param string $newPassword
     * @return Admin
     * @throws ResourceNotFoundException
     */
    public function updatePassword(int $id, string $newPassword): Admin
    {
        $admin = $this->getEmployeeById($id);

        return $this->executeInTransaction(function () use ($admin, $newPassword) {
            $admin->password = Hash::make($newPassword);
            $admin->save();

            // Revoke all existing tokens (force re-login)
            $admin->tokens()->delete();

            return $admin->fresh(['role']);
        });
    }

    /**
     * Update employee password by ID
     *
     * @param int $id
     * @param string $newPassword
     * @return Admin
     * @throws ResourceNotFoundException
     */
    public function updatePasswordById(int $id, string $newPassword): Admin
    {
        $admin = $this->getEmployeeById($id);

        return $this->executeInTransaction(function () use ($admin, $newPassword) {
            $admin->password = Hash::make($newPassword);
            $admin->save();

            // Revoke all existing tokens (force re-login)
            $admin->tokens()->delete();

            return $admin->fresh(['role']);
        });
    }

    /**
     * Toggle employee status (activate/deactivate)
     *
     * @param int $id
     * @return Admin
     * @throws ResourceNotFoundException
     * @throws BusinessLogicException
     */
    public function toggleStatus(int $id): Admin
    {
        $admin = $this->getEmployeeById($id);

        // Prevent deactivating owner
        if ($admin->id == 1 && $admin->status == true) {
            $this->fail('Cannot deactivate owner account', 'EMPLOYEE_OWNER_PROTECTED', 422);
        }

        return $this->executeInTransaction(function () use ($admin) {
            $admin->status = !$admin->status;
            $admin->save();

            // If deactivating, revoke all tokens
            if (!$admin->status) {
                $admin->tokens()->delete();
            }

            return $admin->fresh(['role']);
        });
    }

    /**
     * Toggle employee status by ID (activate/deactivate)
     *
     * @param int $id
     * @return Admin
     * @throws ResourceNotFoundException
     * @throws BusinessLogicException
     */
    public function toggleStatusById(int $id): Admin
    {
        $admin = $this->getEmployeeById($id);

        // Prevent deactivating owner
        if ($admin->id == 1 && $admin->status == true) {
            $this->fail('Cannot deactivate owner account', 'EMPLOYEE_OWNER_PROTECTED', 422);
        }

        return $this->executeInTransaction(function () use ($admin) {
            $admin->status = !$admin->status;
            $admin->save();

            // If deactivating, revoke all tokens
            if (!$admin->status) {
                $admin->tokens()->delete();
            }

            return $admin->fresh(['role']);
        });
    }

    /**
     * Update employee roles
     *
     * @param int $id
     * @param int $roleId
     * @return Admin
     * @throws ResourceNotFoundException
     * @throws BusinessLogicException
     */
    public function updateRole(int $id, int $roleId): Admin
    {
        $admin = $this->getEmployeeById($id);

        // Verify role exists
        $this->ensureFound(Role::find($roleId), 'Role not found');

        return $this->executeInTransaction(function () use ($admin, $roleId) {
            $admin->role_id = $roleId;
            $admin->save();

            return $admin->fresh(['role']);
        });
    }

    /**
     * Update employee roles by ID
     *
     * @param int $id
     * @param int $roleId
     * @return Admin
     * @throws ResourceNotFoundException
     * @throws BusinessLogicException
     */
    public function updateRoleById(int $id, int $roleId): Admin
    {
        $admin = $this->getEmployeeById($id);

        // Verify role exists
        $this->ensureFound(Role::find($roleId), 'Role not found');

        return $this->executeInTransaction(function () use ($admin, $roleId) {
            $admin->role_id = $roleId;
            $admin->save();

            return $admin->fresh(['role']);
        });
    }

    /**
     * Get all available roles
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getRoles()
    {
        return Role::all();
    }

    /**
     * Get employee statistics
     *
     * @return array
     */
    public function getStatistics(): array
    {
        return [
            'total' => Admin::count(),
            'active' => Admin::where('status', true)->count(),
            'inactive' => Admin::where('status', false)->count(),
            'by_role' => Admin::selectRaw('role_id, COUNT(*) as count')
                ->groupBy('role_id')
                ->with('role')
                ->get()
                ->map(function($item) {
                    return [
                        'role_id' => $item->role_id,
                        'role_name' => $item->role?->name,
                        'count' => $item->count,
                    ];
                }),
        ];
    }
}

