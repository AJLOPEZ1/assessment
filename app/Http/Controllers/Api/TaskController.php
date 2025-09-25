<?php

namespace App\Http\Controllers\Api;

use App\Data\CreateTaskData;
use App\Data\UpdateTaskData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Task\TaskStoreRequest;
use App\Http\Requests\Task\TaskUpdateRequest;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Notifications\TaskAssignedNotification;
use App\Services\TaskService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Task Controller
 * 
 * Handles task management including CRUD operations and task-related functionality
 */
class TaskController extends Controller
{
    /**
     * Constructor - Service Injection
     *
     * @param TaskService $taskService
     */
    public function __construct(
        private TaskService $taskService
    ) {
        parent::__construct();
    }

    /**
     * Display tasks for a specific project with search and filtering.
     *
     * @param Request $request
     * @param Project $project
     * @return JsonResponse
     */
    public function projectTasks(Request $request, Project $project): JsonResponse
    {
        try {
            $filters = [
                'status' => $request->get('status'),
                'assigned_to' => $request->get('assigned_to'),
                'search' => $request->get('search'),
            ];

            $tasks = $this->taskService->getProjectTasks($project, $filters);

            return $this->successfulResponse(
                data: ['tasks' => $tasks],
                message: __('Tasks retrieved successfully')
            );
        } catch (\Exception $e) {
            Log::error('Failed to retrieve project tasks', [
                'project_id' => $project->id,
                'user_id' => $request->user()?->id,
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'trace' => $e->getTrace(),
                'timestamp' => now()
            ]);

            return $this->errorResponse(
                message: __('Failed to retrieve tasks.'),
                statusCode: 500
            );
        }
    }

    /**
     * Store a newly created task for a project.
     *
     * @param TaskStoreRequest $request
     * @param Project $project
     * @return JsonResponse
     */
    public function store(TaskStoreRequest $request, Project $project): JsonResponse
    {
        try {
            $taskData = CreateTaskData::fromRequest($request->validated(), $project->id);
            $task = $this->taskService->createTask($taskData);

            // Send notification to assigned user
            if ($task->assigned_to) {
                $assignedUser = User::find($task->assigned_to);
                if ($assignedUser) {
                    $assignedUser->notify(new TaskAssignedNotification($task->load('project')));
                }
            }

            Log::info('Task created successfully', [
                'task_id' => $task->id,
                'project_id' => $project->id,
                'user_id' => $request->user()->id,
                'assigned_to' => $task->assigned_to
            ]);

            return $this->successfulResponse(
                data: ['task' => $task->load(['assignedUser', 'project', 'comments'])],
                message: __('Task created successfully'),
                statusCode: 201
            );
        } catch (\Exception $e) {
            Log::error('Task creation failed', [
                'project_id' => $project->id,
                'user_id' => $request->user()->id,
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'trace' => $e->getTrace(),
                'timestamp' => now()
            ]);

            return $this->errorResponse(
                message: __('Failed to create task. Please try again.'),
                statusCode: 500
            );
        }
    }

    /**
     * Display the specified task.
     *
     * @param Task $task
     * @param Request $request
     * @return JsonResponse
     */
    public function show(Task $task, Request $request): JsonResponse
    {
        try {
            // Check if user can access this task
            if (!$this->taskService->canUserAccessTask($request->user(), $task)) {
                return $this->forbiddenResponse(__('Access denied to this task.'));
            }

            $taskData = $this->taskService->findTask($task->id);

            return $this->successfulResponse(
                data: ['task' => $taskData],
                message: __('Task retrieved successfully')
            );
        } catch (\Exception $e) {
            Log::error('Failed to retrieve task', [
                'task_id' => $task->id,
                'user_id' => $request->user()?->id,
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'trace' => $e->getTrace(),
                'timestamp' => now()
            ]);

            return $this->errorResponse(
                message: __('Failed to retrieve task.'),
                statusCode: 500
            );
        }
    }

    /**
     * Update the specified task.
     *
     * @param TaskUpdateRequest $request
     * @param Task $task
     * @return JsonResponse
     */
    public function update(TaskUpdateRequest $request, Task $task): JsonResponse
    {
        try {
            // Check if user can modify this task
            if (!$this->taskService->canUserModifyTask($request->user(), $task)) {
                return $this->forbiddenResponse(__('You are not authorized to update this task.'));
            }

            $updateData = UpdateTaskData::fromRequest($request->validated());
            $this->taskService->updateTask($task, $updateData);

            Log::info('Task updated successfully', [
                'task_id' => $task->id,
                'user_id' => $request->user()->id,
                'changes' => $request->validated()
            ]);

            return $this->successfulResponse(
                data: ['task' => $task->fresh()->load(['assignedUser', 'project', 'comments'])],
                message: __('Task updated successfully')
            );
        } catch (\Exception $e) {
            Log::error('Task update failed', [
                'task_id' => $task->id,
                'user_id' => $request->user()->id,
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'trace' => $e->getTrace(),
                'timestamp' => now()
            ]);

            return $this->errorResponse(
                message: __('Task update failed. Please try again.'),
                statusCode: 500
            );
        }
    }

    /**
     * Remove the specified task.
     *
     * @param Task $task
     * @param Request $request
     * @return JsonResponse
     */
    public function destroy(Task $task, Request $request): JsonResponse
    {
        try {
            // Check if user can modify this task
            if (!$this->taskService->canUserModifyTask($request->user(), $task)) {
                return $this->forbiddenResponse(__('You are not authorized to delete this task.'));
            }

            $this->taskService->deleteTask($task);

            Log::info('Task deleted successfully', [
                'task_id' => $task->id,
                'user_id' => $request->user()->id,
                'title' => $task->title
            ]);

            return $this->successfulResponse(
                message: __('Task deleted successfully')
            );
        } catch (\Exception $e) {
            Log::error('Task deletion failed', [
                'task_id' => $task->id,
                'user_id' => $request->user()->id,
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'trace' => $e->getTrace(),
                'timestamp' => now()
            ]);

            return $this->errorResponse(
                message: __('Task deletion failed. Please try again.'),
                statusCode: 500
            );
        }
    }
}
