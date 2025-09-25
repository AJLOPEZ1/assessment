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
     * Get all comments with optional filtering
     *
     * @param array<string, mixed> $filters
     * @return Collection<int, Comment>
     */
    public function getAllComments(array $filters = []): Collection
    {
        $query = Comment::with(['user', 'task']);

        if (!empty($filters['task_id'])) {
            $query->where('task_id', $filters['task_id']);
        }

        if (!empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where('body', 'like', "%{$search}%");
        }

        return $query->orderBy('created_at', 'desc')->get();
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

    /**
     * Get comments by user
     *
     * @param User $user
     * @return Collection<int, Comment>
     */
    public function getUserComments(User $user): Collection
    {
        return $user->comments()
            ->with(['task.project'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Find comment by ID
     *
     * @param int $id
     * @return Comment|null
     */
    public function findComment(int $id): ?Comment
    {
        return Comment::with(['user', 'task.project'])->find($id);
    }

    /**
     * Update comment
     *
     * @param Comment $comment
     * @param string $body
     * @return bool
     */
    public function updateComment(Comment $comment, string $body): bool
    {
        return $comment->update(['body' => $body]);
    }

    /**
     * Delete comment
     *
     * @param Comment $comment
     * @return bool
     */
    public function deleteComment(Comment $comment): bool
    {
        return $comment->delete();
    }

    /**
     * Check if user can access comment
     *
     * @param User $user
     * @param Comment $comment
     * @return bool
     */
    public function canUserAccessComment(User $user, Comment $comment): bool
    {
        // Admin can access all comments
        if ($user->role === 'admin') {
            return true;
        }

        // Comment author can access
        if ($comment->user_id === $user->id) {
            return true;
        }

        // Task creator can access task comments
        if ($comment->task && $comment->task->created_by === $user->id) {
            return true;
        }

        // Task assigned user can access task comments
        if ($comment->task && $comment->task->assigned_to === $user->id) {
            return true;
        }

        // Project creator can access project task comments
        if ($comment->task && $comment->task->project && 
            $comment->task->project->created_by === $user->id) {
            return true;
        }

        return false;
    }

    /**
     * Check if user can modify comment
     *
     * @param User $user
     * @param Comment $comment
     * @return bool
     */
    public function canUserModifyComment(User $user, Comment $comment): bool
    {
        return $user->role === 'admin' || $comment->user_id === $user->id;
    }

    /**
     * Get recent comments for user's tasks/projects
     *
     * @param User $user
     * @param int $limit
     * @return Collection<int, Comment>
     */
    public function getRecentCommentsForUser(User $user, int $limit = 10): Collection
    {
        $taskIds = collect();

        // Get tasks created by user
        $createdTaskIds = $user->createdTasks()->pluck('id');
        $taskIds = $taskIds->merge($createdTaskIds);

        // Get tasks assigned to user
        $assignedTaskIds = $user->assignedTasks()->pluck('id');
        $taskIds = $taskIds->merge($assignedTaskIds);

        // Get tasks from projects created by user
        $projectTaskIds = Task::whereIn('project_id', 
            $user->projects()->pluck('id')
        )->pluck('id');
        $taskIds = $taskIds->merge($projectTaskIds);

        return Comment::whereIn('task_id', $taskIds->unique())
            ->with(['user', 'task.project'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get comment statistics
     *
     * @param array<string, mixed> $filters
     * @return array<string, int>
     */
    public function getCommentStatistics(array $filters = []): array
    {
        $query = Comment::query();

        if (!empty($filters['task_id'])) {
            $query->where('task_id', $filters['task_id']);
        }

        if (!empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        $comments = $query->get();

        return [
            'total_comments' => $comments->count(),
            'comments_today' => $comments->where('created_at', '>=', now()->startOfDay())->count(),
            'comments_this_week' => $comments->where('created_at', '>=', now()->startOfWeek())->count(),
            'comments_this_month' => $comments->where('created_at', '>=', now()->startOfMonth())->count(),
        ];
    }

    /**
     * Search comments by body content
     *
     * @param string $query
     * @param int $limit
     * @return Collection<int, Comment>
     */
    public function searchComments(string $query, int $limit = 10): Collection
    {
        return Comment::where('body', 'like', "%{$query}%")
            ->with(['user', 'task.project'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }
}