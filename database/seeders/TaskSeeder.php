<?php

namespace Database\Seeders;

use App\Models\Member;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TaskSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $projects = Project::all();

        $statuses = ['backlog', 'todo', 'in_progress', 'in_review', 'done'];

        $tasksPerProject = 15;

        foreach ($projects as $project) {
            $workspaceMembers = Member::where('workspace_id', $project->workspace_id)->pluck('user_id');

            if ($workspaceMembers->isEmpty()) {
                continue;
            }

            for ($i = 0; $i < $tasksPerProject; $i++) {
                $randomStatus = $statuses[array_rand($statuses)];

                $randomAssigneeId = $workspaceMembers->random();

                $highestPosition = Task::where('workspace_id', $project->workspace_id)
                    ->where('status', $randomStatus)
                    ->max('position');

                $newPosition = $highestPosition !== null ? $highestPosition + 1 : 1;

                Task::factory()->create([
                    'status' => $randomStatus,
                    'position' => $newPosition,
                    'workspace_id' => $project->workspace_id,
                    'project_id' => $project->id,
                    'assignee_id' => $randomAssigneeId,
                ]);
            }
        }
    }
}
