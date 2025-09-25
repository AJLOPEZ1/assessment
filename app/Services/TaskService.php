<?php

namespace App\Services;

use App\Data\CreateTaskData;
use App\Data\UpdateTaskData;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

/**
 * Task service class for handling task-related business logic
 */
class TaskService
{
    /**
     * Create a new task
     *
     * @param CreateTaskData $taskData
     * @return Task
     */
    public function createTask(CreateTaskData $taskData): Task
    {
        return Task::create($taskData->toModelData());
    }

    /**
     * Get project tasks
     *
     * @param Project $project
     * @param array<string, mixed> $filters
     * @return Collection<int, Task>
     */
    public function getProjectTasks(Project $project, array $filters = []): Collection
    {
        $query = $project->tasks()->with(['assignedUser', 'comments']);

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['assigned_to'])) {
            $query->where('assigned_to', $filters['assigned_to']);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    /**
     * Find task by ID
     *
     * @param int $id
     * @return Task|null
     */
    public function findTask(int $id): ?Task
    {
        return Task::with(['project', 'assignedUser', 'comments.user'])->find($id);
    }

    /**
     * Update task
     *
     * @param Task $task
     * @param UpdateTaskData $updateData
     * @return bool
     */
    public function updateTask(Task $task, UpdateTaskData $updateData): bool
    {
        $data = $updateData->toModelData();
        return empty($data) ? true : $task->update($data);
    }

    /**
     * Delete task
     *
     * @param Task $task
     * @return bool
     */
    public function deleteTask(Task $task): bool
    {
        return $task->delete();
    }

    /**
     * Check if user can access task
     *
     * @param User $user
     * @param Task $task
     * @return bool
     */
    public function canUserAccessTask(User $user, Task $task): bool
    {
        // Admin can access all tasks
        if ($user->role === 'admin') {
            return true;
        }

        // Project creator can access all project tasks
        if ($task->project->created_by === $user->id) {
            return true;
        }

        // Task is assigned to user
        if ($task->assigned_to === $user->id) {
            return true;
        }

        // Manager can access tasks in projects they're involved in
        if ($user->role === 'manager') {
            // Check if user has any tasks in this project or created the project
            return $task->project->created_by === $user->id ||
                   $task->project->tasks()->where('assigned_to', $user->id)->exists();
        }

        return false;
    }

    /**
     * Check if user can modify task
     *
     * @param User $user
     * @param Task $task
     * @return bool
     */
    public function canUserModifyTask(User $user, Task $task): bool
    {
        // Admin can modify all tasks
        if ($user->role === 'admin') {
            return true;
        }

        // Manager can modify tasks in projects they're involved in
        if ($user->role === 'manager') {
            return $task->project->created_by === $user->id ||
                   $task->project->tasks()->where('assigned_to', $user->id)->exists();
        }

        // User can modify tasks assigned to them (limited updates)
        return $task->assigned_to === $user->id;
    }
}