<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Workspace>
 */
class WorkspaceFactory extends Factory
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
            'user_id' => User::factory(),
            'invite_code' => fake()->unique()->regexify('[A-Za-z0-9]{10}'),
        ];
    }
}
