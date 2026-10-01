<?php

namespace Database\Factories;

use App\Models\Comment;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Defaults to a project as the commentable since Grade has no factory; attach it to
 * a grade with `Comment::factory()->for($grade, 'commentable')`.
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
            'commentable_type' => 'project',
            'commentable_id' => Project::factory(),
            'body' => fake()->paragraph(),
        ];
    }
}
