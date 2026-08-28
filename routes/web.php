<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('/', 'Home')->name('home');
});

require __DIR__.'/profile.php';
