<?php

namespace App\Http\Controllers;

use App\Http\Requests\SubjectRequest;
use App\Models\AverageNode;
use App\Models\FormationPlan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class SubjectController extends Controller
{
    public function index(Request $request): Response
    {
        $nodes = AverageNode::query()
            ->with('formationPlans:id,name')
            ->withCount(['children', 'grades'])
            ->orderBy('name')
            ->get();

        $names = $nodes->pluck('name', 'id');

        $track = fn (AverageNode $node): array => $node->formationPlans
            ->map(fn (FormationPlan $plan): string => $plan->shortName())
            ->unique()
            ->values()
            ->all();

        $subjects = $nodes
            ->filter(fn (AverageNode $node): bool => $node->children_count === 0 && $node->parent_id !== null)
            ->map(fn (AverageNode $node): array => [
                'id' => $node->id,
                'name' => $node->name,
                'parent_id' => $node->parent_id,
                'parent' => $names[$node->parent_id],
                'weight' => (int) round((float) $node->weight * 100),
                'tracks' => $track($node),
                'grades_count' => $node->grades_count,
                'can_delete' => $node->grades_count === 0,
            ])
            ->values();

        $search = trim((string) $request->query('search', ''));
        $trackFilter = in_array($request->query('track'), ['IT', 'EC'], true) ? $request->query('track') : 'All';

        $bytrack = $subjects->filter(fn (array $subject): bool => $trackFilter === 'All' || in_array($trackFilter, $subject['tracks'], true));

        // A domain of the other track is not offered: the filter falls back to "all".
        $domains = $bytrack->pluck('parent')->unique()->sort(SORT_LOCALE_STRING)->values();
        $domain = $domains->contains($request->query('domain')) ? $request->query('domain') : 'all';

        $results = $bytrack->filter(fn (array $subject): bool => ($search === '' || mb_stripos($subject['name'], $search) !== false)
            && ($domain === 'all' || $subject['parent'] === $domain));

        return Inertia::render('Subjects', [
            'subjects' => $results->values(),
            'total' => $subjects->count(),
            'filters' => ['search' => $search, 'track' => $trackFilter, 'domain' => $domain],
            'domains' => $domains
                ->map(fn (string $name): array => ['value' => $name, 'label' => $name])
                ->prepend(['value' => 'all', 'label' => 'Tous les domaines'])
                ->values(),
            'parents' => $nodes
                ->filter(fn (AverageNode $node): bool => $node->children_count > 0)
                ->map(fn (AverageNode $node): array => [
                    'value' => (string) $node->id,
                    'label' => $node->name.' ('.implode(', ', $track($node)).')',
                ])
                ->values(),
        ]);
    }

    public function store(SubjectRequest $request): RedirectResponse
    {
        $data = $request->validated();

        DB::transaction(function () use ($data): void {
            $parent = AverageNode::query()->whereKey($data['parent_id'])->firstOrFail();

            $subject = AverageNode::query()->create([
                'parent_id' => $parent->id,
                'name' => $data['name'],
                'weight' => $data['weight'] / 100,
            ]);

            $subject->formationPlans()->sync($parent->formationPlans()->pluck('formation_plans.id'));
        });

        return redirect()->route('subjects.index');
    }

    public function update(SubjectRequest $request, AverageNode $subject): RedirectResponse
    {
        abort_unless($this->isSubject($subject), 404);

        $data = $request->validated();
        $parent = AverageNode::query()->whereKey($data['parent_id'])->firstOrFail();
        $moved = $parent->id !== $subject->parent_id;

        if ($moved) {
            if (! $subject->parent->children()->whereKeyNot($subject->id)->exists()) {
                return back()->withErrors(['parent_id' => 'Le domaine actuel n\'aurait plus aucune matière : ajoutez-en une autre avant de déplacer celle-ci.']);
            }

            // Grades stay on the subject: they must still belong to a plan that uses it.
            $plans = fn (AverageNode $node): array => $node->formationPlans()->pluck('formation_plans.id')->sort()->values()->all();

            if ($subject->grades()->exists() && $plans($parent) !== $plans($subject)) {
                return back()->withErrors(['parent_id' => 'Cette matière a des notes : elle ne peut aller que dans un domaine des mêmes filières.']);
            }
        }

        DB::transaction(function () use ($subject, $parent, $data, $moved): void {
            $subject->update([
                'name' => $data['name'],
                'parent_id' => $parent->id,
                'weight' => $data['weight'] / 100,
            ]);

            if ($moved) {
                $subject->formationPlans()->sync($parent->formationPlans()->pluck('formation_plans.id'));
            }
        });

        return redirect()->route('subjects.index');
    }

    public function destroy(AverageNode $subject): RedirectResponse
    {
        abort_unless($this->isSubject($subject), 404);

        if ($subject->grades()->exists()) {
            return back()->withErrors(['subject' => 'Cette matière est utilisée par au moins une note : elle ne peut pas être supprimée.']);
        }

        // The domain would become a leaf: a subject nobody asked for.
        if (! $subject->parent->children()->whereKeyNot($subject->id)->exists()) {
            return back()->withErrors(['subject' => 'C\'est la dernière matière de son domaine : elle ne peut pas être supprimée.']);
        }

        $subject->delete();

        return redirect()->route('subjects.index');
    }

    private function isSubject(AverageNode $node): bool
    {
        return $node->parent_id !== null && $node->children()->doesntExist();
    }
}
