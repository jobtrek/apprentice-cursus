<?php

namespace App\Enums;

use Database\Seeders\ApprenticeshipSeeder;

/**
 * The Entra ID groups that grant access to the application. The backing value
 * is the key under `services.azure.groups` holding the Graph group object id.
 */
enum AzureGroup: string
{
    case ApprenticesIt = 'apprentices_IT';
    case ApprenticesEc = 'apprentices_EC';
    case Trainer = 'trainer';
    case Coach = 'coach';

    public function role(): UserRole
    {
        return match ($this) {
            self::ApprenticesIt, self::ApprenticesEc => UserRole::Apprentice,
            self::Trainer => UserRole::Trainer,
            self::Coach => UserRole::Coach,
        };
    }

    /** Coaches belong to no section. */
    public function apprenticeship(): ?string
    {
        return match ($this) {
            self::ApprenticesIt, self::Trainer => ApprenticeshipSeeder::IT,
            self::ApprenticesEc => ApprenticeshipSeeder::EC,
            self::Coach => null,
        };
    }

    public function groupId(): ?string
    {
        $id = config("services.azure.groups.{$this->value}");

        return is_string($id) && $id !== '' ? $id : null;
    }

    public static function fromGroupId(string $id): ?self
    {
        if ($id === '') {
            return null;
        }

        foreach (self::cases() as $case) {
            if ($case->groupId() === $id) {
                return $case;
            }
        }

        return null;
    }
}
