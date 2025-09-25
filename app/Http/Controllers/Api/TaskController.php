<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Notifications\TaskAssignedNotification;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    /**
     * Display tasks for a specific project.
     */
    public function projectTasks(Project $project)
    {
        $tasks = $project->tasks()->with(['assignedUser', 'comments'])->get();

        return response()->json([
            'tasks' => $tasks,
        ]);
    }

    /**
     * Store a newly created task for a project.
     */
    public function store(Request $request, Project $project)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'due_date' => 'required|date|after:today',
            'assigned_to' => 'required|exists:users,id',
        ]);

        $task = Task::create([
            'title' => $request->title,
            'description' => $request->description,
            'due_date' => $request->due_date,
            'assigned_to' => $request->assigned_to,
            'project_id' => $project->id,
            'status' => 'pending',
        ]);

        // Send notification to assigned user
        $assignedUser = User::find($request->assigned_to);
        if ($assignedUser) {
            $assignedUser->notify(new TaskAssignedNotification($task->load('project')));
        }

        return response()->json([
            'message' => 'Task created successfully',
            'task' => $task->load(['assignedUser', 'project', 'comments']),
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Task $task)
    {
        return response()->json([
            'task' => $task->load(['assignedUser', 'project', 'comments.user']),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Task $task)
    {
        // Check if user is manager or assigned user
        if ($request->user()->role !== 'manager' && $request->user()->id !== $task->assigned_to) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|required|string',
            'status' => 'sometimes|required|in:pending,in-progress,done',
            'due_date' => 'sometimes|required|date',
            'assigned_to' => 'sometimes|required|exists:users,id',
        ]);

        $task->update($request->only(['title', 'description', 'status', 'due_date', 'assigned_to']));

        return response()->json([
            'message' => 'Task updated successfully',
            'task' => $task->load(['assignedUser', 'project', 'comments']),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Task $task)
    {
        $task->delete();

        return response()->json([
            'message' => 'Task deleted successfully',
        ]);
    }
}
