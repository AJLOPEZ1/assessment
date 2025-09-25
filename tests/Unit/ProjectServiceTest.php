<?php

namespace Tests\Unit;

use App\Data\CreateProjectData;
use App\Data\UpdateProjectData;
use App\Models\Project;
use App\Models\User;
use App\Services\ProjectService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class ProjectServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ProjectService $projectService;
    protected User $admin;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->projectService = new ProjectService();
        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->user = User::factory()->create(['role' => 'user']);
    }

    public function test_create_project_creates_project_and_clears_cache()
    {
        // Set up cache with some data
        Cache::put('projects_list_test', 'cached_data');

        $projectData = CreateProjectData::from([
            'title' => 'Test Project',
            'description' => 'Test project description',
            'start_date' => '2025-01-01',
            'end_date' => '2025-12-31',
            'created_by' => $this->admin->id,
        ]);

        $project = $this->projectService->createProject($projectData);

        $this->assertInstanceOf(Project::class, $project);
        $this->assertEquals('Test Project', $project->title);
        $this->assertEquals($this->admin->id, $project->created_by);

        // Cache should be cleared
        $this->assertNull(Cache::get('projects_list_test'));
    }

    public function test_get_all_projects_uses_cache()
    {
        // Create some projects
        Project::factory(3)->create(['created_by' => $this->admin->id]);

        // First call should cache the results
        $firstResult = $this->projectService->getAllProjects();
        
        // Second call should return cached results
        $secondResult = $this->projectService->getAllProjects();

        $this->assertEquals($firstResult->total(), $secondResult->total());
    }

    public function test_get_all_projects_with_search_filter()
    {
        Project::factory()->create([
            'title' => 'Searchable Project',
            'description' => 'This project should be found',
            'created_by' => $this->admin->id
        ]);

        Project::factory()->create([
            'title' => 'Other Project',
            'description' => 'This should not be found',
            'created_by' => $this->admin->id
        ]);

        $results = $this->projectService->getAllProjects(['search' => 'Searchable']);

        $this->assertEquals(1, $results->total());
        $this->assertEquals('Searchable Project', $results->first()->title);
    }

    public function test_get_user_projects()
    {
        $userProjects = Project::factory(3)->create([
            'created_by' => $this->user->id
        ]);

        Project::factory(2)->create([
            'created_by' => $this->admin->id
        ]);

        $results = $this->projectService->getUserProjects($this->user);

        $this->assertEquals(3, $results->count());
        $this->assertEquals($this->user->id, $results->first()->created_by);
    }

    public function test_find_project_returns_project_with_relations()
    {
        $project = Project::factory()->create(['created_by' => $this->admin->id]);

        $result = $this->projectService->findProject($project->id);

        $this->assertInstanceOf(Project::class, $result);
        $this->assertEquals($project->id, $result->id);
        $this->assertTrue($result->relationLoaded('creator'));
        $this->assertTrue($result->relationLoaded('tasks'));
    }

    public function test_find_project_returns_null_for_nonexistent_project()
    {
        $result = $this->projectService->findProject(999);

        $this->assertNull($result);
    }

    public function test_update_project_updates_and_clears_cache()
    {
        // Set up cache
        Cache::put('projects_list_test', 'cached_data');

        $project = Project::factory()->create(['created_by' => $this->admin->id]);

        $updateData = UpdateProjectData::from([
            'title' => 'Updated Project Title',
            'description' => 'Updated description'
        ]);

        $result = $this->projectService->updateProject($project, $updateData);

        $this->assertTrue($result);
        $this->assertEquals('Updated Project Title', $project->fresh()->title);
        
        // Cache should be cleared
        $this->assertNull(Cache::get('projects_list_test'));
    }

    public function test_delete_project_deletes_and_clears_cache()
    {
        // Set up cache
        Cache::put('projects_list_test', 'cached_data');

        $project = Project::factory()->create(['created_by' => $this->admin->id]);
        $projectId = $project->id;

        $result = $this->projectService->deleteProject($project);

        $this->assertTrue($result);
        $this->assertDatabaseMissing('projects', ['id' => $projectId]);
        
        // Cache should be cleared
        $this->assertNull(Cache::get('projects_list_test'));
    }

    public function test_can_user_access_project_admin()
    {
        $project = Project::factory()->create(['created_by' => $this->user->id]);

        $result = $this->projectService->canUserAccessProject($this->admin, $project);

        $this->assertTrue($result);
    }

    public function test_can_user_access_project_owner()
    {
        $project = Project::factory()->create(['created_by' => $this->user->id]);

        $result = $this->projectService->canUserAccessProject($this->user, $project);

        $this->assertTrue($result);
    }

    public function test_can_user_access_project_with_assigned_task()
    {
        $project = Project::factory()->create(['created_by' => $this->admin->id]);
        
        // Create a task assigned to the user
        $project->tasks()->create([
            'title' => 'Test Task',
            'description' => 'Test description',
            'status' => 'pending',
            'due_date' => '2025-12-31',
            'assigned_to' => $this->user->id,
        ]);

        $result = $this->projectService->canUserAccessProject($this->user, $project);

        $this->assertTrue($result);
    }

    public function test_can_user_access_project_denied()
    {
        $otherUser = User::factory()->create(['role' => 'user']);
        $project = Project::factory()->create(['created_by' => $this->admin->id]);

        $result = $this->projectService->canUserAccessProject($otherUser, $project);

        $this->assertFalse($result);
    }

    public function test_can_user_modify_project_admin()
    {
        $project = Project::factory()->create(['created_by' => $this->user->id]);

        $result = $this->projectService->canUserModifyProject($this->admin, $project);

        $this->assertTrue($result);
    }

    public function test_can_user_modify_project_owner()
    {
        $project = Project::factory()->create(['created_by' => $this->user->id]);

        $result = $this->projectService->canUserModifyProject($this->user, $project);

        $this->assertTrue($result);
    }

    public function test_can_user_modify_project_denied()
    {
        $otherUser = User::factory()->create(['role' => 'user']);
        $project = Project::factory()->create(['created_by' => $this->admin->id]);

        $result = $this->projectService->canUserModifyProject($otherUser, $project);

        $this->assertFalse($result);
    }

    public function test_search_projects()
    {
        Project::factory()->create([
            'title' => 'Laravel Project',
            'description' => 'A Laravel-based project',
            'created_by' => $this->admin->id
        ]);

        Project::factory()->create([
            'title' => 'Vue.js App',
            'description' => 'A Vue application with Laravel backend',
            'created_by' => $this->admin->id
        ]);

        Project::factory()->create([
            'title' => 'React App',
            'description' => 'A React application',
            'created_by' => $this->admin->id
        ]);

        $results = $this->projectService->searchProjects('Laravel');

        $this->assertEquals(2, $results->count());
    }
}