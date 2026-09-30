<?php

namespace Database\Factories;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Created users get exactly one Spatie role: apprentice unless a state says otherwise.
     */
    public function configure(): static
    {
        return $this->afterCreating(fn (User $user) => $user->syncRoles(UserRole::Apprentice->value));
    }

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Indicate that the model has two-factor authentication configured.
     */
    public function withTwoFactor(): static {}

    /**
     * Indicate that the user is a coach.
     */
    public function coach(): static
    {
        return $this->afterCreating(fn (User $user) => $user->syncRoles(UserRole::Coach->value));
    }

    /**
     * Indicate that the user is a trainer.
     */
    public function trainer(): static
    {
        return $this->afterCreating(fn (User $user) => $user->syncRoles(UserRole::Trainer->value));
    }

    /**
     * Indicate that the user is an admin (local-only dev role).
     */
    public function admin(): static
    {
        return $this->afterCreating(fn (User $user) => $user->syncRoles(UserRole::Admin->value));
    }
}
