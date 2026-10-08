<?php

use App\Enums\Permission;
use App\Enums\UserRole;
use App\Models\Comment;
use App\Models\Grade;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;

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

test('the role accessor derives from the single Spatie role', function () {
    $user = User::factory()->create();
    expect($user->role)->toBe(UserRole::Apprentice);

    $user->syncRoles(UserRole::Coach->value);

    expect($user->role)->toBe(UserRole::Coach)
        ->and($user->fresh()->hasRole('coach'))->toBeTrue()
        ->and($user->fresh()->hasRole('apprentice'))->toBeFalse()
        ->and(User::role('coach')->pluck('id')->all())->toBe([$user->id]);
});

test('factory states leave exactly one role', function () {
    expect(User::factory()->coach()->create()->roles->pluck('name')->all())->toBe(['coach'])
        ->and(User::factory()->trainer()->create()->roles->pluck('name')->all())->toBe(['trainer'])
        ->and(User::factory()->make()->role)->toBeNull();
});

test('a migrated database without seeders has roles and permissions', function () {
    expect(Role::findByName('coach')->hasPermissionTo(Permission::GradesComment->value))->toBeTrue();

    $this->actingAs(User::factory()->coach()->create())
        ->get(route('apprentisdashboard'))
        ->assertOk();

    $coach = User::factory()->coach()->create();
    $grade = makeGrade(makeApprentice(coach: $coach));

    $this->actingAs($coach)->get(route('grades.show', $grade))->assertOk();
    $this->actingAs(User::factory()->coach()->create())->get(route('grades.show', $grade))->assertForbidden();
});

describe('apprentice', function () {
    test('can open own pages', function () {
        $apprentice = User::factory()->create();
        $grade = makeGrade($apprentice);

        $this->actingAs($apprentice)->get(route('grades.dashboard'))->assertOk();
        $this->actingAs($apprentice)->get(route('grades.show', $grade))->assertOk();
        $this->actingAs($apprentice)->get(route('portfolio.index'))->assertOk();
    });

    test('cannot open the supervisor pages', function () {
        $apprentice = User::factory()->create();
        $grade = makeGrade($apprentice);

        $this->actingAs($apprentice)->get(route('apprentisdashboard'))->assertForbidden();
        $this->actingAs($apprentice)->get(route('apprentices.show', $apprentice))->assertForbidden();
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

        $this->actingAs($coach)->get(route('grades.dashboard'))->assertForbidden();
        $this->actingAs($coach)->get(route('portfolio.index'))->assertForbidden();
    });
});

describe('trainer', function () {
    test('sees grades of its own apprentices only', function () {
        $it = section('IT');
        $ec = section('EC');
        $trainer = User::factory()->trainer()->create();
        $trainer->forceFill(['apprenticeship_context_id' => contextFor($it)->id])->save();

        $ownGrade = makeGrade(makeApprentice($it, trainer: $trainer));
        $unassignedGrade = makeGrade(makeApprentice($it));
        $ecGrade = makeGrade(makeApprentice($ec, trainer: $trainer));

        $this->actingAs($trainer)->get(route('apprentisdashboard'))->assertOk();
        $this->actingAs($trainer)->get(route('grades.show', $ownGrade))->assertOk();
        $this->actingAs($trainer)->get(route('grades.show', $unassignedGrade))->assertForbidden();
        // Even assigned, an apprentice of another section stays out of reach.
        $this->actingAs($trainer)->get(route('grades.show', $ecGrade))->assertForbidden();
    });

    test('cannot use apprentice-only pages', function () {
        $trainer = User::factory()->trainer()->create();

        $this->actingAs($trainer)->get(route('grades.dashboard'))->assertForbidden();
        $this->actingAs($trainer)->get(route('portfolio.index'))->assertForbidden();
    });
});

