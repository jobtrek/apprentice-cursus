<?php

use App\Models\Apprenticeship;
use App\Models\Domain;
use App\Models\DomainLink;
use App\Models\Subject;
use Database\Seeders\ApprenticeshipSeeder;
use Database\Seeders\EvaluationTreeSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

function seedReferential(): void
{
    test()->seed([ApprenticeshipSeeder::class, EvaluationTreeSeeder::class]);
}

function rootOf(string $apprenticeship, bool $isMp = false): Domain
{
    return Apprenticeship::query()
        ->where('name', $apprenticeship)
        ->sole()
        ->contexts()
        ->where('is_mp', $isMp)
        ->sole()
        ->rootDomain;
}

function childNamed(Domain $domain, string $name): Domain
{
    return $domain->children()->where('name', $name)->sole();
}

function weightsFor(Domain $parent, int $contextId): array
{
    return DB::table('domain_link_weights')
        ->join('domain_links', 'domain_links.id', '=', 'domain_link_weights.domain_link_id')
        ->join('domains', 'domains.id', '=', 'domain_links.child_id')
        ->where('domain_link_weights.apprenticeship_context_id', $contextId)
        ->where('domain_links.parent_id', $parent->id)
        ->orderBy('domains.name')
        ->pluck('domain_link_weights.weight', 'domains.name')
        ->map(static fn (string $weight): float => (float) $weight)
        ->all();
}

test('each apprenticeship has a complete weighted context tree', function () {
    seedReferential();

    expect(weightsFor(
        rootOf(ApprenticeshipSeeder::IT),
        rootOf(ApprenticeshipSeeder::IT)->apprenticeshipContexts()->sole()->id,
    ))->toEqual([
        'Compétences de base élargies' => 0.1,
        'Compétences en informatique' => 0.3,
        'Culture générale' => 0.2,
        'Travail pratique individuel (TPI)' => 0.4,
    ]);

    expect(weightsFor(
        rootOf(ApprenticeshipSeeder::EC),
        rootOf(ApprenticeshipSeeder::EC)->apprenticeshipContexts()->where('is_mp', false)->sole()->id,
    ))->toEqual([
        'Connaissances professionnelles et culture générale' => 0.3,
        'Note d\'expérience' => 0.4,
        'Travail pratique' => 0.3,
    ]);
});

test('the IT modules come from modules.json, grouped by school', function () {
    seedReferential();

    $modules = collect(File::json(resource_path('js/data/modules.json')))->countBy('school');
    $itSkills = childNamed(rootOf(ApprenticeshipSeeder::IT), 'Compétences en informatique');

    expect($itSkills->children()->where('name', 'Modules école professionnelle')->sole()->children()->count())
        ->toBe($modules['EPSIC']);
    expect($itSkills->children()->where('name', 'Modules cours interentreprises')->sole()->children()->count())
        ->toBe($modules['CIE']);
});

test('EC contexts use different weights and share their leaves', function () {
    seedReferential();

    $standard = Apprenticeship::query()->where('name', ApprenticeshipSeeder::EC)->sole()->contexts()->where('is_mp', false)->sole();
    $mp = Apprenticeship::query()->where('name', ApprenticeshipSeeder::EC)->sole()->contexts()->where('is_mp', true)->sole();
    $experience = childNamed($standard->rootDomain, 'Note d\'expérience');

    expect(weightsFor($experience, $standard->id))->toEqual([
        'Cours interentreprises' => 0.25,
        'Enseignement des connaissances professionnelles et de la culture générale' => 0.5,
        'Formation à la pratique professionnelle' => 0.25,
    ]);
    expect(weightsFor($experience, $mp->id))->toEqual([
        'Cours interentreprises' => 0.5,
        'Formation à la pratique professionnelle' => 0.5,
    ]);
    expect(childNamed($experience, 'Cours interentreprises')->id)
        ->toBe(childNamed($mp->rootDomain->children()->where('name', 'Note d\'expérience')->sole(), 'Cours interentreprises')->id);
});

test('weights under every context parent sum to one and leaves have subjects', function () {
    seedReferential();

    $contexts = DB::table('apprenticeship_contexts')->get();
    foreach ($contexts as $context) {
        $sums = DB::table('domain_link_weights')
            ->join('domain_links', 'domain_links.id', '=', 'domain_link_weights.domain_link_id')
            ->where('apprenticeship_context_id', $context->id)
            ->select('domain_links.parent_id', DB::raw('sum(weight) as sum_weight'))
            ->groupBy('domain_links.parent_id')
            ->pluck('sum_weight', 'domain_links.parent_id');

        foreach ($sums as $sum) {
            expect((float) $sum)->toBe(1.0);
        }
    }

    Domain::query()->each(function (Domain $domain) {
        expect($domain->subjects()->exists())->toBe($domain->children()->doesntExist(), $domain->name);
    });
});

test('seeding twice changes nothing', function () {
    seedReferential();

    $count = fn (): array => [
        Domain::query()->count(),
        DomainLink::query()->count(),
        DB::table('domain_link_weights')->count(),
        Subject::query()->count(),
        DB::table('apprenticeship_contexts')->count(),
    ];
    $before = $count();

    seedReferential();

    expect($count())->toBe($before);
});

test('a domain with only the root name is not mistaken for the tree', function () {
    $decoy = Domain::query()->create(['name' => EvaluationTreeSeeder::IT_ROOT]);

    seedReferential();

    expect(rootOf(ApprenticeshipSeeder::IT)->id)->not->toBe($decoy->id);
});

test('linkChild rejects transitive cycles', function () {
    $a = Domain::query()->create(['name' => 'A']);
    $b = Domain::query()->create(['name' => 'B']);
    $c = Domain::query()->create(['name' => 'C']);

    $a->linkChild($b);
    $b->linkChild($c);

    expect(fn () => $c->linkChild($a))
        ->toThrow(LogicException::class);
});
