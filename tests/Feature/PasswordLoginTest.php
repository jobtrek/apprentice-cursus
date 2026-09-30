<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('password login is not available outside the local environment', function () {
    $user = User::factory()->create(['password' => 'password']);

    $this->post('/login', ['email' => $user->email, 'password' => 'password'])
        ->assertStatus(405);

    $this->assertGuest();
});

test('the login page hides the password form outside the local environment', function () {
    $this->get(route('login'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('auth/Login')
            ->where('passwordLoginUrl', null));
});