test('guests are redirected to login', function () {
    $this->get(route('grades.dashboard'))->assertRedirect(route('login'));
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

describe('apprentice pages', function () {
    test('a coach cannot open the page of an apprentice it does not coach', function () {
        $coach = User::factory()->coach()->create();
        $other = makeApprentice(coach: User::factory()->coach()->create());

        $this->actingAs($coach)->get(route('apprentices.show', $other))->assertForbidden();
    });

    test('the grade must belong to the apprentice in the URL', function () {
        $coach = User::factory()->coach()->create();
        $mine = makeApprentice(coach: $coach);
        $alsoMine = makeApprentice(coach: $coach);
        $grade = makeGrade($alsoMine);

        $this->actingAs($coach)->get(route('apprentices.grades.show', [$mine, $grade]))->assertNotFound();
    });

    test('a trainer cannot open an apprentice of another section', function () {
        $it = section('IT');
        $trainer = User::factory()->trainer()->create();
        $trainer->forceFill(['apprenticeship_context_id' => contextFor($it)->id])->save();
        $ec = makeApprentice(section('EC'));

        $this->actingAs($trainer)->get(route('apprentices.show', $ec))->assertForbidden();
        $this->actingAs($trainer)->get(route('apprentices.show', makeApprentice($it, trainer: $trainer)))->assertOk();
    });
});

test('the grades dashboard requires the own-grades permission', function () {
    $this->actingAs(User::factory()->create())->get(route('grades.dashboard'))->assertOk();
    $this->actingAs(User::factory()->coach()->create())->get(route('grades.dashboard'))->assertForbidden();
});

test('a supervisor can load a followed apprentice\'s portfolio screenshot', function () {
    Storage::fake();
    $coach = User::factory()->coach()->create();
    $apprentice = makeApprentice(coach: $coach);
    $project = Project::factory()->create(['user_id' => $apprentice->id]);
    Storage::put('projects/x/a.png', 'img');
    $screenshot = $project->screenshots()->create(['path' => 'projects/x/a.png']);

    $this->actingAs($coach)->get(route('portfolio.screenshots.show', $screenshot))->assertOk();
    $this->actingAs(User::factory()->coach()->create())
        ->get(route('portfolio.screenshots.show', $screenshot))->assertForbidden();
});

test('deactivated users are logged out on the next request', function () {
    $user = User::factory()->create();
    $this->actingAs($user)->get(route('home'))->assertOk();

    $user->forceFill(['is_active' => false])->save();

    $this->get(route('home'))->assertRedirect(route('login'));
    $this->assertGuest();
});

test('deactivated users cannot create or change projects', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create(['user_id' => $user->id]);
    $user->forceFill(['is_active' => false])->save();

    expect($user->fresh()->can('create', Project::class))->toBeFalse()
        ->and($user->fresh()->can('update', $project))->toBeFalse()
        ->and($user->fresh()->can('delete', $project))->toBeFalse();
});

test('auth.user only exposes explicit fields', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('home'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('auth.user', fn (Assert $user) => $user
                ->hasAll(['id', 'name', 'email', 'role', 'apprenticeship_id'])));
});

describe('supervision scoping', function () {
    test('a trainer cannot open the page of another trainer or of itself', function () {
        $it = section('IT');
        $trainer = User::factory()->trainer()->create();
        $trainer->forceFill(['apprenticeship_context_id' => contextFor($it)->id])->save();
        $otherTrainer = User::factory()->trainer()->create();
        $otherTrainer->forceFill(['apprenticeship_context_id' => contextFor($it)->id])->save();

        $this->actingAs($trainer)->get(route('apprentices.show', $otherTrainer))->assertForbidden();
        $this->actingAs($trainer)->get(route('apprentices.show', $trainer))->assertForbidden();
    });

    test('a coach does not supervise a non-apprentice it has coach_id on', function () {
        $coach = User::factory()->coach()->create();
        $trainer = User::factory()->trainer()->create();
        $trainer->forceFill(['coach_id' => $coach->id])->save();

        expect($coach->supervises($trainer))->toBeFalse();
        $this->actingAs($coach)->get(route('apprentices.show', $trainer))->assertForbidden();
    });
});

