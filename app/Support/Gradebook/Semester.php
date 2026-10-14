<?php

namespace App\Support\Gradebook;

use Carbon\CarbonImmutable;

/**
 * No semester is stored: it is derived from the test date. A school year starts in
 * August; S1 runs August–January, S2 February–July. With no apprenticeship start date
 * in the database, the school year of the apprentice's first grade counts as year 1.
 */
final class Semester
{
    public const MAX = 8;

    /** SQL twin of of(), for a query on `grades`: the first grade comes from a correlated subquery. */
    public const SQL = <<<'SQL'
        LEAST(8, 2 * (
            (EXTRACT(YEAR FROM grades.test_date)::int - CASE WHEN EXTRACT(MONTH FROM grades.test_date) < 8 THEN 1 ELSE 0 END)
            - (SELECT EXTRACT(YEAR FROM MIN(g.test_date))::int - CASE WHEN EXTRACT(MONTH FROM MIN(g.test_date)) < 8 THEN 1 ELSE 0 END
               FROM grades g WHERE g.user_id = grades.user_id)
        ) + CASE WHEN EXTRACT(MONTH FROM grades.test_date) BETWEEN 2 AND 7 THEN 2 ELSE 1 END)
        SQL;

    public static function of(CarbonImmutable $date, CarbonImmutable $firstGradeDate): int
    {
        $semester = 2 * (self::schoolYear($date) - self::schoolYear($firstGradeDate))
            + ($date->month >= 2 && $date->month <= 7 ? 2 : 1);

        return min(self::MAX, max(1, $semester));
    }

    /** Year of apprenticeship (1-based) of the latest grade, counted from the first one. */
    public static function year(CarbonImmutable $latestGradeDate, CarbonImmutable $firstGradeDate): int
    {
        return min(intdiv(self::MAX, 2), self::schoolYear($latestGradeDate) - self::schoolYear($firstGradeDate) + 1);
    }

    /** Calendar year the school year started in. */
    private static function schoolYear(CarbonImmutable $date): int
    {
        return $date->month < 8 ? $date->year - 1 : $date->year;
    }
}
