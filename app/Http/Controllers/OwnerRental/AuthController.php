<?php

namespace App\Http\Controllers\OwnerRental;

use App\Http\Controllers\Controller;
use App\Http\Requests\OwnerRental\ForgotPasswordRequest;
use App\Http\Requests\OwnerRental\OwnerRentalLoginRequest;
use App\Http\Requests\OwnerRental\OwnerRentalLogoutRequest;
use App\Http\Requests\OwnerRental\ResetPasswordRequest;
use App\Models\OwnerRental;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\PersonalAccessToken;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    /**
     * Handle owner rental login
     */
    public function login(OwnerRentalLoginRequest $request)
    {
        try {
            $validated = $request->validated();
            $ownerRental = OwnerRental::where('email', $validated['email'])->first();

            // Check if owner rental exists
            if (!$ownerRental) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid credentials',
                ], 401);
            }

            // Check if account is active
            if (!$ownerRental->is_active) {
                return response()->json([
                    'success' => false,
                    'message' => 'Your account has been deactivated. Please contact the administrator.',
                ], 403);
            }

            // Check password
            if (!Hash::check($validated['password'], $ownerRental->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid credentials',
                ], 401);
            }

            // Update last login
            $ownerRental->update(['last_login_at' => now()]);

            // Create token
            $token = null;
            if (method_exists($ownerRental, 'createToken')) {
                $tokenObj = call_user_func([$ownerRental, 'createToken'], 'owner-rental-token', ['owner-rental']);
                $token = $tokenObj->plainTextToken ?? null;
            }

            return response()->json([
                'success' => true,
                'message' => 'Login successful',
                'data' => [
                    'owner_rental' => $ownerRental,
                    'token' => $token,
                    'token_type' => 'Bearer',
                ],
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Login failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Handle owner rental logout
     */
    public function logout(OwnerRentalLogoutRequest $request)
    {
        try {
            $token = request()->bearerToken();
            if ($token) {
                $accessToken = PersonalAccessToken::findToken($token);
                if ($accessToken) {
                    $accessToken->delete();
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Logout successful',
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Logout failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get authenticated owner rental info
     */
    public function me()
    {
        try {
            $ownerRental = Auth::user();

            return response()->json([
                'success' => true,
                'data' => $ownerRental,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve user information',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Send password reset link
     */
    public function forgotPassword(ForgotPasswordRequest $request)
    {
        $validated = $request->validated();

        try {
            $ownerRental = OwnerRental::where('email', $validated['email'])->first();

            if (!$ownerRental) {
                return response()->json([
                    'success' => false,
                    'message' => 'No account found with this email address',
                ], 404);
            }

            // Generate password reset token
            $broker = Password::broker('owner_rentals');
            $token = method_exists($broker, 'createToken')
                ? call_user_func([$broker, 'createToken'], $ownerRental)
                : Str::random(64);

            // Here you would typically send an email with the reset link
            // For now, we'll return the token (in production, send via email)

            return response()->json([
                'success' => true,
                'message' => 'Password reset link sent to your email',
                'data' => [
                    'token' => $token, // Remove this in production
                    'email' => $validated['email'],
                ],
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send reset link',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Reset password
     */
    public function resetPassword(ResetPasswordRequest $request)
    {
        try {
            $status = Password::broker('owner_rentals')->reset(
                request()->only('email', 'password', 'password_confirmation', 'token'),
                function ($ownerRental, $password) {
                    $ownerRental->forceFill([
                        'password' => Hash::make($password)
                    ])->save();

                    // Revoke all tokens
                    $ownerRental->tokens()->delete();
                }
            );

            if ($status === Password::PASSWORD_RESET) {
                return response()->json([
                    'success' => true,
                    'message' => 'Password has been reset successfully',
                ], 200);
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to reset password',
                'error' => $status,
            ], 400);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to reset password',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}