describe('commenting', function () {
    test('grades of a deactivated apprentice cannot be commented', function () {
        $coach = User::factory()->coach()->create();
        $apprentice = makeApprentice(coach: $coach);
        $grade = makeGrade($apprentice);

        expect($coach->can('comment', $grade))->toBeTrue();

        $apprentice->forceFill(['is_active' => false])->save();

        expect($coach->fresh()->can('comment', $grade->fresh()))->toBeFalse();
    });

    test('a coach can comment only on the grades of its own apprentices', function () {
        $coach = User::factory()->coach()->create();
        $mine = makeGrade(makeApprentice(coach: $coach));
        $notMine = makeGrade(makeApprentice(coach: User::factory()->coach()->create()));
        $unassigned = makeGrade(makeApprentice());

        expect($coach->can('comment', $mine))->toBeTrue()
            ->and($coach->can('comment', $notMine))->toBeFalse()
            ->and($coach->can('comment', $unassigned))->toBeFalse();
    });

    test('a trainer can view and comment only its own apprentices of its section, never a trainer', function () {
        $it = section('IT');
        $ec = section('EC');
        $trainer = User::factory()->trainer()->create();
        $trainer->forceFill(['apprenticeship_context_id' => contextFor($it)->id])->save();

        $itGrade = makeGrade(makeApprentice($it, trainer: $trainer));
        $ecGrade = makeGrade(makeApprentice($ec, trainer: $trainer));

        expect($trainer->can('view', $itGrade))->toBeTrue()
            ->and($trainer->can('comment', $itGrade))->toBeTrue()
            ->and($trainer->can('view', $ecGrade))->toBeFalse()
            ->and($trainer->can('comment', $ecGrade))->toBeFalse();

        $otherTrainer = User::factory()->trainer()->create();
        $otherTrainer->forceFill(['apprenticeship_context_id' => contextFor($it)->id])->save();

        expect($trainer->supervises($otherTrainer))->toBeFalse()
            ->and($trainer->supervises($trainer))->toBeFalse();
    });

    function makeComment(Grade $grade, User $author): Comment
    {
        $comment = new Comment(['body' => 'Well done']);
        $comment->author()->associate($author);
        $grade->comments()->save($comment);

        return $comment;
    }

    test('only the author can update or delete a comment', function () {
        $coach = User::factory()->coach()->create();
        $apprentice = makeApprentice(coach: $coach);
        $comment = makeComment(makeGrade($apprentice), $coach);
        $other = User::factory()->coach()->create();

        expect($coach->can('update', $comment))->toBeTrue()
            ->and($coach->can('delete', $comment))->toBeTrue()
            ->and($other->can('update', $comment))->toBeFalse()
            ->and($other->can('delete', $comment))->toBeFalse();
    });

    test('an author who lost supervision of the apprentice cannot change their comment', function () {
        $coach = User::factory()->coach()->create();
        $apprentice = makeApprentice(coach: $coach);
        $comment = makeComment(makeGrade($apprentice), $coach);

        expect($coach->can('update', $comment))->toBeTrue();

        $apprentice->forceFill(['coach_id' => User::factory()->coach()->create()->id])->save();
        $comment = Comment::query()->findOrFail($comment->id);

        expect($coach->fresh()->can('update', $comment))->toBeFalse()
            ->and($coach->fresh()->can('delete', $comment))->toBeFalse();
    });

    test('comments on a deactivated apprentice cannot be changed', function () {
        $coach = User::factory()->coach()->create();
        $apprentice = makeApprentice(coach: $coach);
        $comment = makeComment(makeGrade($apprentice), $coach);

        $apprentice->forceFill(['is_active' => false])->save();
        $comment = Comment::query()->findOrFail($comment->id);

        expect($coach->can('update', $comment))->toBeFalse()
            ->and($coach->can('delete', $comment))->toBeFalse();
    });
});

describe('routing and landing', function () {
    test('homeRoute is chosen by permission', function () {
        $noRole = User::factory()->create();
        $noRole->syncRoles([]);

        expect(User::factory()->create()->homeRoute())->toBe('grades.dashboard')
            ->and(User::factory()->coach()->create()->homeRoute())->toBe('apprentisdashboard')
            ->and(User::factory()->trainer()->create()->homeRoute())->toBe('apprentisdashboard')
            ->and($noRole->fresh()->homeRoute())->toBe('home');
    });

    test('grade pages expose the server-side comment decision', function () {
        $coach = User::factory()->coach()->create();
        $apprentice = makeApprentice(coach: $coach);
        $grade = makeGrade($apprentice);

        $this->actingAs($coach)->get(route('grades.show', $grade))
            ->assertInertia(fn (Assert $page) => $page->where('can.comment', true));
        $this->actingAs($apprentice)->get(route('grades.show', $grade))
            ->assertInertia(fn (Assert $page) => $page->where('can.comment', false));
    });

    test('non-numeric portfolio ids are not routable', function () {
        $this->actingAs(User::factory()->create());

        $this->get('/portfolio/projects/abc/edit')->assertNotFound();
        $this->get('/portfolio/screenshots/abc')->assertNotFound();
    });
});

describe('demo data', function () {
    test('grade pages carry no demo PDF outside local', function () {
        $apprentice = makeApprentice();
        $grade = makeGrade($apprentice);

        expect(app()->environment('local'))->toBeFalse();

        $this->actingAs($apprentice)->get(route('grades.show', $grade))
            ->assertInertia(fn (Assert $page) => $page
                ->component('GradeDetails')
                ->where('pdfUrl', null)
                ->where('comments', []));
    });

    test('grade pages carry the demo PDF in local', function () {
        app()->detectEnvironment(fn () => 'local');

        $apprentice = makeApprentice();
        $grade = makeGrade($apprentice);

        $this->actingAs($apprentice)->get(route('grades.show', $grade))
            ->assertInertia(fn (Assert $page) => $page
                ->component('GradeDetails')
                ->where('pdfUrl', '/demo/sample-grade-test.pdf')
                ->where('comments', []));
    });
});
