<?php

namespace App\Http\Controllers\Api;

use App\Data\CreateUserData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Authentication Controller
 * 
 * Handles user authentication including registration, login, logout, and user profile
 */
class AuthController extends Controller
{
    /**
     * Register a new user
     *
     * @param RegisterRequest $request
     * @return JsonResponse
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        try {
            $userService = app(UserService::class);
            $userData = CreateUserData::fromRequest($request->validated());
            $user = $userService->createUser($userData);
            $token = $userService->generateAuthToken($user);

            Log::info('User registered successfully', ['user_id' => $user->id, 'email' => $user->email]);

            return $this->successfulResponse(
                message: 'User registered successfully',
                data: [
                    'user' => $user,
                    'token' => $token,
                ],
                statusCode: 201
            );
        } catch (\Exception $e) {
            Log::error('User registration failed', [
                'email' => $request->email,
                'error' => $e->getMessage()
            ]);

            return $this->errorResponse(
                message: 'Registration failed. Please try again.',
                statusCode: 500
            );
        }
    }

    /**
     * Login user
     *
     * @param LoginRequest $request
     * @return JsonResponse
     */
    public function login(LoginRequest $request): JsonResponse
    {
        try {
            $userService = app(UserService::class);
            $credentials = $request->validated();
            $user = $userService->validateCredentials($credentials['email'], $credentials['password']);

            if (!$user) {
                return $this->unauthorizedResponse('The provided credentials are incorrect.');
            }

            $token = $userService->generateAuthToken($user);

            Log::info('User logged in successfully', ['user_id' => $user->id, 'email' => $user->email]);

            return $this->successfulResponse(
                message: 'Login successful',
                data: [
                    'user' => $user,
                    'token' => $token,
                ]
            );
        } catch (\Exception $e) {
            Log::error('User login failed', [
                'email' => $request->email,
                'error' => $e->getMessage()
            ]);

            return $this->errorResponse(
                message: 'Login failed. Please try again.',
                statusCode: 500
            );
        }
    }

    /**
     * Logout user
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function logout(Request $request): JsonResponse
    {
        try {
            $userService = app(UserService::class);
            $user = $request->user();
            $userService->revokeAllTokens($user);

            Log::info('User logged out successfully', ['user_id' => $user->id]);

            return $this->successfulResponse(message: 'Logout successful');
        } catch (\Exception $e) {
            Log::error('User logout failed', [
                'user_id' => $request->user()?->id,
                'error' => $e->getMessage()
            ]);

            return $this->errorResponse(
                message: 'Logout failed. Please try again.',
                statusCode: 500
            );
        }
    }

    /**
     * Get current user profile
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function me(Request $request): JsonResponse
    {
        try {
            $user = $request->user()->load(['projects', 'tasks', 'comments']);

            return $this->successfulResponse(
                message: 'User profile retrieved successfully',
                data: ['user' => $user]
            );
        } catch (\Exception $e) {
            Log::error('Failed to retrieve user profile', [
                'user_id' => $request->user()?->id,
                'error' => $e->getMessage()
            ]);

            return $this->errorResponse(
                message: 'Failed to retrieve user profile.',
                statusCode: 500
            );
        }
    }
}
