<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\MicrosoftAuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware('guest')->get('/login', fn (Request $request) => Inertia::render('auth/Login', [
    'status' => $request->session()->get('status'),
    'error' => $request->session()->get('error'),
]))->name('login');

Route::middleware('auth')->post('/logout', [AuthenticatedSessionController::class, 'destroy'])
    ->name('logout');

Route::get('/auth/microsoft', [MicrosoftAuthController::class, 'redirectToProvider'])
    ->name('microsoft.redirect');

Route::get('/auth/microsoft/callback', [MicrosoftAuthController::class, 'callback'])
    ->name('microsoft.callback');
