<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Api\ApiController;
use App\Http\Requests\Api\Mobile\UpdateProfileRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;

class AuthController extends ApiController
{
    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors()->toArray());
        }

        $credentials = $validator->validated();

        if (! Auth::attempt(['email' => $credentials['email'], 'password' => $credentials['password']])) {
            return $this->error('Invalid credentials', 422);
        }

        /** @var \App\Models\User $user */
        $user = Auth::user();

        $token = $user->createToken('mobile')->plainTextToken;

        return $this->success([
            'token' => $token,
            'user' => [
                'id' => (int) $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'avatar' => $user->avatar ?? null,
                'account_type' => $user->account_type ?? null,
            ],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $token = $request->user()?->currentAccessToken();
        if ($token) {
            $token->delete();
        }

        return $this->success([
            'message' => 'Logged out',
        ]);
    }

    public function forgotPassword(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors()->toArray());
        }

        $status = Password::sendResetLink(['email' => $validator->validated()['email']]);

        if ($status !== Password::RESET_LINK_SENT) {
            return $this->error('Failed to send reset link', 422);
        }

        return $this->success([
            'message' => 'Reset code sent',
        ]);
    }

    public function profile(Request $request): JsonResponse
    {
        $user = $request->user();

        return $this->success([
            'id' => (int) $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone ?? null,
            'avatar' => $user->avatar ?? null,
            'account_type' => $user->account_type ?? null,
        ]);
    }

    public function updateProfile(UpdateProfileRequest $request): JsonResponse
    {
        $user = $request->user();

        $payload = $request->validated();
        if (! empty($payload)) {
            $user->fill($payload);
            $user->save();
        }

        return $this->success([
            'message' => 'Profile updated',
        ]);
    }
}
