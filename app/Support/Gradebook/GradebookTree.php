<?php

namespace App\Support\Gradebook;

use App\Models\Domain;
use App\Models\DomainLinkWeight;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * The apprentice's grade tree, as the gradebook pages need it to compute the
 * CFC averages: every domain reachable from the apprentice's context root,
 * with only the links weighted for that context.
 */
final class GradebookTree
{
    /**
     * @return array{root: int, nodes: array<int, array{id: int, name: string, aggregated: bool, rounding_step: float|null, period_scope: string, children: list<array{id: int, weight: float}>}>}|null
     */
    public static function for(User $user): ?array
    {
        $context = $user->apprenticeshipContext;
        $rootId = $context?->root_domain_id;

        if ($context === null || $rootId === null) {
            return null;
        }

        /** @var Collection<int, object{id: int, parent_id: int, child_id: int}> $links */
        $links = collect(DB::select(<<<'SQL'
            WITH RECURSIVE reachable AS (
                SELECT id, parent_id, child_id
                FROM domain_links
                WHERE parent_id = ?

                UNION

                SELECT dl.id, dl.parent_id, dl.child_id
                FROM domain_links dl
                JOIN reachable r ON r.child_id = dl.parent_id
            )
            SELECT id, parent_id, child_id
            FROM reachable
            ORDER BY id
        SQL, [$rootId]));

        $weights = DomainLinkWeight::query()
            ->where('apprenticeship_context_id', $context->id)
            ->whereIn('domain_link_id', $links->pluck('id'))
            ->get()
            ->keyBy('domain_link_id');

        $weightedLinks = $links->filter(fn (object $link): bool => $weights->has($link->id));
        $domainIds = [$rootId, ...$weightedLinks->pluck('child_id')->all()];
        $domains = Domain::query()->whereIn('id', $domainIds)->get()->keyBy('id');
        $childrenByParent = $weightedLinks->groupBy('parent_id');
        $nodes = [];

        foreach ($domains as $domain) {
            $children = [];

            foreach ($childrenByParent->get($domain->id, []) as $link) {
                $children[] = [
                    'id' => (int) $link->child_id,
                    'weight' => (float) $weights->get($link->id)->weight,
                ];
            }

            $nodes[$domain->id] = [
                'id' => $domain->id,
                'name' => $domain->name,
                'aggregated' => $children !== [],
                'rounding_step' => $domain->rounding_step === null ? null : (float) $domain->rounding_step,
                'period_scope' => (float) $domain->rounding_step === 0.5 ? 'semester' : 'cursus',
                'children' => $children,
            ];
        }

        return ['root' => $rootId, 'nodes' => $nodes];
    }
}
