<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Project>
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
        return [
            'user_id' => User::factory(),
            'title' => fake()->sentence(3),
            'organization' => fake()->company(),
            'description' => fake()->paragraph(),
            'responsibilities' => fake()->jobTitle(),
            'technologies' => 'Laravel, Vue.js',
            'repository_url' => fake()->url(),
            'demo_path' => fake()->url(),
            'date_start' => fake()->date(),
            'date_end' => null,
        ];
    }
}
