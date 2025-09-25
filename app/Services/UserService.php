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
}