<?php

namespace App\Services;

use App\Data\CreateTaskData;
use App\Data\UpdateTaskData;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

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
     * Get all tasks with optional filtering and pagination
     *
     * @param array<string, mixed> $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getAllTasks(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Task::with(['project', 'assignedUser', 'creator', 'comments']);

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['priority'])) {
            $query->where('priority', $filters['priority']);
        }

        if (!empty($filters['assigned_to'])) {
            $query->where('assigned_to', $filters['assigned_to']);
        }

        if (!empty($filters['project_id'])) {
            $query->where('project_id', $filters['project_id']);
        }

        if (!empty($filters['created_by'])) {
            $query->where('created_by', $filters['created_by']);
        }

        if (!empty($filters['due_date_from'])) {
            $query->where('due_date', '>=', $filters['due_date_from']);
        }

        if (!empty($filters['due_date_to'])) {
            $query->where('due_date', '<=', $filters['due_date_to']);
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    /**
     * Get tasks assigned to user
     *
     * @param User $user
     * @param array<string, mixed> $filters
     * @return Collection<int, Task>
     */
    public function getUserTasks(User $user, array $filters = []): Collection
    {
        $query = $user->assignedTasks()->with(['project', 'creator', 'comments']);

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['priority'])) {
            $query->where('priority', $filters['priority']);
        }

        if (!empty($filters['project_id'])) {
            $query->where('project_id', $filters['project_id']);
        }

        return $query->get();
    }

    /**
     * Get tasks created by user
     *
     * @param User $user
     * @return Collection<int, Task>
     */
    public function getTasksCreatedByUser(User $user): Collection
    {
        return $user->createdTasks()->with(['project', 'assignedUser', 'comments'])->get();
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
        $query = $project->tasks()->with(['assignedUser', 'creator', 'comments']);

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['assigned_to'])) {
            $query->where('assigned_to', $filters['assigned_to']);
        }

        return $query->get();
    }

    /**
     * Find task by ID
     *
     * @param int $id
     * @return Task|null
     */
    public function findTask(int $id): ?Task
    {
        return Task::with(['project', 'assignedUser', 'creator', 'comments.user'])->find($id);
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
     * Assign task to user
     *
     * @param Task $task
     * @param User $user
     * @return bool
     */
    public function assignTask(Task $task, User $user): bool
    {
        return $task->update(['assigned_to' => $user->id]);
    }

    /**
     * Unassign task
     *
     * @param Task $task
     * @return bool
     */
    public function unassignTask(Task $task): bool
    {
        return $task->update(['assigned_to' => null]);
    }

    /**
     * Change task status
     *
     * @param Task $task
     * @param string $status
     * @return bool
     */
    public function changeStatus(Task $task, string $status): bool
    {
        return $task->update(['status' => $status]);
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

        // Task creator can access
        if ($task->created_by === $user->id) {
            return true;
        }

        // Assigned user can access
        if ($task->assigned_to === $user->id) {
            return true;
        }

        // Project creator can access project tasks
        if ($task->project && $task->project->created_by === $user->id) {
            return true;
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
        return $user->role === 'admin' || 
               $task->created_by === $user->id || 
               ($task->project && $task->project->created_by === $user->id);
    }

    /**
     * Get overdue tasks
     *
     * @return Collection<int, Task>
     */
    public function getOverdueTasks(): Collection
    {
        return Task::where('due_date', '<', now())
            ->where('status', '!=', 'completed')
            ->with(['project', 'assignedUser', 'creator'])
            ->get();
    }

    /**
     * Get task statistics
     *
     * @param array<string, mixed> $filters
     * @return array<string, int>
     */
    public function getTaskStatistics(array $filters = []): array
    {
        $query = Task::query();

        if (!empty($filters['project_id'])) {
            $query->where('project_id', $filters['project_id']);
        }

        if (!empty($filters['assigned_to'])) {
            $query->where('assigned_to', $filters['assigned_to']);
        }

        $tasks = $query->get();

        return [
            'total' => $tasks->count(),
            'completed' => $tasks->where('status', 'completed')->count(),
            'pending' => $tasks->where('status', 'pending')->count(),
            'in_progress' => $tasks->where('status', 'in_progress')->count(),
            'overdue' => $tasks->where('due_date', '<', now())
                              ->where('status', '!=', 'completed')->count(),
            'high_priority' => $tasks->where('priority', 'high')->count(),
            'urgent' => $tasks->where('priority', 'urgent')->count(),
        ];
    }
}