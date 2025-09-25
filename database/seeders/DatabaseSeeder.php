<?php

namespace Database\Seeders;

use App\Models\Comment;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create users with specific roles
        $admins = User::factory(3)->admin()->create();
        $managers = User::factory(3)->manager()->create();
        $users = User::factory(5)->user()->create();

        // Create projects with admins as creators
        $projects = Project::factory(5)->create([
            'created_by' => $admins->random()->id,
        ]);

        // Create tasks assigned to all users
        $allUsers = collect([$admins, $managers, $users])->flatten();
        
        Task::factory(10)->create()->each(function ($task) use ($projects, $allUsers) {
            $task->update([
                'project_id' => $projects->random()->id,
                'assigned_to' => $allUsers->random()->id,
            ]);
        });

        // Create comments from all users on random tasks
        $tasks = Task::all();
        Comment::factory(10)->create()->each(function ($comment) use ($tasks, $allUsers) {
            $comment->update([
                'task_id' => $tasks->random()->id,
                'user_id' => $allUsers->random()->id,
            ]);
        });
    }
}
