<?php

namespace App\Data;

use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Unique;
use Spatie\LaravelData\Data;

class CreateProjectData extends Data
{
    public function __construct(
        #[Required, StringType, Max(255)]
        public string $title,

        #[StringType, Max(1000)]
        public ?string $description = null,

        #[Required]
        public int $created_by,
    ) {
    }

    /**
     * Create a new instance from a Form Request
     *
     * @param array<string, mixed> $data
     * @return static
     */
    public static function fromRequest(array $data, int $userId): static
    {
        return new static(
            title: $data['title'],
            description: $data['description'] ?? null,
            created_by: $userId
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
            'title' => $this->title,
            'description' => $this->description,
            'created_by' => $this->created_by,
        ];
    }
}