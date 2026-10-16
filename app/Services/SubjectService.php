<?php

namespace App\Services;

use App\Models\AverageNode;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Creates, changes and removes subjects (the leaves of the grade trees). Every rule that
 * refuses a change is checked inside the transaction, under the subject's lock, and
 * reported as a validation error on the field it concerns.
 */
class SubjectService
{
    public const string USED_BY_GRADES = 'Cette matière est utilisée par au moins une note : elle ne peut pas être supprimée.';

    public const string LAST_OF_DOMAIN = 'C\'est la dernière matière de son domaine : elle ne peut pas être supprimée.';

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): AverageNode
    {
        return DB::transaction(function () use ($data): AverageNode {
            $parent = AverageNode::query()->findOrFail((int) $data['parent_id']);

            $subject = AverageNode::query()->create([
                'parent_id' => $parent->id,
                'name' => (string) $data['name'],
                'weight' => (int) $data['weight'] / 100,
            ]);

            $this->joinPlansOf($subject, $parent);

            return $subject;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     *
     * @throws ValidationException
     */
    public function update(AverageNode $subject, array $data): void
    {
        $parent = AverageNode::query()->findOrFail((int) $data['parent_id']);

        DB::transaction(function () use ($subject, $parent, $data): void {
            $subject = $this->lockWithParent($subject);
            $moved = $parent->id !== $subject->parent_id;

            if ($moved) {
                $this->guardMove($subject, $parent);
            }

            $subject->update([
                'name' => (string) $data['name'],
                'parent_id' => $parent->id,
                'weight' => (int) $data['weight'] / 100,
            ]);

            if ($moved) {
                $this->joinPlansOf($subject, $parent);
            }
        });
    }

    /**
     * @throws ValidationException
     */
    public function delete(AverageNode $subject): void
    {
        DB::transaction(function () use ($subject): void {
            $subject = $this->lockWithParent($subject);

            if ($subject->grades()->exists()) {
                throw ValidationException::withMessages(['subject' => self::USED_BY_GRADES]);
            }

            // The domain would become a leaf: a subject nobody asked for.
            if (! $this->hasSiblings($subject)) {
                throw ValidationException::withMessages(['subject' => self::LAST_OF_DOMAIN]);
            }

            $subject->delete();
        });
    }

    /** Whether the node is a leaf under another node, which is what makes it a subject. */
    public function isSubject(AverageNode $node): bool
    {
        return $node->parent_id !== null && $node->children()->doesntExist();
    }

    /**
     * @throws ValidationException
     */
    private function guardMove(AverageNode $subject, AverageNode $parent): void
    {
        if (! $this->hasSiblings($subject)) {
            throw ValidationException::withMessages([
                'parent_id' => 'Le domaine actuel n\'aurait plus aucune matière : ajoutez-en une autre avant de déplacer celle-ci.',
            ]);
        }

        // Grades stay on the subject: they must still belong to a plan that uses it.
        if ($subject->grades()->exists() && $this->planIds($parent) !== $this->planIds($subject)) {
            throw ValidationException::withMessages([
                'parent_id' => 'Cette matière a des notes : elle ne peut aller que dans un domaine des mêmes filières.',
            ]);
        }
    }

    /**
     * Locks the subject, then its current parent, so concurrent removals from the
     * same domain run one after the other and each sees the other's result.
     */
    private function lockWithParent(AverageNode $subject): AverageNode
    {
        $subject = AverageNode::query()->lockForUpdate()->findOrFail($subject->id);
        AverageNode::query()->lockForUpdate()->findOrFail($subject->parent_id);

        return $subject;
    }

    private function hasSiblings(AverageNode $subject): bool
    {
        return AverageNode::query()
            ->where('parent_id', $subject->parent_id)
            ->whereKeyNot($subject->id)
            ->exists();
    }

    private function joinPlansOf(AverageNode $subject, AverageNode $parent): void
    {
        $subject->formationPlans()->sync($parent->formationPlans()->pluck('formation_plans.id'));
    }

    /** @return array<int, mixed> */
    private function planIds(AverageNode $node): array
    {
        return $node->formationPlans()->pluck('formation_plans.id')->sort()->values()->all();
    }
}
