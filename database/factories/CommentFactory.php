<?php

namespace Database\Factories;

use App\Models\Comment;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Defaults to a comment on a project since Grade has no factory; attach it to a
 * grade with `Comment::factory()->for($grade)->state(['project_id' => null])`.
 *
 * @extends Factory<Comment>
 */
class CommentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'author_id' => User::factory(),
            'project_id' => Project::factory(),
            'body' => fake()->paragraph(),
        ];
    }
}
