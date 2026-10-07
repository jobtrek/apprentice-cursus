<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\Permission;
use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Spatie\Permission\Traits\HasRoles;

/**
 * @property int $id
 * @property string|null $azure_id
 * @property string|null $tenant_id
 * @property string $name
 * @property string $email
 * @property bool $is_active Deactivation flag. Users are never deleted, only deactivated.
 * @property-read UserRole|null $role Derived from the Spatie role; use syncRoles() to change it.
 * @property int|null $apprenticeship_context_id
 * @property int|null $coach_id
 * @property int|null $trainer_id
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'email', 'password', 'azure_id', 'tenant_id', 'apprenticeship_context_id'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    /**
     * Timestamps were dropped from the users table (see
     * 2026_09_17_083508_drop_default_columns_from_users_table); Azure SSO is
     * the sole write path and doesn't need them.
     */
    public $timestamps = false;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    /**
     * The single Spatie role of the user as an enum. Spatie is the only source
     * of truth; there is no users.role column.
     *
     * Deliberately a legacy getter, not an Attribute::make() `role()` method:
     * that method would shadow Spatie's `scopeRole()` in `User::role('coach')`.
     */
    public function getRoleAttribute(): ?UserRole
    {
        $name = $this->getRoleNames()->first();

        return $name === null ? null : UserRole::tryFrom($name);
    }

    /** @return BelongsTo<ApprenticeshipContext, $this> */
    public function apprenticeshipContext(): BelongsTo
    {
        return $this->belongsTo(ApprenticeshipContext::class);
    }

    /** @return BelongsTo<User, $this> */
    public function coach(): BelongsTo
    {
        return $this->belongsTo(self::class, 'coach_id');
    }

    /** @return BelongsTo<User, $this> */
    public function trainer(): BelongsTo
    {
        return $this->belongsTo(self::class, 'trainer_id');
    }

    /** @return HasMany<User, $this> */
    public function coachees(): HasMany
    {
        return $this->hasMany(self::class, 'coach_id');
    }

    /** @return HasMany<User, $this> */
    public function trainees(): HasMany
    {
        return $this->hasMany(self::class, 'trainer_id');
    }

    /** @return HasMany<ApprenticeshipPeriod, $this> */
    public function apprenticeshipPeriods(): HasMany
    {
        return $this->hasMany(ApprenticeshipPeriod::class, 'apprentice_id');
    }

    /** @return HasMany<Grade, $this> */
    public function grades(): HasMany
    {
        return $this->hasMany(Grade::class, 'apprentice_id');
    }

    /** @return HasMany<Comment, $this> */
    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class, 'author_id');
    }

    /**
     * @return HasMany<Project, $this>
     */
    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    /**
     * The admin role only grants anything in the local environment; elsewhere
     * this is always false.
     */
    public function isLocalAdmin(): bool
    {
        return app()->environment('local') && $this->hasRole(UserRole::Admin->value);
    }

    /**
     * Named route a user lands on after signing in, chosen by permission
     * rather than by role so new roles only need permissions.
     */
    public function homeRoute(): string
    {
        return match (true) {
            $this->isLocalAdmin() => 'apprentisdashboard',
            $this->hasPermissionTo(Permission::GradesViewOwn->value) => 'grades.dashboard',
            $this->hasPermissionTo(Permission::ApprenticesViewList->value) => 'apprentisdashboard',
            default => 'home',
        };
    }

    public function supervises(self $apprentice): bool
    {
        if ($apprentice->id === $this->id || ! $apprentice->hasRole(UserRole::Apprentice->value)) {
            return false;
        }

        if ($this->isLocalAdmin()) {
            return true;
        }

        return match ($this->role) {
            UserRole::Coach => $apprentice->coach_id === $this->id,
            UserRole::Trainer => $this->apprenticeship_context_id !== null
                && $apprentice->apprenticeshipContext?->apprenticeship_id === $this->apprenticeshipContext?->apprenticeship_id,
            default => false,
        };
    }
}
