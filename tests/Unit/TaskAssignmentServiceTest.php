<?php

namespace Tests\Unit;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Services\TaskAssignmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class TaskAssignmentServiceTest extends TestCase
{
    use RefreshDatabase;

    protected TaskAssignmentService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new TaskAssignmentService();
    }

    public function test_assign_task_successfully()
    {
        $user = User::factory()->user()->create();
        $project = Project::factory()->create();
        $initialUser = User::factory()->user()->create();
        $task = Task::factory()->create([
            'project_id' => $project->id,
            'assigned_to' => $initialUser->id
        ]);

        $result = $this->service->assignTask($task, $user->id);

        $this->assertEquals($user->id, $result->assigned_to);
        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'assigned_to' => $user->id
        ]);
    }

    public function test_cannot_assign_task_to_nonexistent_user()
    {
        $project = Project::factory()->create();
        $task = Task::factory()->create(['project_id' => $project->id]);

        $this->expectException(ValidationException::class);
        $this->service->assignTask($task, 99999);
    }

    public function test_cannot_assign_task_to_admin()
    {
        $admin = User::factory()->admin()->create();
        $project = Project::factory()->create();
        $task = Task::factory()->create(['project_id' => $project->id]);

        $this->expectException(ValidationException::class);
        $this->service->assignTask($task, $admin->id);
    }

    public function test_validate_assignment_with_valid_data()
    {
        $user = User::factory()->user()->create();
        $project = Project::factory()->create();
        $task = Task::factory()->create(['project_id' => $project->id]);

        $isValid = $this->service->validateAssignment([
            'task_id' => $task->id,
            'user_id' => $user->id
        ]);

        $this->assertTrue($isValid);
    }

    public function test_validate_assignment_with_invalid_data()
    {
        $isValid = $this->service->validateAssignment([
            'task_id' => 99999,
            'user_id' => 99999
        ]);

        $this->assertFalse($isValid);
    }

    public function test_get_user_task_stats()
    {
        $user = User::factory()->user()->create();
        $project = Project::factory()->create();
        
        Task::factory(3)->create([
            'assigned_to' => $user->id,
            'project_id' => $project->id,
            'status' => 'pending'
        ]);
        
        Task::factory(2)->create([
            'assigned_to' => $user->id,
            'project_id' => $project->id,
            'status' => 'done'
        ]);

        $stats = $this->service->getUserTaskStats($user->id);

        $this->assertEquals(5, $stats['total_tasks']);
        $this->assertEquals(3, $stats['pending_tasks']);
        $this->assertEquals(2, $stats['completed_tasks']);
    }

    public function test_get_stats_for_nonexistent_user()
    {
        $stats = $this->service->getUserTaskStats(99999);
        
        $this->assertEquals([], $stats);
    }
}
