<?php

namespace App\Enums;

/**
 * Every permission the app checks. Rows are created by RolesAndPermissionsSeeder;
 * policies and routes must reference these instead of role names.
 */
enum Permission: string
{
    case GradesCreate = 'grades.create';
    case GradesViewOwn = 'grades.view-own';
    case GradesViewSupervised = 'grades.view-supervised';
    case GradesComment = 'grades.comment';
    case PortfolioManageOwn = 'portfolio.manage-own';
    case PortfolioViewSupervised = 'portfolio.view-supervised';
    case ApprenticesViewList = 'apprentices.view-list';
    case CoachingAssignSelf = 'coaching.assign-self';

    /**
     * Role → permissions mapping (role_permissions.md).
     *
     * @return array<string, list<self>>
     */
    public static function byRole(): array
    {
        return [
            UserRole::Apprentice->value => [
                self::GradesCreate,
                self::GradesViewOwn,
                self::PortfolioManageOwn,
            ],
            UserRole::Coach->value => [
                self::GradesViewSupervised,
                self::GradesComment,
                self::PortfolioViewSupervised,
                self::ApprenticesViewList,
                self::CoachingAssignSelf,
            ],
            UserRole::Trainer->value => [
                self::GradesViewSupervised,
                self::GradesComment,
                self::PortfolioViewSupervised,
                self::ApprenticesViewList,
            ],
        ];
    }
}
