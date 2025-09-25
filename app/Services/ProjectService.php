<?php

namespace App\Services;

use App\Data\CreateProjectData;
use App\Data\UpdateProjectData;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

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
        return Project::create($projectData->toModelData());
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
        $query = Project::with(['creator', 'tasks']);

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
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
    }

    /**
     * Get projects created by user
     *
     * @param User $user
     * @return Collection<int, Project>
     */
    public function getUserProjects(User $user): Collection
    {
        return $user->projects()->with('tasks')->get();
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
        return empty($data) ? true : $project->update($data);
    }

    /**
     * Delete project
     *
     * @param Project $project
     * @return bool
     */
    public function deleteProject(Project $project): bool
    {
        return $project->delete();
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
     * Search projects by name or description
     *
     * @param string $query
     * @param int $limit
     * @return Collection<int, Project>
     */
    public function searchProjects(string $query, int $limit = 10): Collection
    {
        return Project::where('name', 'like', "%{$query}%")
            ->orWhere('description', 'like', "%{$query}%")
            ->limit($limit)
            ->get();
    }
}