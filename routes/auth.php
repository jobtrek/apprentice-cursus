<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\MicrosoftAuthController;

Route::get('/auth/microsoft', [MicrosoftAuthController::class, 'redirect'])
    ->name('microsoft.redirect');

Route::get('/auth/microsoft/callback', [MicrosoftAuthController::class, 'callback'])
    ->name('microsoft.callback');
