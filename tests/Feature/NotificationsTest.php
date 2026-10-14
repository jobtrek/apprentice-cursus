<?php

use App\Models\User;
use App\Notifications\GradeAddedNotification;
use App\Notifications\GradeDeletedNotification;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected to the login page', function () {
    $this->get(route('notifications.index'))->assertRedirect(route('login'));
});

test('authenticated users can open the notifications inbox', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('notifications.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Notifications')
            ->where('selected', null));
});

test('the notification to open is taken from the query string', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('notifications.index', ['notification' => 'abc']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('selected', 'abc'));
});

test('the notifications and the unread count are shared with every page', function () {
    $coach = User::factory()->coach()->create();
    $apprentice = makeApprentice(coach: $coach);
    $coach->notify(new GradeAddedNotification(makeGrade($apprentice)));

    $this->actingAs($coach)
        ->get(route('notifications.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('unreadNotificationsCount', 1)
            ->has('notifications', 1, fn (Assert $notification) => $notification
                ->where('author', ['name' => $apprentice->name, 'role' => 'Apprenti'])
                ->where('action', 'a ajouté une note')
                ->where('target', 'Test node')
                ->where('read', false)
                ->etc()));
});

test('the supervisors of an apprentice are their active coach and every trainer of their plan', function () {
    $it = section('IT');
    $coach = User::factory()->coach()->create();
    $firstTrainer = makeTrainer($it);
    $secondTrainer = makeTrainer($it);
    makeTrainer(section('EC'));
    $apprentice = makeApprentice($it, $coach);

    expect($apprentice->supervisors()->pluck('id')->sort()->values()->all())
        ->toBe(collect([$coach->id, $firstTrainer->id, $secondTrainer->id])->sort()->values()->all());

    $secondTrainer->forceFill(['is_active' => false])->save();
    $coach->forceFill(['is_active' => false])->save();
    expect($apprentice->fresh()->supervisors()->pluck('id')->all())->toBe([$firstTrainer->id]);

    expect(makeApprentice()->supervisors())->toBeEmpty();
});

test('a notification links the coach to the apprentice grade page', function () {
    $coach = User::factory()->coach()->create();
    $grade = makeGrade(makeApprentice(coach: $coach));

    Notification::send($grade->user->supervisors(), new GradeAddedNotification($grade));

    expect($coach->notifications()->first()->data['url'])
        ->toBe(route('apprentices.grades.show', [$grade->user, $grade], absolute: false));
});

test('a deleted grade notification keeps its name and has no link', function () {
    $coach = User::factory()->coach()->create();
    $grade = makeGrade(makeApprentice(coach: $coach));

    $notification = new GradeDeletedNotification($grade);
    $grade->delete();
    $coach->notify($notification);

    expect($coach->notifications()->first()->data)
        ->toMatchArray(['action' => 'a supprimé une note', 'target' => 'Test node', 'url' => null]);
});

test('commenting on a grade notifies its apprentice', function () {
    $coach = User::factory()->coach()->create();
    $apprentice = makeApprentice(coach: $coach);
    $grade = makeGrade($apprentice);

    $this->actingAs($coach)
        ->post(route('grades.comments.store', $grade), ['body' => 'Bien joué'])
        ->assertRedirect();

    expect($apprentice->notifications)->toHaveCount(1)
        ->and($apprentice->notifications->first()->data)->toMatchArray([
            'author' => ['name' => $coach->name, 'role' => 'Coach'],
            'action' => 'a commenté votre note',
            'url' => route('grades.show', $grade, absolute: false),
        ]);
});

test('a refused comment notifies nobody', function () {
    $apprentice = makeApprentice();

    $this->actingAs(User::factory()->coach()->create())
        ->post(route('grades.comments.store', makeGrade($apprentice)), ['body' => 'Non'])
        ->assertForbidden();

    expect($apprentice->notifications)->toBeEmpty();
});

describe('read state', function () {
    beforeEach(function () {
        $this->coach = User::factory()->coach()->create();
        $this->coach->notify(new GradeAddedNotification(makeGrade(makeApprentice(coach: $this->coach))));
        $this->notification = $this->coach->notifications()->first();
    });

    test('a notification is marked as read then unread', function () {
        $this->actingAs($this->coach)
            ->put(route('notifications.read', $this->notification->id))
            ->assertRedirect();
        expect($this->notification->fresh()->read_at)->not->toBeNull();

        $this->actingAs($this->coach)
            ->delete(route('notifications.unread', $this->notification->id))
            ->assertRedirect();
        expect($this->notification->fresh()->read_at)->toBeNull();
    });

    test('all notifications are marked as read', function () {
        $this->actingAs($this->coach)->put(route('notifications.read-all'))->assertRedirect();

        expect($this->coach->unreadNotifications()->count())->toBe(0);
    });

    test("someone else's notification cannot be touched", function () {
        $this->actingAs(User::factory()->coach()->create())
            ->put(route('notifications.read', $this->notification->id))
            ->assertNotFound();

        expect($this->notification->fresh()->read_at)->toBeNull();
    });
});
