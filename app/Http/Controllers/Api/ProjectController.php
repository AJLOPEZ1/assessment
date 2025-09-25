<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Project Controller
 * 
 * Handles project management including CRUD operations and project-related functionality
 */
class ProjectController extends Controller
{

    /**
     * Display a listing of projects
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $projects = Project::with(['creator'])->paginate(15);

            return response()->json([
                'success' => true,
                'message' => 'Projects retrieved successfully',
                'data' => $projects->items(),
                'pagination' => [
                    'current_page' => $projects->currentPage(),
                    'per_page' => $projects->perPage(),
                    'total' => $projects->total(),
                    'total_pages' => $projects->lastPage(),
                ],
                'timestamp' => now()->format('Y-m-d H:i:s'),
                'execution_time' => '0 ms'
            ], 200);
        } catch (\Exception $e) {
            Log::error('Failed to retrieve projects', [
                'user_id' => $request->user()?->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve projects.',
                'timestamp' => now()->format('Y-m-d H:i:s'),
                'execution_time' => '0 ms'
            ], 500);
        }
    }

    /**
     * Store a newly created project
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        try {
            // Basic validation first
            $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string|max:1000',
            ]);

            // Create project directly
            $project = Project::create([
                'name' => $request->name,
                'description' => $request->description,
                'created_by' => $request->user()->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Project created successfully',
                'data' => [
                    'project' => $project
                ],
                'timestamp' => now()->format('Y-m-d H:i:s'),
                'execution_time' => '0 ms'
            ], 201);
        } catch (\Exception $e) {
            Log::error('Project creation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'data' => $request->all() ?? []
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Project creation failed: ' . $e->getMessage(),
                'timestamp' => now()->format('Y-m-d H:i:s'),
                'execution_time' => '0 ms'
            ], 500);
        }
    }

    /**
     * Display the specified project
     *
     * @param Project $project
     * @param Request $request
     * @return JsonResponse
     */
    public function show(Project $project, Request $request): JsonResponse
    {
        try {
            // Load the project with its creator
            $project->load(['creator']);
            
            // Simple statistics
            $statistics = [
                'total_tasks' => 0,
                'completed_tasks' => 0,
                'pending_tasks' => 0,
            ];

            return response()->json([
                'success' => true,
                'message' => 'Project retrieved successfully',
                'data' => [
                    'project' => $project,
                    'statistics' => $statistics
                ],
                'timestamp' => now()->format('Y-m-d H:i:s'),
                'execution_time' => '0 ms'
            ], 200);
        } catch (\Exception $e) {
            Log::error('Failed to retrieve project', [
                'project_id' => $project->id,
                'user_id' => $request->user()?->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve project.',
                'timestamp' => now()->format('Y-m-d H:i:s'),
                'execution_time' => '0 ms'
            ], 500);
        }
    }

    /**
     * Update the specified project
     *
     * @param Request $request
     * @param Project $project
     * @return JsonResponse
     */
    public function update(Request $request, Project $project): JsonResponse
    {
        try {
            $request->validate([
                'name' => 'sometimes|required|string|max:255',
                'description' => 'sometimes|nullable|string|max:1000',
            ]);

            $project->update($request->only(['name', 'description']));

            return response()->json([
                'success' => true,
                'message' => 'Project updated successfully',
                'data' => [
                    'project' => $project->fresh()->load('creator')
                ],
                'timestamp' => now()->format('Y-m-d H:i:s'),
                'execution_time' => '0 ms'
            ], 200);
        } catch (\Exception $e) {
            Log::error('Project update failed', [
                'project_id' => $project->id,
                'user_id' => $request->user()->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Project update failed. Please try again.',
                'timestamp' => now()->format('Y-m-d H:i:s'),
                'execution_time' => '0 ms'
            ], 500);
        }
    }

    /**
     * Remove the specified project
     *
     * @param Project $project
     * @param Request $request
     * @return JsonResponse
     */
    public function destroy(Project $project, Request $request): JsonResponse
    {
        try {
            $project->delete();

            return response()->json([
                'success' => true,
                'message' => 'Project deleted successfully',
                'timestamp' => now()->format('Y-m-d H:i:s'),
                'execution_time' => '0 ms'
            ], 200);
        } catch (\Exception $e) {
            Log::error('Project deletion failed', [
                'project_id' => $project->id,
                'user_id' => $request->user()->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Project deletion failed. Please try again.',
                'timestamp' => now()->format('Y-m-d H:i:s'),
                'execution_time' => '0 ms'
            ], 500);
        }
    }
}
