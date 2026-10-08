<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\Permission;
use App\Enums\UserRole;
use Carbon\CarbonImmutable;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
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
 * @property CarbonImmutable|null $synced_at Last time the Entra account sync confirmed this user. NULL = never synced (local accounts).
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
            'synced_at' => 'datetime',
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

    /**
     * The section, reached through the context. Read-only: to change it,
     * write `apprenticeship_context_id`.
     *
     * @return HasOneThrough<Apprenticeship, ApprenticeshipContext, $this>
     */
    public function apprenticeship(): HasOneThrough
    {
        return $this->hasOneThrough(
            Apprenticeship::class,
            ApprenticeshipContext::class,
            firstKey: 'id',                          // apprenticeship_contexts.id
            secondKey: 'id',                         // apprenticeships.id
            localKey: 'apprenticeship_context_id',   // users.apprenticeship_context_id
            secondLocalKey: 'apprenticeship_id',     // apprenticeship_contexts.apprenticeship_id
        );
    }

    /** Id of the section, null for a user without a context (coach, admin). */
    public function apprenticeshipId(): ?int
    {
        return $this->apprenticeshipContext?->apprenticeship_id;
    }

    public function coach(): BelongsTo
    {
        return $this->belongsTo(self::class, 'coach_id');
    }

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
        return $this->hasMany(ApprenticeshipPeriod::class);
    }

    /** @return HasMany<Grade, $this> */
    public function grades(): HasMany
    {
        return $this->hasMany(Grade::class);
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

    /**
     * Active apprentices this user follows: a coach its coachees, a trainer the
     * apprentices assigned to it in its own section, the local admin everyone.
     *
     * @return Builder<self>
     */
    public function listedApprentices(): Builder
    {
        $query = self::role(UserRole::Apprentice->value)->where('is_active', true);

        if ($this->isLocalAdmin()) {
            return $query;
        }

        return match ($this->role) {
            UserRole::Coach => $query->where('coach_id', $this->id),
            UserRole::Trainer => $query->where('trainer_id', $this->id)
                ->where('apprenticeship_id', $this->apprenticeship_id),
            default => $query->whereRaw('false'),
        };
    }

    /**
     * Column this user fills when taking an apprentice on: `coach_id` for a
     * coach, `trainer_id` for a trainer, null for anyone else (the local admin
     * assigns from the list's selects instead).
     */
    public function selfAssignmentColumn(): ?string
    {
        return match (true) {
            $this->hasPermissionTo(Permission::CoachingAssignSelf->value) => 'coach_id',
            $this->hasPermissionTo(Permission::TrainingAssignSelf->value) => 'trainer_id',
            default => null,
        };
    }

    /**
     * Active apprentices this user can take on (see UserPolicy::assignSelf()):
     * no coach yet for a coach, no trainer yet and in its own section for a trainer.
     *
     * @return Builder<self>
     */
    public function assignableApprentices(): Builder
    {
        $query = self::role(UserRole::Apprentice->value)->where('is_active', true);

        return match ($this->selfAssignmentColumn()) {
            'coach_id' => $query->whereNull('coach_id'),
            'trainer_id' => $query->whereNull('trainer_id')
                ->whereNotNull('apprenticeship_id')
                ->where('apprenticeship_id', $this->apprenticeship_id),
            default => $query->whereRaw('false'),
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
            UserRole::Trainer => $apprentice->trainer_id === $this->id
                && $this->apprenticeship_id !== null
                && $apprentice->apprenticeship_id === $this->apprenticeship_id,
            default => false,
        };
    }
}
