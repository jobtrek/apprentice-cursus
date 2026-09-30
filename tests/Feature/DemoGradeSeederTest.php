<?php

use App\Models\EvaluationNode;
use App\Models\Grade;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DemoApprenticeSeeder;
use Database\Seeders\DemoGradeSeeder;
use Inertia\Testing\AssertableInertia as Assert;

function seededUser(string $email): User
{
    return User::query()->where('email', $email)->firstOrFail();
}

describe('in the local environment', function () {
    beforeEach(function () {
        app()->detectEnvironment(fn () => 'local');

        $this->seed(DatabaseSeeder::class);
    });

    it('gives every demo and local apprentice grades on leaves of their own tree', function () {
        foreach (DemoGradeSeeder::emails() as $email) {
            $user = seededUser($email);
            $root = EvaluationNode::query()->findOrFail($user->apprenticeship->evaluation_node_id);
            $grades = $user->grades()->with('evaluationNode')->get();

            expect($grades)->toHaveCount(count(DemoGradeSeeder::GRADES));

            foreach ($grades as $grade) {
                expect($grade->evaluationNode->isLeaf())->toBeTrue();
                expect($root->hasDescendant($grade->evaluationNode))->toBeTrue();
            }
        }
    });

    it('does not add grades when seeded again', function () {
        $count = Grade::query()->count();

        expect($count)->toBeGreaterThan(0);

        $this->seed(DemoGradeSeeder::class);

        expect(Grade::query()->count())->toBe($count);
    });

    it('lets supervisors open a demo apprentice gradebook and grade', function (string $email) {
        $demo = seededUser(DemoApprenticeSeeder::email(1));
        $ids = $demo->grades()->orderBy('test_date')->orderBy('id')->pluck('id')->all();

        expect($ids)->toHaveCount(3);

        $this->actingAs(seededUser($email))
            ->get(route('apprentices.show', $demo))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('ApprenticeShow')
                ->has('grades', 3)
                ->where('grades.0.id', $ids[0])
                ->where('grades.1.id', $ids[1])
                ->where('grades.2.id', $ids[2]));

        $this->actingAs(seededUser($email))
            ->get(route('apprentices.grades.show', [$demo, $ids[0]]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('GradeDetails')
                ->where('grade.id', $ids[0])
                ->where('can.comment', true));
    })->with(['trainer@example.com', 'coach@example.com']);

    it('shows an apprentice their own grades', function () {
        $apprentice = seededUser('apprentice-it@example.com');
        $ids = $apprentice->grades()->orderBy('test_date')->orderBy('id')->pluck('id')->all();

        $this->actingAs($apprentice)
            ->get(route('grades.dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('GradesDashboard')
                ->has('grades', 3)
                ->where('grades.0.id', $ids[0])
                ->where('grades.1.id', $ids[1])
                ->where('grades.2.id', $ids[2]));

        $this->actingAs($apprentice)
            ->get(route('grades.show', $ids[0]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('GradeDetails')
                ->where('grade.id', $ids[0]));
    });
});

it('creates no grades outside the local environment', function () {
    expect(app()->environment('local'))->toBeFalse();

    $this->seed(DatabaseSeeder::class);

    expect(Grade::query()->count())->toBe(0);
});
