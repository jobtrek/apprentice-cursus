<?php

namespace App\Support;

use App\Models\AverageNode;
use App\Models\FormationPlan;
use App\Services\SubjectService;
use Illuminate\Support\Collection;

/**
 * Props of the subjects page: the leaves of the grade trees, filtered by search,
 * track and domain, with the options of the filters and of the "new subject" form.
 */
class SubjectList
{
    /**
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     */
    public static function for(array $query): array
    {
        $nodes = AverageNode::query()
            ->with('formationPlans:id,name')
            ->withCount(['children', 'grades'])
            ->orderBy('name')
            ->get()
            ->keyBy('id');

        $subjects = self::subjects($nodes);

        $search = trim((string) ($query['search'] ?? ''));
        $track = in_array($query['track'] ?? null, ['IT', 'EC'], true) ? $query['track'] : 'All';

        $ofTrack = $subjects->filter(fn (array $subject): bool => $track === 'All' || in_array($track, $subject['tracks'], true));

        // A domain of the other track is not offered: the filter falls back to "all".
        $domains = $ofTrack->pluck('parent')->unique()->sort(SORT_LOCALE_STRING)->values();
        $domain = $domains->contains($query['domain'] ?? null) ? $query['domain'] : 'all';

        return [
            'subjects' => $ofTrack
                ->filter(fn (array $subject): bool => ($search === '' || mb_stripos($subject['name'], $search) !== false)
                    && ($domain === 'all' || $subject['parent'] === $domain))
                ->values(),
            'total' => $subjects->count(),
            'filters' => ['search' => $search, 'track' => $track, 'domain' => $domain],
            'domains' => $domains
                ->map(fn (string $name): array => ['value' => $name, 'label' => $name])
                ->prepend(['value' => 'all', 'label' => 'Tous les domaines'])
                ->values(),
            'parents' => self::parents($nodes),
        ];
    }

    /**
     * @param  Collection<int, AverageNode>  $nodes
     * @return Collection<int, array{id: int, name: string, parent_id: int|null, parent: string, weight: int, tracks: array<int, string>, grades_count: int, delete_blocked: string|null, can_delete: bool}>
     */
    private static function subjects(Collection $nodes): Collection
    {
        return $nodes
            ->filter(fn (AverageNode $node): bool => $node->children_count === 0 && $node->parent_id !== null)
            ->map(function (AverageNode $node) use ($nodes): array {
                $blocked = match (true) {
                    $node->grades_count > 0 => SubjectService::USED_BY_GRADES,
                    $nodes[$node->parent_id]->children_count < 2 => SubjectService::LAST_OF_DOMAIN,
                    default => null,
                };

                return [
                    'id' => $node->id,
                    'name' => $node->name,
                    'parent_id' => $node->parent_id,
                    'parent' => $nodes[$node->parent_id]->name,
                    'weight' => (int) round((float) $node->weight * 100),
                    'tracks' => self::tracks($node),
                    'grades_count' => $node->grades_count,
                    'delete_blocked' => $blocked,
                    'can_delete' => $blocked === null,
                ];
            })
            ->values();
    }

    /**
     * Where a new subject can go: any node that already averages children.
     *
     * @param  Collection<int, AverageNode>  $nodes
     * @return Collection<int, array{value: string, label: string}>
     */
    private static function parents(Collection $nodes): Collection
    {
        return $nodes
            ->filter(fn (AverageNode $node): bool => $node->children_count > 0)
            ->map(fn (AverageNode $node): array => [
                'value' => (string) $node->id,
                'label' => $node->name.' ('.implode(', ', self::tracks($node)).')',
            ])
            ->values();
    }

    /** @return array<int, string> */
    private static function tracks(AverageNode $node): array
    {
        return $node->formationPlans
            ->map(fn (FormationPlan $plan): string => $plan->shortName())
            ->unique()
            ->values()
            ->all();
    }
}
