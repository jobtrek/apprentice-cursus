<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('/', 'Home')->name('home');
    Route::inertia('/grades/create', 'CreateGrade')->name('grades.create');
    Route::inertia('/dashboard', 'Dashboard')->name('dashboard');
});

require __DIR__.'/profile.php';
