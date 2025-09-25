<?php

namespace App\Data;

use Carbon\Carbon;
use Spatie\LaravelData\Attributes\Validation\Exists;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

class CreateTaskData extends Data
{
    public function __construct(
        #[Required, StringType, Max(255)]
        public string $title,

        #[StringType, Max(1000)]
        public ?string $description = null,

        #[Exists('users', 'id')]
        public ?int $assigned_to = null,

        public ?Carbon $due_date = null,

        #[Required, Exists('projects', 'id')]
        public int $project_id,
    ) {
    }

    /**
     * Create a new instance from a Form Request
     *
     * @param array<string, mixed> $data
     * @param int $projectId
     * @return static
     */
    public static function fromRequest(array $data, int $projectId): static
    {
        return new static(
            title: $data['title'],
            description: $data['description'] ?? null,
            assigned_to: $data['assigned_to'] ?? null,
            due_date: isset($data['due_date']) ? Carbon::parse($data['due_date']) : null,
            project_id: $projectId,
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
            'assigned_to' => $this->assigned_to,
            'due_date' => $this->due_date?->format('Y-m-d'),
            'project_id' => $this->project_id,
            'status' => 'pending',
        ];
    }
}