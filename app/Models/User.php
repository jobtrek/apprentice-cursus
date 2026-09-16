<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
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

/**
 * @property int $id
 * @property string|null $azure_id
 * @property string|null $tenant_id
 * @property string $name
 * @property string $email
 * @property bool|null $is_mp Maturité professionnelle track. NULL = not applicable (non-apprentice roles).
 * @property bool $is_active Deactivation flag. Users are never deleted, only deactivated.
 * @property UserRole $role
 * @property int|null $apprenticeship_id
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
/*
 * Only self-service profile fields are mass-assignable. Everything that decides what a
 * user is allowed to do or who they are attached to — role, is_active, is_mp,
 * apprenticeship_id, coach_id, trainer_id — must be assigned explicitly by the
 * administration flow that owns it, never filled from a request payload.
 */
#[Fillable(['name', 'email', 'password', 'azure_id', 'tenant_id'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

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
            'is_mp' => 'boolean',
            'is_active' => 'boolean',
            'role' => UserRole::class,
        ];
    }

    public function apprenticeship(): BelongsTo
    {
        return $this->belongsTo(Apprenticeship::class);
    }

    public function coach(): BelongsTo
    {
        return $this->belongsTo(self::class, 'coach_id');
    }

    public function trainer(): BelongsTo
    {
        return $this->belongsTo(self::class, 'trainer_id');
    }

    public function coachees(): HasMany
    {
        return $this->hasMany(self::class, 'coach_id');
    }

    public function trainees(): HasMany
    {
        return $this->hasMany(self::class, 'trainer_id');
    }

    public function grades(): HasMany
    {
        return $this->hasMany(Grade::class);
    }

    public function evaluationResults(): HasMany
    {
        return $this->hasMany(EvaluationResult::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class, 'author_id');
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }
}
