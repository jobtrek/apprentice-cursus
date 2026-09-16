<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('/', 'Home')->name('home');
    Route::inertia('/grades/create', 'CreateGrade')->name('grades.create');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('/apprentisdashboard', 'ApprentisDashboard')->name('apprentisdashboard');
});

require __DIR__.'/profile.php';
