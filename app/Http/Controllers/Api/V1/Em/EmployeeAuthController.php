<?php
namespace App\Http\Controllers\Api\V1\Em;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class EmployeeAuthController extends Controller
{

    /**
     * POST /api/v1/em/auth/register
     * - Requires a TENANT token (owner) to create an employee under their account.
     * - Creates a user row with account_type=employee and tenant_id = current tenant id.
     */
    public function register(Request $request)
    {
        $tenant = $request->user();

        if (!$tenant || !$tenant->isTenant()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Only tenant owners can create employees.',
            ], 403);
        }

        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name'  => ['nullable', 'string', 'max:100'],
            'email'      => [
                'required', 'email', 'max:255',
                'unique:users,email',
            ],
            'password'   => ['required', 'string', 'min:6', 'confirmed'],
            'phone'      => ['nullable', 'string', 'max:50'],
            'active'     => ['nullable', 'boolean'],
        ]);

        $employee = new User();
        $employee->first_name   = $validated['first_name'];
        $employee->last_name    = $validated['last_name'] ?? null;
        $employee->email        = $validated['email'];
        $employee->phone        = $request->input('phone');
        $employee->password     = Hash::make($validated['password']);

        // Multi-tenant flags
        $employee->tenant_id    = $tenant->id;
        $employee->account_type = 'employee';
        $employee->active       = (bool)($validated['active'] ?? true);

        $employee->save();

        // Optional: issue a token directly after registration
        $token = $employee->createToken('employee_api')->plainTextToken;

        return response()->json([
            'status'       => 'success',
            'message'      => 'Employee registered successfully',
            'employee'     => [
                'id'          => $employee->id,
                'first_name'  => $employee->first_name,
                'last_name'   => $employee->last_name,
                'email'       => $employee->email,
                'tenant_id'   => $employee->tenant_id,
                'account_type'=> $employee->account_type,
                'active'      => (bool)$employee->active,
            ],
            'access_token' => $token,
            'token_type'   => 'Bearer',
        ], 201);
    }


    /**
     * POST /api/v1/em/auth/login
     * - Employee login (no auth required)
     * Body: { "email": "...", "password": "..." }
     */
    public function login(Request $request)
    {
        $validated = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $employee = User::where('email', $validated['email'])
            ->where('account_type', 'employee')
            ->first();

        if (!$employee || !Hash::check($validated['password'], $employee->password)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Invalid credentials.',
            ], 422);
        }

        if (!$employee->active) {
            return response()->json([
                'status'  => 'error',
                'message' => 'This account is inactive.',
            ], 403);
        }

        $employee->last_login_at = now();
        $employee->save();

        $token = $employee->createToken('employee_api')->plainTextToken;

        return response()->json([
            'status'       => 'success',
            'message'      => 'Logged in successfully',
            'employee'     => [
                'id'          => $employee->id,
                'first_name'  => $employee->first_name,
                'last_name'   => $employee->last_name,
                'email'       => $employee->email,
                'tenant_id'   => $employee->tenant_id,
                'account_type'=> $employee->account_type,
            ],
            'access_token' => $token,
            'token_type'   => 'Bearer',
        ]);
    }


    /**
     * GET /api/v1/em/auth/me
     * - Requires Sanctum token
     */
    public function me(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'status'  => 'success',
            'user'    => [
                'id'           => $user->id,
                'first_name'   => $user->first_name,
                'last_name'    => $user->last_name,
                'email'        => $user->email,
                'tenant_id'    => $user->tenant_id,
                'account_type' => $user->account_type,
                'active'       => (bool)$user->active,
                'last_login_at'=> optional($user->last_login_at)->toISOString(),
            ],
        ]);
    }

    /**
     * POST /api/v1/em/auth/logout
     * - Requires Sanctum token
     */
    public function logout(Request $request)
    {
        $token = $request->user()->currentAccessToken();
        if ($token) {
            $token->delete();
        }

        return response()->json([
            'status'  => 'success',
            'message' => 'Logged out successfully',
        ]);
    }


}
