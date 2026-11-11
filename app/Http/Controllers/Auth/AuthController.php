<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function login(LoginRequest $request, AuthService $auth): JsonResponse
    {
        $data = $request->validated();
        $credentials = $auth->authenticate($data['email'], $data['password']);

        if ($credentials === null) {
            return response()->json(['success' => false, 'message' => 'Invalid email or password.'], 401);
        }

        return response()->json([
            'success' => true, 'message' => 'Login successful.', 'data' => $credentials]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json(['success' => true, 'message' => 'Logged out successfully.']);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json(['success' => true, 'data' => $request->user()]);
    }
}
