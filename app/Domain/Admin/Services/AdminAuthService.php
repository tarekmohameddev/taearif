<?php

namespace App\Domain\Admin\Services;

use App\Domain\Admin\Models\Admin;
use App\Domain\Admin\Repositories\AdminRepositoryInterface;
use App\Domain\Shared\Services\BaseService;
use App\Exceptions\AdminAuthException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

/**
 * Admin Authentication Service
 *
 * Handles admin authentication logic
 */
class AdminAuthService extends BaseService
{
    /**
     * @var AdminRepositoryInterface
     */
    protected AdminRepositoryInterface $adminRepository;

    /**
     * AdminAuthService constructor.
     *
     * @param AdminRepositoryInterface $adminRepository
     */
    public function __construct(AdminRepositoryInterface $adminRepository)
    {
        $this->adminRepository = $adminRepository;
    }

    /**
     * Authenticate admin and generate token
     *
     * @param string $email
     * @param string $password
     * @param string $deviceName
     * @return array
     * @throws AdminAuthException
     */
    public function login(string $email, string $password, string $deviceName = 'web'): array
    {
        // Find admin by email
        $admin = $this->adminRepository->findByEmail($email);

        // Check if admin exists
        if (!$admin) {
            throw new AdminAuthException('Invalid credentials', 'AUTH_INVALID_CREDENTIALS');
        }

        // Check if password is correct
        if (!Hash::check($password, $admin->password)) {
            throw new AdminAuthException('Invalid credentials', 'AUTH_INVALID_CREDENTIALS');
        }

        // Check if admin is active
        if (!$admin->isActive()) {
            throw new AdminAuthException('Account is inactive', 'AUTH_ACCOUNT_INACTIVE');
        }

        // Update last login
        $this->adminRepository->updateLastLogin($admin);

        // Generate Sanctum token
        $token = $admin->createToken($deviceName)->plainTextToken;

        return [
            'admin' => $admin->load('role'),
            'token' => $token,
        ];
    }

    /**
     * Logout admin (revoke current token)
     *
     * @param Admin $admin
     * @return bool
     */
    public function logout(Admin $admin): bool
    {
        // Revoke current access token
        $admin->currentAccessToken()->delete();

        return true;
    }

    /**
     * Logout from all devices (revoke all tokens)
     *
     * @param Admin $admin
     * @return bool
     */
    public function logoutAll(Admin $admin): bool
    {
        // Revoke all tokens
        $admin->tokens()->delete();

        return true;
    }

    /**
     * Get authenticated admin with role
     *
     * @param Admin $admin
     * @return Admin
     */
    public function me(Admin $admin): Admin
    {
        return $admin->load('role');
    }

    /**
     * Send password reset link
     *
     * @param string $email
     * @return string
     * @throws AdminAuthException
     */
    public function forgotPassword(string $email): string
    {
        // Find admin by email
        $admin = $this->adminRepository->findByEmail($email);

        if (!$admin) {
            throw new AdminAuthException('Admin not found', 'AUTH_ADMIN_NOT_FOUND');
        }

        // Send password reset notification
        // Note: You'll need to create a password reset notification
        $token = Str::random(64);

        // Store token in password_resets table
        \DB::table('password_resets')->updateOrInsert(
            ['email' => $email],
            [
                'email' => $email,
                'token' => Hash::make($token),
                'created_at' => now(),
            ]
        );

        // TODO: Send email with reset link
        // You can use Laravel's built-in password reset or custom notification

        return $token; // In production, don't return token, just success message
    }

    /**
     * Reset password with token
     *
     * @param string $email
     * @param string $token
     * @param string $password
     * @return bool
     * @throws AdminAuthException
     */
    public function resetPassword(string $email, string $token, string $password): bool
    {
        // Find admin
        $admin = $this->adminRepository->findByEmail($email);

        if (!$admin) {
            throw new AdminAuthException('Admin not found', 'AUTH_ADMIN_NOT_FOUND');
        }

        // Verify token
        $passwordReset = \DB::table('password_resets')
            ->where('email', $email)
            ->first();

        if (!$passwordReset) {
            throw new AdminAuthException('Invalid or expired token', 'AUTH_INVALID_TOKEN');
        }

        if (!Hash::check($token, $passwordReset->token)) {
            throw new AdminAuthException('Invalid or expired token', 'AUTH_INVALID_TOKEN');
        }

        // Check if token is not expired (24 hours)
        if (now()->diffInHours($passwordReset->created_at) > 24) {
            throw new AdminAuthException('Token has expired', 'AUTH_TOKEN_EXPIRED');
        }

        // Update password
        $admin->password = Hash::make($password);
        $admin->save();

        // Delete password reset token
        \DB::table('password_resets')->where('email', $email)->delete();

        // Revoke all existing tokens (logout from all devices)
        $admin->tokens()->delete();

        return true;
    }
}

