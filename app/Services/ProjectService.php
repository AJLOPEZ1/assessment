<?php

namespace App\Services;

use App\Data\CreateProjectData;
use App\Data\UpdateProjectData;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;

/**
 * Project service class for handling project-related business logic
 */
class ProjectService
{
    /**
     * Create a new project
     *
     * @param CreateProjectData $projectData
     * @return Project
     */
    public function createProject(CreateProjectData $projectData): Project
    {
        $project = Project::create($projectData->toModelData());
        
        // Clear project cache when new project is created
        $this->clearProjectsCache();
        
        return $project;
    }

    /**
     * Get all projects with optional filtering and pagination
     *
     * @param array<string, mixed> $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getAllProjects(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        // Create cache key based on filters and pagination
        $cacheKey = 'projects_list_' . md5(serialize($filters) . '_' . $perPage);
        
        return Cache::remember($cacheKey, 3600, function () use ($filters, $perPage) {
            $query = Project::with(['creator', 'tasks']);

            if (!empty($filters['search'])) {
                $search = $filters['search'];
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
                });
            }

            if (!empty($filters['created_by'])) {
                $query->where('created_by', $filters['created_by']);
            }

            if (!empty($filters['status'])) {
                // Assuming we might add status to projects in the future
                $query->where('status', $filters['status']);
            }

            return $query->orderBy('created_at', 'desc')->paginate($perPage);
        });
    }

    /**
     * Find project by ID
     *
     * @param int $id
     * @return Project|null
     */
    public function findProject(int $id): ?Project
    {
        return Project::with(['creator', 'tasks.assignedUser', 'tasks.comments'])->find($id);
    }

    /**
     * Update project
     *
     * @param Project $project
     * @param UpdateProjectData $updateData
     * @return bool
     */
    public function updateProject(Project $project, UpdateProjectData $updateData): bool
    {
        $data = $updateData->toModelData();
        $result = empty($data) ? true : $project->update($data);
        
        if ($result) {
            // Clear project cache when project is updated
            $this->clearProjectsCache();
        }
        
        return $result;
    }

    /**
     * Delete project
     *
     * @param Project $project
     * @return bool
     */
    public function deleteProject(Project $project): bool
    {
        $result = $project->delete();
        
        if ($result) {
            // Clear project cache when project is deleted
            $this->clearProjectsCache();
        }
        
        return $result;
    }

    /**
     * Check if user can access project
     *
     * @param User $user
     * @param Project $project
     * @return bool
     */
    public function canUserAccessProject(User $user, Project $project): bool
    {
        // Admin can access all projects
        if ($user->role === 'admin') {
            return true;
        }

        // Project creator can access their project
        if ($project->created_by === $user->id) {
            return true;
        }

        // User assigned to any task in the project can access it
        return $project->tasks()
            ->where('assigned_to', $user->id)
            ->exists();
    }

    /**
     * Check if user can modify project
     *
     * @param User $user
     * @param Project $project
     * @return bool
     */
    public function canUserModifyProject(User $user, Project $project): bool
    {
        return $user->role === 'admin' || $project->created_by === $user->id;
    }

    /**
     * Get project statistics
     *
     * @param Project $project
     * @return array<string, int>
     */
    public function getProjectStatistics(Project $project): array
    {
        $tasks = $project->tasks;
        
        return [
            'total_tasks' => $tasks->count(),
            'completed_tasks' => $tasks->where('status', 'completed')->count(),
            'pending_tasks' => $tasks->where('status', 'pending')->count(),
            'in_progress_tasks' => $tasks->where('status', 'in_progress')->count(),
            'high_priority_tasks' => $tasks->where('priority', 'high')->count(),
            'urgent_tasks' => $tasks->where('priority', 'urgent')->count(),
        ];
    }

    /**
     * Clear all projects related cache
     *
     * @return void
     */
    private function clearProjectsCache(): void
    {
        // Since we can't easily iterate cache keys in file driver,
        // we'll use cache tags when available or clear specific known keys
        // For now, we'll clear the cache store completely for simplicity
        // In production, consider using Redis with cache tags
        Cache::flush();
    }
}