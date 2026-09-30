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

    public function role(): UserRole
    {
        return match ($this) {
            self::ApprenticesIt, self::ApprenticesEc => UserRole::Apprentice,
            self::Trainer => UserRole::Trainer,
        };
    }

    public function apprenticeship(): string
    {
        return match ($this) {
            self::ApprenticesIt, self::Trainer => ApprenticeshipSeeder::IT,
            self::ApprenticesEc => ApprenticeshipSeeder::EC,
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

    /**
     * The distinct mapped groups among the given Graph group ids.
     *
     * @param  list<string>  $groupIds
     * @return list<self>
     */
    public static function matching(array $groupIds): array
    {
        $matched = [];

        foreach ($groupIds as $id) {
            $group = self::fromGroupId($id);

            if ($group !== null) {
                $matched[$group->value] = $group;
            }
        }

        return array_values($matched);
    }

    /**
     * The single mapped group of an account, or null when it is in no mapped group
     * or in several of them. Shared by the sign-in and the apprentice sync.
     *
     * @param  list<string>  $groupIds
     */
    public static function resolveFrom(array $groupIds): ?self
    {
        $matched = self::matching($groupIds);

        return count($matched) === 1 ? $matched[0] : null;
    }
}
