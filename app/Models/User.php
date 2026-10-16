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
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Spatie\Permission\Traits\HasRoles;

/**
 * @property int $id
 * @property string|null $azure_id
 * @property string|null $tenant_id
 * @property string $name
 * @property string $email
 * @property bool $is_active Deactivation flag. Users are never deleted, only deactivated.
 * @property-read UserRole|null $role Derived from the Spatie role; use syncRoles() to change it.
 * @property int|null $formation_plan_id The apprentice's plan; MP or standard is part of the plan.
 * @property int|null $coach_id
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property CarbonImmutable|null $synced_at Last time the Entra account sync confirmed this user. NULL = never synced (local accounts).
 */
#[Fillable(['name', 'email', 'password', 'azure_id', 'tenant_id'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    /** The users table has no timestamps: Azure SSO is the sole write path and doesn't need them. */
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

    /**
     * The plan an apprentice follows.
     *
     * @return BelongsTo<FormationPlan, $this>
     */
    public function formationPlan(): BelongsTo
    {
        return $this->belongsTo(FormationPlan::class);
    }

    /**
     * The plans a trainer trains (IT, EC…).
     *
     * @return BelongsToMany<FormationPlan, $this>
     */
    public function trainedFormationPlans(): BelongsToMany
    {
        return $this->belongsToMany(FormationPlan::class, 'users_formation_plans');
    }

    /**
     * @return BelongsTo<self, $this>
     */
    public function coach(): BelongsTo
    {
        return $this->belongsTo(self::class, 'coach_id');
    }

    /**
     * @return HasMany<self, $this>
     */
    public function coachees(): HasMany
    {
        return $this->hasMany(self::class, 'coach_id');
    }

    /**
     * @return HasMany<Grade, $this>
     */
    public function grades(): HasMany
    {
        return $this->hasMany(Grade::class);
    }

    /**
     * @return HasMany<Comment, $this>
     */
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
     * The supervision rule, active or not: a coach its coachees, a trainer the
     * apprentices of the plans it trains, the local admin everyone.
     *
     * @return Builder<self>
     */
    public function supervisedApprentices(): Builder
    {
        $query = self::role(UserRole::Apprentice->value);

        if ($this->isLocalAdmin()) {
            return $query;
        }

        return match ($this->role) {
            UserRole::Coach => $query->where('coach_id', $this->id),
            UserRole::Trainer => $query->whereIn(
                'formation_plan_id',
                $this->trainedFormationPlans()->select('formation_plans.id'),
            ),
            default => $query->whereRaw('false'),
        };
    }

    /**
     * @return Builder<self>
     */
    public function listedApprentices(): Builder
    {
        return $this->supervisedApprentices()->where('is_active', true);
    }

    /** Same rule as `supervisedApprentices()`, checked in memory: only a trainer costs a query. */
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
            UserRole::Trainer => $apprentice->formation_plan_id !== null
                && $this->trainedFormationPlans()->whereKey($apprentice->formation_plan_id)->exists(),
            default => false,
        };
    }

    /**
     * Only a coach takes single apprentices on. A trainer follows whole plans;
     * the local admin assigns from the list's selects instead.
     */
    public function canSelfAssign(): bool
    {
        return $this->hasPermissionTo(Permission::CoachingAssignSelf->value);
    }

    /**
     * Active apprentices with no coach yet, for a coach (see UserPolicy::assignSelf()).
     *
     * @return Builder<self>
     */
    public function assignableApprentices(): Builder
    {
        $query = self::role(UserRole::Apprentice->value)->where('is_active', true)->whereNull('coach_id');

        return $this->canSelfAssign() ? $query : $query->whereRaw('false');
    }

    /**
     * Active coach and trainers who follow this apprentice: whom to notify about their
     * grades. The trainers are the ones of the apprentice's plan, as in supervises().
     *
     * @return Collection<int, self>
     */
    public function supervisors(): Collection
    {
        $trainers = $this->formation_plan_id === null
            ? collect()
            : self::role(UserRole::Trainer->value)
                ->where('is_active', true)
                ->whereHas('trainedFormationPlans', fn (Builder $plans) => $plans->whereKey($this->formation_plan_id))
                ->get();

        return collect([$this->coach])
            ->filter(fn (?self $coach): bool => $coach !== null && $coach->is_active)
            ->concat($trainers)
            ->values();
    }
}
