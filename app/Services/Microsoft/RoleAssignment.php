<?php

namespace App\Services\Microsoft;

use App\Enums\UserRole;

/**
 * What an Entra user is in the app: a role, and for apprentices and trainers the
 * section (apprenticeships.code). Coaches have no section.
 */
final readonly class RoleAssignment
{
    public function __construct(
        public UserRole $role,
        public ?string $apprenticeshipCode,
    ) {}
}
