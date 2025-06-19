<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\Workspace;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProjectSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $workspaces = Workspace::all();

        Project::factory()
            ->count(200)
            ->recycle($workspaces)
            ->create();
    }
}
