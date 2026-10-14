<?php

namespace App\Enums;

use App\Models\FormationPlan;

/**
 * The Entra ID groups that grant access to the application. The backing value
 * is the key under `services.azure.groups` holding the Graph group object id.
 */
enum AzureGroup: string
{
    case ApprenticesIt = 'apprentices_IT';
    case ApprenticesEc = 'apprentices_EC';
    case TrainerIt = 'trainer_IT';
    case TrainerEc = 'trainer_EC';
    case Coach = 'coach';

    public function role(): UserRole
    {
        return match ($this) {
            self::ApprenticesIt, self::ApprenticesEc => UserRole::Apprentice,
            self::TrainerIt, self::TrainerEc => UserRole::Trainer,
            self::Coach => UserRole::Coach,
        };
    }

    /**
     * Formation plans of the group's section: a trainer trains all of them, a new
     * apprentice starts on the first one (the MP variant is chosen by hand).
     * Coaches belong to no section.
     *
     * @return list<string>
     */
    public function formationPlans(): array
    {
        return match ($this) {
            self::ApprenticesIt, self::TrainerIt => [FormationPlan::IT],
            self::ApprenticesEc, self::TrainerEc => [FormationPlan::EC, FormationPlan::EC_MP],
            self::Coach => [],
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
