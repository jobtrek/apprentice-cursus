<?php

use App\Models\Apprenticeship;
use App\Models\Comment;
use App\Models\Grade;
use App\Models\Project;
use App\Models\User;

/**
 * An unsaved grade/project owned by $owner: the policies only read the owner.
 */
function ownedBy(string $class, User $owner): Grade|Project
{
    $model = new $class;
    $model->user_id = $owner->id;
    $model->setRelation('user', $owner);

    return $model;
}

beforeEach(function () {
    $this->itApprentice = User::factory()->apprentice(Apprenticeship::IT)->create();
    $this->ecApprentice = User::factory()->apprentice(Apprenticeship::EC)->create();
    $this->itTrainer = User::factory()->trainer(Apprenticeship::IT)->create();
    $this->ecTrainer = User::factory()->trainer(Apprenticeship::EC)->create();
    $this->coach = User::factory()->coach()->create();
});

test('only apprentices submit grades and projects', function () {
    expect($this->itApprentice->can('create', Grade::class))->toBeTrue()
        ->and($this->itApprentice->can('create', Project::class))->toBeTrue()
        ->and($this->itTrainer->can('create', Grade::class))->toBeFalse()
        ->and($this->coach->can('create', Project::class))->toBeFalse();
});

test('a deactivated apprentice cannot submit grades', function () {
    $inactive = User::factory()->apprentice()->inactive()->create();

    expect($inactive->can('create', Grade::class))->toBeFalse();
});

test('grade visibility follows role scope', function (string $class) {
    $itModel = ownedBy($class, $this->itApprentice);

    expect($this->itApprentice->can('view', $itModel))->toBeTrue()
        ->and($this->ecApprentice->can('view', $itModel))->toBeFalse()
        ->and($this->itTrainer->can('view', $itModel))->toBeTrue()
        ->and($this->ecTrainer->can('view', $itModel))->toBeFalse()
        ->and($this->coach->can('view', $itModel))->toBeTrue();
})->with([Grade::class, Project::class]);

test('only the owning apprentice edits or deletes', function (string $class) {
    $itModel = ownedBy($class, $this->itApprentice);

    expect($this->itApprentice->can('update', $itModel))->toBeTrue()
        ->and($this->itApprentice->can('delete', $itModel))->toBeTrue()
        ->and($this->ecApprentice->can('update', $itModel))->toBeFalse()
        ->and($this->itTrainer->can('update', $itModel))->toBeFalse()
        ->and($this->coach->can('delete', $itModel))->toBeFalse();
})->with([Grade::class, Project::class]);

test('trainers of the section and coaches comment, apprentices do not', function () {
    $grade = ownedBy(Grade::class, $this->itApprentice);

    expect($this->itTrainer->can('create', [Comment::class, $grade]))->toBeTrue()
        ->and($this->coach->can('create', [Comment::class, $grade]))->toBeTrue()
        ->and($this->ecTrainer->can('create', [Comment::class, $grade]))->toBeFalse()
        ->and($this->itApprentice->can('create', [Comment::class, $grade]))->toBeFalse();
});

test('nobody comments on a deactivated apprentice', function () {
    $grade = ownedBy(Grade::class, User::factory()->apprentice()->inactive()->create());

    expect($this->coach->can('create', [Comment::class, $grade]))->toBeFalse();
});

test('only the author edits or deletes a comment', function () {
    $comment = new Comment;
    $comment->author_id = $this->itTrainer->id;

    expect($this->itTrainer->can('update', $comment))->toBeTrue()
        ->and($this->coach->can('delete', $comment))->toBeFalse();
});

test('dashboard and apprentice views follow role scope', function () {
    expect($this->itTrainer->can('viewAny', User::class))->toBeTrue()
        ->and($this->coach->can('viewAny', User::class))->toBeTrue()
        ->and($this->itApprentice->can('viewAny', User::class))->toBeFalse()
        ->and($this->itTrainer->can('view', $this->itApprentice))->toBeTrue()
        ->and($this->itTrainer->can('view', $this->ecApprentice))->toBeFalse()
        ->and($this->coach->can('view', $this->ecApprentice))->toBeTrue()
        ->and($this->itApprentice->can('view', $this->ecApprentice))->toBeFalse();
});

test('coaches assign themselves only to an apprentice without coach', function () {
    $otherCoach = User::factory()->coach()->create();

    expect($this->coach->can('assignCoach', $this->itApprentice))->toBeTrue()
        ->and($this->itTrainer->can('assignCoach', $this->itApprentice))->toBeFalse();

    $this->itApprentice->forceFill(['coach_id' => $otherCoach->id])->save();

    expect($this->coach->can('assignCoach', $this->itApprentice))->toBeFalse()
        ->and($this->coach->can('unassignCoach', $this->itApprentice))->toBeFalse()
        ->and($otherCoach->can('unassignCoach', $this->itApprentice))->toBeTrue();
});

test('only an EC apprentice declares their own MP status', function () {
    expect($this->ecApprentice->can('updateMpStatus', $this->ecApprentice))->toBeTrue()
        ->and($this->itApprentice->can('updateMpStatus', $this->itApprentice))->toBeFalse()
        ->and($this->coach->can('updateMpStatus', $this->ecApprentice))->toBeFalse();
});
