<?php

use App\Models\User;
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
        ->get(route('notifications.index', ['notification' => 3]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('selected', 3));
});
