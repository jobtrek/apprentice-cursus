<?php

namespace App\Support\Gradebook;

/**
 * Grading thresholds on the Swiss 1–6 scale. Shared with the front as the `grading`
 * Inertia prop, so a grade is judged the same way on both sides.
 */
final class Grading
{
    /** Minimum passing grade (CFC). */
    public const PASS = 4.0;

    /** From here on, a grade or an average counts as good. */
    public const GOOD = 4.5;

    /** Without a grade for longer than this, an apprentice is flagged. */
    public const STALE_AFTER_DAYS = 90;

    /**
     * @return array{pass: float, good: float, stale_after_days: int}
     */
    public static function thresholds(): array
    {
        return [
            'pass' => self::PASS,
            'good' => self::GOOD,
            'stale_after_days' => self::STALE_AFTER_DAYS,
        ];
    }
}
