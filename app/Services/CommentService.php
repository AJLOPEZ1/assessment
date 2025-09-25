<?php

namespace App\Services;

use App\Data\CreateCommentData;
use App\Models\Comment;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

/**
 * Comment service class for handling comment-related business logic
 */
class CommentService
{
    /**
     * Create a new comment
     *
     * @param CreateCommentData $commentData
     * @return Comment
     */
    public function createComment(CreateCommentData $commentData): Comment
    {
        return Comment::create($commentData->toModelData());
    }

    /**
     * Get comments for a specific task
     *
     * @param Task $task
     * @return Collection<int, Comment>
     */
    public function getTaskComments(Task $task): Collection
    {
        return $task->comments()
            ->with(['user'])
            ->orderBy('created_at', 'asc')
            ->get();
    }
}