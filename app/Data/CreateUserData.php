<?php

namespace App\Data;

use App\Enums\UserRoleEnum;
use Spatie\LaravelData\Attributes\Validation\Email;
use Spatie\LaravelData\Attributes\Validation\Enum;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Attributes\Validation\Unique;
use Spatie\LaravelData\Data;

class CreateUserData extends Data
{
    public function __construct(
        #[Required, StringType]
        public string $name,

        #[Required, Email, Unique('users', 'email')]
        public string $email,

        #[Required, StringType, Min(8)]
        public string $password,

        #[Required, StringType, Enum(UserRoleEnum::class)]
        public string $role,
    ) {
    }

    /**
     * Create a new instance from a Form Request
     *
     * @param array<string, mixed> $data
     * @return static
     */
    public static function fromRequest(array $data): static
    {
        return new static(
            name: $data['name'],
            email: $data['email'],
            password: $data['password'],
            role: $data['role']
        );
    }

    /**
     * Transform the DTO to array for model creation
     *
     * @return array<string, mixed>
     */
    public function toModelData(): array
    {
        return [
            'name' => $this->name,
            'email' => $this->email,
            'password' => bcrypt($this->password),
            'role' => $this->role,
        ];
    }
}