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
     * Constructor - Service Injection
     *
     * @param UserService $userService
     */
    public function __construct(private UserService $userService)
    {
        parent::__construct();
    }
    /**
     * Register a new user
     *
     * @param RegisterRequest $request
     * @return JsonResponse
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        try {
            $userData = CreateUserData::fromRequest($request->validated());
            $user = $this->userService->createUser($userData);
            $token = $this->userService->generateAuthToken($user);

            Log::info('User registered successfully', [
                'user_id' => $user->id, 
                'email' => $user->email
            ]);

            return $this->successfulResponse(
                data: [
                    'user' => $user,
                    'token' => $token,
                ],
                message: __('User registered successfully'),
                statusCode: 201
            );
        } catch (\Exception $e) {
            Log::error('User registration failed', [
                'email' => $request->email,
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'trace' => $e->getTrace(),
                'timestamp' => now()
            ]);

            return $this->errorResponse(
                message: __('Registration failed. Please try again.'),
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
            $credentials = $request->validated();
            $user = $this->userService->validateCredentials($credentials['email'], $credentials['password']);

            if (!$user) {
                return $this->unauthorizedResponse(__('The provided credentials are incorrect.'));
            }

            $token = $this->userService->generateAuthToken($user);

            Log::info('User logged in successfully', [
                'user_id' => $user->id, 
                'email' => $user->email
            ]);

            return $this->successfulResponse(
                data: [
                    'user' => $user,
                    'token' => $token,
                ],
                message: __('Login successful')
            );
        } catch (\Exception $e) {
            Log::error('User login failed', [
                'email' => $request->email ?? 'unknown',
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'trace' => $e->getTrace(),
                'timestamp' => now()
            ]);

            return $this->errorResponse(
                message: __('Login failed. Please try again.'),
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
            $user = $request->user();
            $this->userService->revokeAllTokens($user);

            Log::info('User logged out successfully', ['user_id' => $user->id]);

            return $this->successfulResponse(message: __('Logout successful'));
        } catch (\Exception $e) {
            Log::error('User logout failed', [
                'user_id' => $request->user()?->id,
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'trace' => $e->getTrace(),
                'timestamp' => now()
            ]);

            return $this->errorResponse(
                message: __('Logout failed. Please try again.'),
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
                data: ['user' => $user],
                message: __('User profile retrieved successfully')
            );
        } catch (\Exception $e) {
            Log::error('Failed to retrieve user profile', [
                'user_id' => $request->user()?->id,
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'trace' => $e->getTrace(),
                'timestamp' => now()
            ]);

            return $this->errorResponse(
                message: __('Failed to retrieve user profile.'),
                statusCode: 500
            );
        }
    }
}
