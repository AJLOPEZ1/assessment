<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TaskTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $manager;
    protected User $user;
    protected Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->manager = User::factory()->create(['role' => 'manager']);
        $this->user = User::factory()->create(['role' => 'user']);
        
        $this->project = Project::factory()->create([
            'created_by' => $this->admin->id
        ]);
    }

    public function test_manager_can_create_task()
    {
        Sanctum::actingAs($this->manager);

        $taskData = [
            'title' => 'Test Task',
            'description' => 'Test task description',
            'due_date' => '2025-12-31',
            'assigned_to' => $this->user->id,
        ];

        $response = $this->postJson("/api/projects/{$this->project->id}/tasks", $taskData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'title',
                    'description',
                    'status',
                    'due_date',
                    'project_id',
                    'assigned_to',
                    'created_at',
                    'updated_at'
                ]
            ]);

        $this->assertDatabaseHas('tasks', [
            'title' => 'Test Task',
            'project_id' => $this->project->id,
            'assigned_to' => $this->user->id
        ]);
    }

    public function test_regular_user_cannot_create_task()
    {
        Sanctum::actingAs($this->user);

        $taskData = [
            'title' => 'Test Task',
            'description' => 'Test task description',
            'due_date' => '2025-12-31',
            'assigned_to' => $this->user->id,
        ];

        $response = $this->postJson("/api/projects/{$this->project->id}/tasks", $taskData);

        $response->assertStatus(403);
    }

    public function test_can_get_project_tasks()
    {
        Sanctum::actingAs($this->user);

        $tasks = Task::factory(3)->create([
            'project_id' => $this->project->id,
            'assigned_to' => $this->user->id
        ]);

        $response = $this->getJson("/api/projects/{$this->project->id}/tasks");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'title',
                        'description',
                        'status',
                        'due_date',
                        'project_id',
                        'assigned_to',
                        'created_at',
                        'updated_at'
                    ]
                ]
            ]);
    }

    public function test_can_show_task_details()
    {
        $task = Task::factory()->create([
            'project_id' => $this->project->id,
            'assigned_to' => $this->user->id
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson("/api/tasks/{$task->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'title',
                    'description',
                    'status',
                    'due_date',
                    'project_id',
                    'assigned_to',
                    'project',
                    'assigned_user',
                    'comments',
                    'created_at',
                    'updated_at'
                ]
            ]);
    }

    public function test_can_update_task()
    {
        $task = Task::factory()->create([
            'project_id' => $this->project->id,
            'assigned_to' => $this->user->id
        ]);

        Sanctum::actingAs($this->user);

        $updateData = [
            'title' => 'Updated Task Title',
            'status' => 'in_progress',
            'description' => 'Updated description'
        ];

        $response = $this->putJson("/api/tasks/{$task->id}", $updateData);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'title',
                    'description',
                    'status'
                ]
            ]);

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'title' => 'Updated Task Title',
            'status' => 'in_progress',
            'description' => 'Updated description'
        ]);
    }

    public function test_manager_can_delete_task()
    {
        $task = Task::factory()->create([
            'project_id' => $this->project->id,
            'assigned_to' => $this->user->id
        ]);

        Sanctum::actingAs($this->manager);

        $response = $this->deleteJson("/api/tasks/{$task->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Task deleted successfully'
            ]);

        $this->assertDatabaseMissing('tasks', [
            'id' => $task->id
        ]);
    }

    public function test_regular_user_cannot_delete_task()
    {
        $task = Task::factory()->create([
            'project_id' => $this->project->id,
            'assigned_to' => $this->user->id
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->deleteJson("/api/tasks/{$task->id}");

        $response->assertStatus(403);

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id
        ]);
    }

    public function test_cannot_access_unauthorized_task()
    {
        $otherUser = User::factory()->create();
        $task = Task::factory()->create([
            'project_id' => $this->project->id,
            'assigned_to' => $otherUser->id
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson("/api/tasks/{$task->id}");

        $response->assertStatus(403);
    }

    public function test_task_creation_validation()
    {
        Sanctum::actingAs($this->manager);

        // Test missing required fields
        $response = $this->postJson("/api/projects/{$this->project->id}/tasks", []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title', 'description', 'due_date', 'assigned_to']);

        // Test invalid assigned_to
        $response = $this->postJson("/api/projects/{$this->project->id}/tasks", [
            'title' => 'Test Task',
            'description' => 'Test description',
            'due_date' => '2025-12-31',
            'assigned_to' => 99999
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['assigned_to']);
    }

    public function test_task_update_validation()
    {
        $task = Task::factory()->create([
            'project_id' => $this->project->id,
            'assigned_to' => $this->user->id
        ]);

        Sanctum::actingAs($this->user);

        // Test invalid status update
        $response = $this->putJson("/api/tasks/{$task->id}", [
            'status' => 'invalid_status'
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['status']);
    }

    public function test_unauthenticated_user_cannot_access_tasks()
    {
        $task = Task::factory()->create([
            'project_id' => $this->project->id
        ]);

        $response = $this->getJson("/api/tasks/{$task->id}");
        $response->assertStatus(401);

        $response = $this->getJson("/api/projects/{$this->project->id}/tasks");
        $response->assertStatus(401);

        $response = $this->postJson("/api/projects/{$this->project->id}/tasks", []);
        $response->assertStatus(401);

        $response = $this->putJson("/api/tasks/{$task->id}", []);
        $response->assertStatus(401);

        $response = $this->deleteJson("/api/tasks/{$task->id}");
        $response->assertStatus(401);
    }
}