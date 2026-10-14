<?php

namespace App\Enums;

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
    /** Assign any coach to any apprentice. No production role has it: only the local admin (Gate::before). */
    case SupervisionManage = 'supervision.manage';

    /** @return array<string, list<self>> */
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
            // No explicit permissions: in local the Gate::before bypass grants everything,
            // so new permissions are covered automatically.
            UserRole::Admin->value => [],
        ];
    }
}
