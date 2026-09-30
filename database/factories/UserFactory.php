<?php

namespace Database\Factories;

use App\Enums\UserRole;
use App\Models\Apprenticeship;
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
            'role' => UserRole::Apprentice,
            'apprenticeship_id' => fn () => Apprenticeship::where('code', Apprenticeship::IT)->value('id'),
        ];
    }

    /**
     * An apprentice of the given section (it / ec).
     */
    public function apprentice(string $code = Apprenticeship::IT): static
    {
        return $this->state(fn () => [
            'role' => UserRole::Apprentice,
            'apprenticeship_id' => Apprenticeship::where('code', $code)->value('id'),
        ]);
    }

    /**
     * A trainer of the given section (it / ec).
     */
    public function trainer(string $code = Apprenticeship::IT): static
    {
        return $this->state(fn () => [
            'role' => UserRole::Trainer,
            'apprenticeship_id' => Apprenticeship::where('code', $code)->value('id'),
        ]);
    }

    public function coach(): static
    {
        return $this->state(fn () => [
            'role' => UserRole::Coach,
            'apprenticeship_id' => null,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
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
}
