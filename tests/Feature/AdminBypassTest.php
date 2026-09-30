<?php

use App\Enums\AzureGroup;
use App\Enums\Permission;
use App\Enums\UserRole;
use App\Models\Grade;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;

/**
 * Every route the admin is exercised on, as name => [route name, parameters resolver].
 * The resolver receives a foreign apprentice and its grade.
 *
 * @return array<string, array{string, Closure(User, Grade): array<string, mixed>}>
 */
function adminRoutes(): array
{
    return [
        'grades.dashboard' => ['grades.dashboard', fn () => []],
        'grades.create' => ['grades.create', fn () => []],
        'portfolio.index' => ['portfolio.index', fn () => []],
        'apprentisdashboard' => ['apprentisdashboard', fn () => []],
        'apprentices.show' => ['apprentices.show', fn (User $apprentice) => ['apprentice' => $apprentice]],
        'apprentices.grades.show' => ['apprentices.grades.show', fn (User $apprentice, $grade) => ['apprentice' => $apprentice, 'grade' => $grade]],
        'grades.show' => ['grades.show', fn (User $apprentice, $grade) => ['grade' => $grade]],
    ];
}

describe('in local', function () {
    beforeEach(function () {
        app()->detectEnvironment(fn () => 'local');
    });

    test('an admin opens every page, including other users\' data', function () {
        $admin = User::factory()->admin()->create();
        $apprentice = makeApprentice();
        $grade = makeGrade($apprentice);

        foreach (adminRoutes() as [$name, $params]) {
            $this->actingAs($admin)->get(route($name, $params($apprentice, $grade)))
                ->assertOk();
        }
    });

    test('an admin can do everything and sees every Inertia flag', function () {
        $admin = User::factory()->admin()->create();

        foreach (Permission::cases() as $permission) {
            expect($admin->can($permission->value))->toBeTrue();
        }

        $this->actingAs($admin)->get(route('apprentisdashboard'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('auth.can.createGrade', true)
                ->where('auth.can.viewOwnGrades', true)
                ->where('auth.can.viewSupervisedGrades', true)
                ->where('auth.can.managePortfolio', true)
                ->where('auth.can.viewApprentices', true));
    });

    test('an admin supervises every apprentice but not itself or non-apprentices', function () {
        $admin = User::factory()->admin()->create();

        expect($admin->isLocalAdmin())->toBeTrue()
            ->and($admin->supervises(makeApprentice(section('IT'))))->toBeTrue()
            ->and($admin->supervises(makeApprentice(section('EC'))))->toBeTrue()
            ->and($admin->supervises(makeApprentice()))->toBeTrue()
            ->and($admin->supervises(User::factory()->coach()->create()))->toBeFalse()
            ->and($admin->supervises($admin))->toBeFalse();
    });

    test('an admin lands on the apprentice list', function () {
        expect(User::factory()->admin()->create()->homeRoute())->toBe('apprentisdashboard');
    });
});

describe('outside local', function () {
    beforeEach(function () {
        expect(app()->environment('local'))->toBeFalse();
    });

    test('an admin is forbidden everywhere', function () {
        $admin = User::factory()->admin()->create();
        $apprentice = makeApprentice();
        $grade = makeGrade($apprentice);

        foreach (adminRoutes() as [$name, $params]) {
            $this->actingAs($admin)->get(route($name, $params($apprentice, $grade)))
                ->assertForbidden();
        }
    });

    test('an admin has no abilities', function () {
        $admin = User::factory()->admin()->create();

        foreach (Permission::cases() as $permission) {
            expect($admin->can($permission->value))->toBeFalse();
        }

        expect($admin->isLocalAdmin())->toBeFalse()
            ->and($admin->supervises(makeApprentice(section('IT'))))->toBeFalse()
            ->and($admin->homeRoute())->toBe('home');
    });
});

test('no Azure group maps to the admin role', function () {
    expect(collect(AzureGroup::cases())->map->role()->all())->not->toContain(UserRole::Admin);
});

test('the migrated database has an admin role without permissions', function () {
    expect(Role::findByName('admin')->permissions)->toBeEmpty();
});
