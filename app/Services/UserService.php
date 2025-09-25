<?php

namespace App\Services;

use App\Data\CreateUserData;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Hash;

/**
 * User service class for handling user-related business logic
 */
class UserService
{
    /**
     * Create a new user
     *
     * @param CreateUserData $userData
     * @return User
     */
    public function createUser(CreateUserData $userData): User
    {
        return User::create($userData->toModelData());
    }

    /**
     * Get all users with optional filtering and pagination
     *
     * @param array<string, mixed> $filters
     * @return Collection<int, User>
     */
    public function getAllUsers(array $filters = []): Collection
    {
        $query = User::query();

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if (!empty($filters['role'])) {
            $query->where('role', $filters['role']);
        }

        return $query->get();
    }

    /**
     * Find user by ID
     *
     * @param int $id
     * @return User|null
     */
    public function findUser(int $id): ?User
    {
        return User::find($id);
    }

    /**
     * Find user by email
     *
     * @param string $email
     * @return User|null
     */
    public function findUserByEmail(string $email): ?User
    {
        return User::where('email', $email)->first();
    }

    /**
     * Validate user credentials
     *
     * @param string $email
     * @param string $password
     * @return User|null
     */
    public function validateCredentials(string $email, string $password): ?User
    {
        $user = $this->findUserByEmail($email);

        if ($user && Hash::check($password, $user->password)) {
            return $user;
        }

        return null;
    }

    /**
     * Generate authentication token for user
     *
     * @param User $user
     * @return string
     */
    public function generateAuthToken(User $user): string
    {
        // Delete existing tokens
        $user->tokens()->delete();
        
        // Create new token
        return $user->createToken('api-token')->plainTextToken;
    }

    /**
     * Revoke all user tokens
     *
     * @param User $user
     * @return void
     */
    public function revokeAllTokens(User $user): void
    {
        $user->tokens()->delete();
    }

    /**
     * Update user information
     *
     * @param User $user
     * @param array<string, mixed> $data
     * @return bool
     */
    public function updateUser(User $user, array $data): bool
    {
        return $user->update($data);
    }

    /**
     * Delete user
     *
     * @param User $user
     * @return bool
     */
    public function deleteUser(User $user): bool
    {
        return $user->delete();
    }

    /**
     * Check if user has specific role
     *
     * @param User $user
     * @param string $role
     * @return bool
     */
    public function hasRole(User $user, string $role): bool
    {
        return $user->role === $role;
    }
}