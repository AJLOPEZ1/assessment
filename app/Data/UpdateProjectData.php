<?php

namespace App\Data;

use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Attributes\Validation\Unique;
use Spatie\LaravelData\Data;

class UpdateProjectData extends Data
{
    public function __construct(
        #[StringType, Max(255)]
        public ?string $name = null,

        #[StringType, Max(1000)]
        public ?string $description = null,
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
            name: $data['name'] ?? null,
            description: $data['description'] ?? null
        );
    }

    /**
     * Transform the DTO to array for model update
     *
     * @return array<string, mixed>
     */
    public function toModelData(): array
    {
        return array_filter([
            'name' => $this->name,
            'description' => $this->description,
        ], fn($value) => $value !== null);
    }
}