<?php
namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Api\Employee;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class EmployeeAuthController extends Controller
{

    public function register(Request $request)
    {
        $tenant = $request->user();
        if (!$tenant) {
            return response()->json(['status'=>'error','message'=>'Unauthenticated'], 401);
        }

        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|string|email|max:255|unique:api_employees,email',
            'password' => 'required|string|min:6|confirmed',
            'phone'    => 'nullable|string|max:30',

        ]);

        $employee = Employee::create([
            'name'     => $validated['name'],
            'email'    => $validated['email'],
            'phone'    => $validated['phone'],
            'password' => Hash::make($validated['password']),
            'active'      => true,
            'user_id'  => $tenant->id,
            'last_login_at' => now(),
        ]);

        // Issue token
        $token = $employee->createToken('employee_api')->plainTextToken;

        return response()->json([
            'status'      => 'success',
            'message'     => 'Employee registered successfully',
            'employee'    => $employee,
            'access_token'=> $token,
            'token_type'  => 'Bearer',
        ], 201);
    }

    // POST /api/v1/em/auth/login
    public function login(Request $request)
    {
        $data = $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        $employee = Employee::where('email', $data['email'])->first();

        if (! $employee || ! Hash::check($data['password'], $employee->password) || ! $employee->active) {
            return response()->json(['status'=>'error','message'=>'Invalid credentials'], 422);
        }

        $token = $employee->createToken('employee_api')->plainTextToken;

        return response()->json(['status'=>'success','token'=>$token]);
    }

    // GET /api/v1/em/auth/me
    public function me(Request $request)
    {
        return response()->json([
            'status' => 'success',
            'user'   => $request->user(),
        ]);
    }

    // POST /api/v1/em/auth/logout
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'Logged out successfully',
        ]);
    }

}
