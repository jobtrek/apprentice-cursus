<?php

use App\Enums\Permission;
use App\Models\Apprenticeship;
use App\Models\EvaluationNode;
use App\Models\Grade;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;

function makeGrade(User $apprentice): Grade
{
    $node = EvaluationNode::query()->firstOrCreate(['name' => 'Test node', 'period_scope' => 'semester']);

    return Grade::query()->create([
        'user_id' => $apprentice->id,
        'evaluation_node_id' => $node->id,
        'value' => 5.0,
        'test_date' => '2026-01-15',
        'semester' => 1,
    ]);
}

function makeApprentice(?Apprenticeship $section = null, ?User $coach = null): User
{
    $apprentice = User::factory()->create();
    $apprentice->forceFill([
        'apprenticeship_id' => $section?->id,
        'coach_id' => $coach?->id,
    ])->save();

    return $apprentice;
}

function section(string $name): Apprenticeship
{
    return Apprenticeship::query()->create(['name' => $name]);
}

test('roles map to the expected permissions', function () {
    expect(Role::findByName('apprentice')->permissions->pluck('name')->sort()->values()->all())
        ->toBe(collect([
            Permission::GradesCreate,
            Permission::GradesViewOwn,
            Permission::PortfolioManageOwn,
        ])->map->value->sort()->values()->all());

    expect(Role::findByName('coach')->hasPermissionTo(Permission::CoachingAssignSelf->value))->toBeTrue()
        ->and(Role::findByName('trainer')->hasPermissionTo(Permission::CoachingAssignSelf->value))->toBeFalse();
});

test('a role change keeps the Spatie role in sync', function () {
    $user = User::factory()->create();
    expect($user->hasRole('apprentice'))->toBeTrue();

    $user->forceFill(['role' => 'coach'])->save();

    expect($user->fresh()->hasRole('coach'))->toBeTrue()
        ->and($user->fresh()->hasRole('apprentice'))->toBeFalse();
});

describe('apprentice', function () {
    test('can open own pages', function () {
        $apprentice = User::factory()->create();
        $grade = makeGrade($apprentice);

        $this->actingAs($apprentice)->get(route('grades.create'))->assertOk();
        $this->actingAs($apprentice)->get(route('grades.show', $grade))->assertOk();
        $this->actingAs($apprentice)->get(route('portfolio.index'))->assertOk();
    });

    test('cannot open the supervisor pages', function () {
        $apprentice = User::factory()->create();
        $grade = makeGrade($apprentice);

        $this->actingAs($apprentice)->get(route('apprentisdashboard'))->assertForbidden();
        $this->actingAs($apprentice)->get(route('apprentices.show', 1))->assertForbidden();
        $this->actingAs($apprentice)->get(route('apprentices.grades.show', [$apprentice, $grade]))->assertForbidden();
    });

    test('cannot view another apprentice\'s grade, even in the same section', function () {
        $section = section('IT');
        $me = makeApprentice($section);
        $other = makeApprentice($section);
        $otherGrade = makeGrade($other);

        $this->actingAs($me)->get(route('grades.show', $otherGrade))->assertForbidden();
        $this->actingAs($me)->get(route('apprentices.grades.show', [$other, $otherGrade]))->assertForbidden();
    });

    test('non-apprentices cannot create grades', function () {
        $this->actingAs(User::factory()->coach()->create())->get(route('grades.create'))->assertForbidden();
        $this->actingAs(User::factory()->trainer()->create())->get(route('grades.create'))->assertForbidden();
    });
});

describe('coach', function () {
    test('sees the supervisor pages and its own apprentice\'s grade', function () {
        $coach = User::factory()->coach()->create();
        $apprentice = makeApprentice(coach: $coach);
        $grade = makeGrade($apprentice);

        $this->actingAs($coach)->get(route('apprentisdashboard'))->assertOk();
        $this->actingAs($coach)->get(route('apprentices.show', $apprentice->id))->assertOk();
        $this->actingAs($coach)->get(route('grades.show', $grade))->assertOk();
        $this->actingAs($coach)->get(route('apprentices.grades.show', [$apprentice, $grade]))->assertOk();
    });

    test('cannot view a grade of an apprentice it does not coach', function () {
        $coach = User::factory()->coach()->create();
        $grade = makeGrade(makeApprentice(coach: User::factory()->coach()->create()));

        $this->actingAs($coach)->get(route('grades.show', $grade))->assertForbidden();
        $this->actingAs($coach)->get(route('apprentices.grades.show', [$grade->user_id, $grade]))->assertForbidden();
    });

    test('cannot use apprentice-only pages', function () {
        $coach = User::factory()->coach()->create();

        $this->actingAs($coach)->get(route('grades.create'))->assertForbidden();
        $this->actingAs($coach)->get(route('portfolio.index'))->assertForbidden();
    });
});

describe('trainer', function () {
    test('sees grades of apprentices in the same section only', function () {
        $it = section('IT');
        $ec = section('EC');
        $trainer = User::factory()->trainer()->create();
        $trainer->forceFill(['apprenticeship_id' => $it->id])->save();

        $itGrade = makeGrade(makeApprentice($it));
        $ecGrade = makeGrade(makeApprentice($ec));

        $this->actingAs($trainer)->get(route('apprentisdashboard'))->assertOk();
        $this->actingAs($trainer)->get(route('grades.show', $itGrade))->assertOk();
        $this->actingAs($trainer)->get(route('grades.show', $ecGrade))->assertForbidden();
    });

    test('cannot use apprentice-only pages', function () {
        $trainer = User::factory()->trainer()->create();

        $this->actingAs($trainer)->get(route('grades.create'))->assertForbidden();
        $this->actingAs($trainer)->get(route('portfolio.index'))->assertForbidden();
    });
});

test('guests are redirected to login', function () {
    $this->get(route('grades.create'))->assertRedirect(route('login'));
});

test('auth.can exposes permission booleans per role', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('home'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('auth.can.createGrade', true)
            ->where('auth.can.viewOwnGrades', true)
            ->where('auth.can.managePortfolio', true)
            ->where('auth.can.viewApprentices', false)
            ->where('auth.can.viewSupervisedGrades', false));

    $this->actingAs(User::factory()->coach()->create())
        ->get(route('home'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('auth.can.createGrade', false)
            ->where('auth.can.viewApprentices', true)
            ->where('auth.can.viewSupervisedGrades', true));
});
