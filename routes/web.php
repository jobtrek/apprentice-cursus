<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('/', 'Home')->name('home');

    Route::inertia('/portfolio', 'Portfolio')->name('portfolio.index');
    Route::inertia('/portfolio/preview', 'PortfolioPreview')
        ->name('portfolio.preview');
    Route::inertia('/portfolio/projects/create', 'PortfolioProjectForm')
        ->name('portfolio.projects.create');
    Route::get(
        '/portfolio/projects/{project}/edit',
        fn (int $project) => Inertia::render('PortfolioProjectForm', [
            'projectId' => $project,
        ])
    )->whereNumber('project')->name('portfolio.projects.edit');
});

require __DIR__.'/profile.php';
