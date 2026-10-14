<?php

use App\Models\AverageNode;
use App\Models\FormationPlan;
use App\Models\Grade;
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

function makeGrade(User $apprentice): Grade
{
    $node = AverageNode::query()->firstOrCreate(['name' => 'Test node']);

    return Grade::query()->create([
        'user_id' => $apprentice->id,
        'average_node_id' => $node->id,
        'value' => 5.0,
        'test_date' => '2026-01-15',
    ]);
}

function makeApprentice(?FormationPlan $section = null, ?User $coach = null): User
{
    $apprentice = User::factory()->create();
    $apprentice->forceFill([
        'formation_plan_id' => $section?->id,
        'coach_id' => $coach?->id,
    ])->save();

    return $apprentice;
}

/** A trainer of the given plans: it follows every apprentice of them. */
function makeTrainer(FormationPlan ...$plans): User
{
    $trainer = User::factory()->trainer()->create();
    $trainer->trainedFormationPlans()->attach(array_map(fn (FormationPlan $plan): int => $plan->id, $plans));

    return $trainer;
}

function section(string $name): FormationPlan
{
    return FormationPlan::query()->create(['name' => $name]);
}
