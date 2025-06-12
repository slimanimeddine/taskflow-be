<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Task>
 */
class TaskFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'description' => fake()->paragraph(),
            'due_date' => fake()->dateTime(),
            'status' => fake()->randomElement(['backlog', 'todo', 'in_progress', 'in_review', 'done']),
            'position' => fake()->numberBetween(1000, 1000000),
            'workspace_id' => Workspace::factory(),
            'project_id' => Project::factory(),
            'assignee_id' => User::factory(),
        ];
    }
}
