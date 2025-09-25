<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\Task;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    /**
     * Display comments for a specific task.
     */
    public function index(Task $task)
    {
        $comments = $task->comments()->with('user')->get();

        return response()->json([
            'comments' => $comments,
        ]);
    }

    /**
     * Store a new comment for a task.
     */
    public function store(Request $request, Task $task)
    {
        $request->validate([
            'body' => 'required|string',
        ]);

        $comment = Comment::create([
            'body' => $request->body,
            'task_id' => $task->id,
            'user_id' => $request->user()->id,
        ]);

        return response()->json([
            'message' => 'Comment added successfully',
            'comment' => $comment->load('user'),
        ], 201);
    }
}
