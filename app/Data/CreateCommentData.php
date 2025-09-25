<?php

namespace App\Data;

use Spatie\LaravelData\Attributes\Validation\Exists;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

class CreateCommentData extends Data
{
    public function __construct(
        #[Required, StringType, Max(1000)]
        public string $content,

        #[Required, Exists('tasks', 'id')]
        public int $task_id,

        #[Required]
        public int $user_id,
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
            content: $data['content'],
            task_id: $data['task_id'],
            user_id: $userId
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
            'content' => $this->content,
            'task_id' => $this->task_id,
            'user_id' => $this->user_id,
        ];
    }
}