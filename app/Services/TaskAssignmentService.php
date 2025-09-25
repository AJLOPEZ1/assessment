<?php

namespace App\Services;

use App\Models\Task;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class TaskAssignmentService
{
    /**
     * Assign a task to a user with validation
     */
    public function assignTask(Task $task, int $userId): Task
    {
        $user = User::find($userId);
        
        if (!$user) {
            throw ValidationException::withMessages([
                'assigned_to' => ['User not found.']
            ]);
        }

        // Validate user role
        if ($user->role === 'admin') {
            throw ValidationException::withMessages([
                'assigned_to' => ['Admin users cannot be assigned to tasks.']
            ]);
        }

        // Check if user has too many active tasks
        $activeTasks = Task::where('assigned_to', $userId)
            ->whereIn('status', ['pending', 'in-progress'])
            ->count();

        if ($activeTasks >= 10) {
            throw ValidationException::withMessages([
                'assigned_to' => ['User already has too many active tasks.']
            ]);
        }

        $task->assigned_to = $userId;
        $task->save();

        return $task->load(['assignedUser', 'project']);
    }

    /**
     * Validate task assignment data
     */
    public function validateAssignment(array $data): bool
    {
        if (!isset($data['task_id']) || !isset($data['user_id'])) {
            return false;
        }

        $task = Task::find($data['task_id']);
        $user = User::find($data['user_id']);

        return $task && $user && in_array($user->role, ['manager', 'user']);
    }

    /**
     * Get user's task statistics
     */
    public function getUserTaskStats(int $userId): array
    {
        $user = User::find($userId);
        
        if (!$user) {
            return [];
        }

        return [
            'total_tasks' => $user->tasks()->count(),
            'pending_tasks' => $user->tasks()->where('status', 'pending')->count(),
            'in_progress_tasks' => $user->tasks()->where('status', 'in-progress')->count(),
            'completed_tasks' => $user->tasks()->where('status', 'done')->count(),
        ];
    }
}