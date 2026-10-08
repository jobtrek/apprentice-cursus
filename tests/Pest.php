<?php

use App\Models\Apprenticeship;
use App\Models\ApprenticeshipContext;
use App\Models\ApprenticeshipPeriod;
use App\Models\Domain;
use App\Models\Grade;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function makeGrade(User $apprentice, ?Domain $domain = null): Grade
{
    $domain ??= Domain::query()->firstOrCreate(['name' => 'Test node']);
    $subject = Subject::query()->firstOrCreate(['name' => 'Test subject']);
    // A grade is only accepted on a subject attached to its domain.
    $domain->subjects()->syncWithoutDetaching([$subject->id]);

    $period = ApprenticeshipPeriod::query()->firstOrCreate(
        ['user_id' => $apprentice->id, 'semester' => 2],
        ['start_date' => '2026-01-01', 'end_date' => '2026-07-31'],
    );

    return Grade::query()->create([
        'user_id' => $apprentice->id,
        'domain_id' => $domain->id,
        'subject_id' => $subject->id,
        'apprenticeship_period_id' => $period->id,
        'value' => 5.0,
        'test_date' => '2026-01-15',
    ]);
}

function makeApprentice(?Apprenticeship $section = null, ?User $coach = null, ?User $trainer = null): User
{
    $apprentice = User::factory()->create();
    $apprentice->forceFill([
        'apprenticeship_context_id' => $section === null ? null : contextFor($section)->id,
        'coach_id' => $coach?->id,
        'trainer_id' => $trainer?->id,
    ])->save();

    return $apprentice;
}

/**
 * The context of a section, created when missing. Contexts of one section
 * share their root domain, like the seeded trees.
 */
function contextFor(Apprenticeship $section, bool $mp = false): ApprenticeshipContext
{
    return ApprenticeshipContext::query()->firstOrCreate(
        ['apprenticeship_id' => $section->id, 'is_mp' => $mp],
        fn (): array => [
            'root_domain_id' => $section->contexts()->value('root_domain_id')
                ?? Domain::query()->create(['name' => "Root {$section->name}"])->id,
        ],
    );
}

function section(string $name): Apprenticeship
{
    return Apprenticeship::query()->create(['name' => $name]);
}
