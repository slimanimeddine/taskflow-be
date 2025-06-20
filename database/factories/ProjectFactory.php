<?php

namespace Database\Factories;

use App\Models\Workspace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Project>
 */
class ProjectFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $selectedImage = fake()->numberBetween(1, 40);

        return [
            'name' => fake()->company(),
            'image_path' => "seeding-photos/{$selectedImage}.jpeg",
            'workspace_id' => Workspace::factory(),
        ];
    }
}
