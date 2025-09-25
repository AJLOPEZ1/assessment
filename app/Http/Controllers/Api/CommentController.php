<?php

namespace App\Http\Controllers\Api;

use App\Data\CreateCommentData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Comment\CommentStoreRequest;
use App\Models\Task;
use App\Services\CommentService;
use App\Services\TaskService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Comment Controller
 * 
 * Handles comment management including CRUD operations and comment-related functionality
 */
class CommentController extends Controller
{
    /**
     * Constructor - Service Injection
     *
     * @param CommentService $commentService
     * @param TaskService $taskService
     */
    public function __construct(
        private CommentService $commentService,
        private TaskService $taskService
    ) {
        parent::__construct();
    }

    /**
     * Display comments for a specific task with search and pagination.
     *
     * @param Request $request
     * @param Task $task
     * @return JsonResponse
     */
    public function index(Request $request, Task $task): JsonResponse
    {
        try {
            // Check if user can access this task
            if (!$this->taskService->canUserAccessTask($request->user(), $task)) {
                return $this->forbiddenResponse(__('Access denied to this task.'));
            }

            $comments = $this->commentService->getTaskComments($task);

            return $this->successfulResponse(
                data: ['comments' => $comments],
                message: __('Comments retrieved successfully')
            );
        } catch (\Exception $e) {
            Log::error('Failed to retrieve task comments', [
                'task_id' => $task->id,
                'user_id' => $request->user()?->id,
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'trace' => $e->getTrace(),
                'timestamp' => now()
            ]);

            return $this->errorResponse(
                message: __('Failed to retrieve comments.'),
                statusCode: 500
            );
        }
    }

    /**
     * Store a new comment for a task.
     *
     * @param CommentStoreRequest $request
     * @param Task $task
     * @return JsonResponse
     */
    public function store(CommentStoreRequest $request, Task $task): JsonResponse
    {
        try {
            // Check if user can access this task
            if (!$this->taskService->canUserAccessTask($request->user(), $task)) {
                return $this->forbiddenResponse(__('Access denied to this task.'));
            }

            $commentData = CreateCommentData::fromRequest(
                $request->validated(),
                $request->user()->id
            );
            
            // Override task_id from route parameter
            $commentData->task_id = $task->id;
            
            $comment = $this->commentService->createComment($commentData);

            Log::info('Comment created successfully', [
                'comment_id' => $comment->id,
                'task_id' => $task->id,
                'user_id' => $request->user()->id
            ]);

            return $this->successfulResponse(
                data: ['comment' => $comment->load('user')],
                message: __('Comment added successfully'),
                statusCode: 201
            );
        } catch (\Exception $e) {
            Log::error('Comment creation failed', [
                'task_id' => $task->id,
                'user_id' => $request->user()->id,
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'trace' => $e->getTrace(),
                'timestamp' => now()
            ]);

            return $this->errorResponse(
                message: __('Failed to create comment. Please try again.'),
                statusCode: 500
            );
        }
    }
}
