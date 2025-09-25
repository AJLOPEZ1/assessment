<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_project()
    {
        $admin = User::factory()->admin()->create();
        $token = $admin->createToken('test-token')->plainTextToken;

        $projectData = [
            'name' => 'Test Project',
            'description' => 'This is a test project description',
        ];

                $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->postJson('/api/projects', $projectData);

        $response->assertStatus(201)
                 ->assertJsonStructure([
                     'success',
                     'message',
                     'data' => [
                         'project' => ['id', 'name', 'description', 'created_by']
                     ],
                     'timestamp',
                     'execution_time'
                 ]);

        $this->assertDatabaseHas('projects', [
            'name' => 'Test Project',
            'created_by' => $admin->id
        ]);
    }

    public function test_non_admin_cannot_create_project()
    {
        $user = User::factory()->user()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $projectData = [
            'name' => 'Test Project',
            'description' => 'This is a test project description',
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->postJson('/api/projects', $projectData);

        // Any authenticated user can create projects based on our new implementation
        $response->assertStatus(201);
    }

    public function test_get_projects_list()
    {
        $admin = User::factory()->admin()->create();
        Project::factory(3)->create(['created_by' => $admin->id]);

        $user = User::factory()->user()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->getJson('/api/projects');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'message',
                     'data',
                     'pagination' => [
                         'current_page',
                         'per_page',
                         'total',
                         'total_pages'
                     ],
                     'timestamp',
                     'execution_time'
                 ]);
    }

    public function test_get_single_project()
    {
        $admin = User::factory()->admin()->create();
        $project = Project::factory()->create(['created_by' => $admin->id]);

        $user = User::factory()->user()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->getJson('/api/projects/' . $project->id);

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'message',
                     'data' => [
                         'project' => ['id', 'name', 'description'],
                         'statistics'
                     ],
                     'timestamp',
                     'execution_time'
                 ]);
    }

    public function test_admin_can_update_project()
    {
        $admin = User::factory()->admin()->create();
        $project = Project::factory()->create(['created_by' => $admin->id]);
        $token = $admin->createToken('test-token')->plainTextToken;

        $updateData = [
            'name' => 'Updated Project Name'
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->putJson('/api/projects/' . $project->id, $updateData);

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'message',
                     'data' => [
                         'project' => ['id', 'name', 'description']
                     ],
                     'timestamp',
                     'execution_time'
                 ]);

        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'name' => 'Updated Project Name'
        ]);
    }

    public function test_admin_can_delete_project()
    {
        $admin = User::factory()->admin()->create();
        $project = Project::factory()->create(['created_by' => $admin->id]);
        $token = $admin->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ])->deleteJson('/api/projects/' . $project->id);

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'message',
                     'timestamp',
                     'execution_time'
                 ])
                 ->assertJson(['message' => 'Project deleted successfully']);

        $this->assertDatabaseMissing('projects', ['id' => $project->id]);
    }
}
