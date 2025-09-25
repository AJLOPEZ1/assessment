<?php

namespace App\Data;

use App\Enums\TaskStatusEnum;
use Carbon\Carbon;
use Spatie\LaravelData\Attributes\Validation\Enum;
use Spatie\LaravelData\Attributes\Validation\Exists;
use Spatie\LaravelData\Attributes\Validation\In;
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

        #[Required, StringType, Enum(TaskStatusEnum::class)]
        public string $status,

        #[Required, StringType, In(['low', 'medium', 'high', 'urgent'])]
        public string $priority,

        #[Exists('users', 'id')]
        public ?int $assigned_to = null,

        public ?Carbon $due_date = null,

        #[Required, Exists('projects', 'id')]
        public int $project_id,

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
            status: $data['status'],
            priority: $data['priority'],
            assigned_to: $data['assigned_to'] ?? null,
            due_date: isset($data['due_date']) ? Carbon::parse($data['due_date']) : null,
            project_id: $data['project_id'],
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
            'status' => $this->status,
            'priority' => $this->priority,
            'assigned_to' => $this->assigned_to,
            'due_date' => $this->due_date?->format('Y-m-d H:i:s'),
            'project_id' => $this->project_id,
            'created_by' => $this->created_by,
        ];
    }
}