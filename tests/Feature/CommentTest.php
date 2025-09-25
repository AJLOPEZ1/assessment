<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CommentTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected User $otherUser;
    protected Project $project;
    protected Task $task;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['role' => 'user']);
        $this->otherUser = User::factory()->create(['role' => 'user']);
        
        $this->project = Project::factory()->create([
            'created_by' => $this->user->id
        ]);
        
        $this->task = Task::factory()->create([
            'project_id' => $this->project->id,
            'assigned_to' => $this->user->id
        ]);
    }

    public function test_user_can_get_task_comments()
    {
        Sanctum::actingAs($this->user);

        // Create some comments
        Comment::factory(3)->create([
            'task_id' => $this->task->id,
            'user_id' => $this->user->id
        ]);

        $response = $this->getJson("/api/tasks/{$this->task->id}/comments");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'comments' => [
                        '*' => [
                            'id',
                            'body',
                            'task_id',
                            'user_id',
                            'user',
                            'created_at',
                            'updated_at'
                        ]
                    ]
                ]
            ]);
    }

    public function test_user_can_create_comment()
    {
        Sanctum::actingAs($this->user);

        $commentData = [
            'body' => 'This is a test comment'
        ];

        $response = $this->postJson("/api/tasks/{$this->task->id}/comments", $commentData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'comment' => [
                        'id',
                        'body',
                        'task_id',
                        'user_id',
                        'created_at',
                        'updated_at'
                    ]
                ]
            ]);

        $this->assertDatabaseHas('comments', [
            'body' => 'This is a test comment',
            'task_id' => $this->task->id,
            'user_id' => $this->user->id
        ]);
    }

    public function test_comment_creation_validation()
    {
        Sanctum::actingAs($this->user);

        // Test empty content
        $response = $this->postJson("/api/tasks/{$this->task->id}/comments", []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['body']);

        // Test content too short
        $response = $this->postJson("/api/tasks/{$this->task->id}/comments", [
            'body' => 'Hi'
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['body']);
    }

    public function test_user_can_comment_on_accessible_task()
    {
        // Create a task where the user is assigned
        $accessibleTask = Task::factory()->create([
            'project_id' => $this->project->id,
            'assigned_to' => $this->user->id
        ]);

        Sanctum::actingAs($this->user);

        $commentData = [
            'body' => 'This is a comment on an accessible task'
        ];

        $response = $this->postJson("/api/tasks/{$accessibleTask->id}/comments", $commentData);

        $response->assertStatus(201);

        $this->assertDatabaseHas('comments', [
            'body' => 'This is a comment on an accessible task',
            'task_id' => $accessibleTask->id,
            'user_id' => $this->user->id
        ]);
    }

    public function test_user_cannot_comment_on_inaccessible_task()
    {
        // Create a task in a different project
        $otherProject = Project::factory()->create([
            'created_by' => $this->otherUser->id
        ]);
        
        $inaccessibleTask = Task::factory()->create([
            'project_id' => $otherProject->id,
            'assigned_to' => $this->otherUser->id
        ]);

        Sanctum::actingAs($this->user);

        $commentData = [
            'body' => 'This should not be allowed'
        ];

        $response = $this->postJson("/api/tasks/{$inaccessibleTask->id}/comments", $commentData);

        $response->assertStatus(403);

        $this->assertDatabaseMissing('comments', [
            'body' => 'This should not be allowed',
            'task_id' => $inaccessibleTask->id,
            'user_id' => $this->user->id
        ]);
    }

    public function test_user_cannot_access_comments_on_inaccessible_task()
    {
        // Create a task in a different project
        $otherProject = Project::factory()->create([
            'created_by' => $this->otherUser->id
        ]);
        
        $inaccessibleTask = Task::factory()->create([
            'project_id' => $otherProject->id,
            'assigned_to' => $this->otherUser->id
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson("/api/tasks/{$inaccessibleTask->id}/comments");

        $response->assertStatus(403);
    }

    public function test_unauthenticated_user_cannot_access_comments()
    {
        $response = $this->getJson("/api/tasks/{$this->task->id}/comments");
        $response->assertStatus(401);

        $response = $this->postJson("/api/tasks/{$this->task->id}/comments", [
            'body' => 'This should not work'
        ]);
        $response->assertStatus(401);
    }

    public function test_comments_are_returned_with_user_information()
    {
        Sanctum::actingAs($this->user);

        $comment = Comment::factory()->create([
            'task_id' => $this->task->id,
            'user_id' => $this->user->id,
            'body' => 'Test comment with user info'
        ]);

        $response = $this->getJson("/api/tasks/{$this->task->id}/comments");

        $response->assertStatus(200)
            ->assertJsonPath('data.comments.0.body', 'Test comment with user info')
            ->assertJsonPath('data.comments.0.user.id', $this->user->id)
            ->assertJsonPath('data.comments.0.user.name', $this->user->name)
            ->assertJsonPath('data.comments.0.user.email', $this->user->email);
    }

    public function test_admin_can_access_all_task_comments()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Sanctum::actingAs($admin);

        // Create comments on various tasks
        Comment::factory()->create([
            'task_id' => $this->task->id,
            'user_id' => $this->user->id
        ]);

        $response = $this->getJson("/api/tasks/{$this->task->id}/comments");

        $response->assertStatus(200);
    }

    public function test_comment_body_field_validation()
    {
        Sanctum::actingAs($this->user);

        $commentData = [
            'body' => 'Testing field validation'
        ];

        $response = $this->postJson("/api/tasks/{$this->task->id}/comments", $commentData);

        $response->assertStatus(201)
            ->assertJsonPath('data.comment.body', 'Testing field validation');

        $this->assertDatabaseHas('comments', [
            'body' => 'Testing field validation',
            'task_id' => $this->task->id,
            'user_id' => $this->user->id
        ]);
    }
}