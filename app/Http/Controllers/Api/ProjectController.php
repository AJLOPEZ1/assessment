<?php

namespace App\Http\Controllers\Api;

use App\Data\CreateProjectData;
use App\Data\UpdateProjectData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Project\ProjectStoreRequest;
use App\Http\Requests\Project\ProjectUpdateRequest;
use App\Models\Project;
use App\Services\ProjectService;
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
     * Constructor - Service Injection
     *
     * @param ProjectService $projectService
     */
    public function __construct(private ProjectService $projectService)
    {
        parent::__construct();
    }

    /**
     * Display a listing of projects with search and pagination
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $filters = [
                'search' => $request->get('search'),
                'status' => $request->get('status'),
                'created_by' => $request->get('created_by'),
            ];

            $perPage = $request->get('per_page', 15);
            $projects = $this->projectService->getAllProjects($filters, $perPage);

            return $this->paginatedResponse(
                paginator: $projects,
                message: __('Projects retrieved successfully')
            );
        } catch (\Exception $e) {
            Log::error('Failed to retrieve projects', [
                'user_id' => $request->user()?->id,
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'trace' => $e->getTrace(),
                'timestamp' => now()
            ]);

            return $this->errorResponse(
                message: __('Failed to retrieve projects.'),
                statusCode: 500
            );
        }
    }

    /**
     * Store a newly created project
     *
     * @param ProjectStoreRequest $request
     * @return JsonResponse
     */
    public function store(ProjectStoreRequest $request): JsonResponse
    {
        try {
            $projectData = CreateProjectData::fromRequest($request->validated(), $request->user()->id);
            $project = $this->projectService->createProject($projectData);

            Log::info('Project created successfully', [
                'project_id' => $project->id,
                'user_id' => $request->user()->id,
                'name' => $project->name
            ]);

            return $this->successfulResponse(
                data: ['project' => $project->load('creator')],
                message: __('Project created successfully'),
                statusCode: 201
            );
        } catch (\Exception $e) {
            Log::error('Project creation failed', [
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'trace' => $e->getTrace(),
                'data' => $request->validated(),
                'timestamp' => now()
            ]);
            
            return $this->errorResponse(
                message: __('Project creation failed. Please try again.'),
                statusCode: 500
            );
        }
    }

    /**
     * Display the specified project with statistics
     *
     * @param Project $project
     * @param Request $request
     * @return JsonResponse
     */
    public function show(Project $project, Request $request): JsonResponse
    {
        try {
            // Check if user can access this project
            if (!$this->projectService->canUserAccessProject($request->user(), $project)) {
                return $this->forbiddenResponse(__('Access denied to this project.'));
            }

            $projectData = $this->projectService->findProject($project->id);
            $statistics = $this->projectService->getProjectStatistics($project);

            return $this->successfulResponse(
                data: [
                    'project' => $projectData,
                    'statistics' => $statistics
                ],
                message: __('Project retrieved successfully')
            );
        } catch (\Exception $e) {
            Log::error('Failed to retrieve project', [
                'project_id' => $project->id,
                'user_id' => $request->user()?->id,
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'trace' => $e->getTrace(),
                'timestamp' => now()
            ]);

            return $this->errorResponse(
                message: __('Failed to retrieve project.'),
                statusCode: 500
            );
        }
    }

    /**
     * Update the specified project
     *
     * @param ProjectUpdateRequest $request
     * @param Project $project
     * @return JsonResponse
     */
    public function update(ProjectUpdateRequest $request, Project $project): JsonResponse
    {
        try {
            // Check if user can modify this project
            if (!$this->projectService->canUserModifyProject($request->user(), $project)) {
                return $this->forbiddenResponse(__('You are not authorized to update this project.'));
            }

            $updateData = UpdateProjectData::fromRequest($request->validated());
            $this->projectService->updateProject($project, $updateData);

            Log::info('Project updated successfully', [
                'project_id' => $project->id,
                'user_id' => $request->user()->id,
                'changes' => $request->validated()
            ]);

            return $this->successfulResponse(
                data: ['project' => $project->fresh()->load('creator')],
                message: __('Project updated successfully')
            );
        } catch (\Exception $e) {
            Log::error('Project update failed', [
                'project_id' => $project->id,
                'user_id' => $request->user()->id,
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'trace' => $e->getTrace(),
                'timestamp' => now()
            ]);

            return $this->errorResponse(
                message: __('Project update failed. Please try again.'),
                statusCode: 500
            );
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
            // Check if user can modify this project
            if (!$this->projectService->canUserModifyProject($request->user(), $project)) {
                return $this->forbiddenResponse(__('You are not authorized to delete this project.'));
            }

            $this->projectService->deleteProject($project);

            Log::info('Project deleted successfully', [
                'project_id' => $project->id,
                'user_id' => $request->user()->id,
                'name' => $project->name
            ]);

            return $this->successfulResponse(
                message: __('Project deleted successfully')
            );
        } catch (\Exception $e) {
            Log::error('Project deletion failed', [
                'project_id' => $project->id,
                'user_id' => $request->user()->id,
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'trace' => $e->getTrace(),
                'timestamp' => now()
            ]);

            return $this->errorResponse(
                message: __('Project deletion failed. Please try again.'),
                statusCode: 500
            );
        }
    }
}
