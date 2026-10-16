<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A PDF read by GradeController::readScan. Costs are in USD.
 *
 * @property int $id
 * @property int $user_id
 * @property int|null $grade_id
 * @property int|null $suggested_node_id
 * @property string $subject
 * @property string|null $grade decimal cast: string at runtime, not float
 * @property float|null $confidence
 * @property bool $flagged
 * @property int $pages_read
 * @property string $gemini_cost
 * @property string $jev_cost
 * @property int $duration_ms
 * @property CarbonImmutable $created_at
 */
#[Fillable(['user_id', 'subject', 'grade', 'confidence', 'flagged', 'pages_read', 'gemini_cost', 'jev_cost', 'duration_ms'])]
class OcrScan extends Model
{
    /** A reading below this Jev confidence is flagged for a closer look. */
    public const float FLAG_BELOW = 0.5;

    protected function casts(): array
    {
        return [
            'grade' => 'decimal:1',
            'confidence' => 'float',
            'flagged' => 'boolean',
            'gemini_cost' => 'decimal:6',
            'jev_cost' => 'decimal:6',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Grade, $this>
     */
    public function savedGrade(): BelongsTo
    {
        return $this->belongsTo(Grade::class, 'grade_id');
    }
}
