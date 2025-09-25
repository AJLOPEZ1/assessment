<?php

namespace App\Data;

use Carbon\Carbon;
use Spatie\LaravelData\Attributes\Validation\Exists;
use Spatie\LaravelData\Attributes\Validation\In;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

class UpdateTaskData extends Data
{
    public function __construct(
        #[StringType, Max(255)]
        public ?string $title = null,

        #[StringType, Max(1000)]
        public ?string $description = null,

        #[StringType, In(['pending', 'in-progress', 'done'])]
        public ?string $status = null,

        #[Exists('users', 'id')]
        public ?int $assigned_to = null,

        public ?Carbon $due_date = null,
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
            title: $data['title'] ?? null,
            description: $data['description'] ?? null,
            status: $data['status'] ?? null,
            assigned_to: $data['assigned_to'] ?? null,
            due_date: isset($data['due_date']) ? Carbon::parse($data['due_date']) : null,
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
            'title' => $this->title,
            'description' => $this->description,
            'status' => $this->status,
            'assigned_to' => $this->assigned_to,
            'due_date' => $this->due_date?->format('Y-m-d'),
        ], fn($value) => $value !== null);
    }
}