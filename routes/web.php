<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('/', 'Home')->name('home');
    Route::inertia('/grades/create', 'CreateGrade')->name('grades.create');
    Route::inertia('/dashboard', 'Dashboard')->name('dashboard');
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

    Route::inertia('/grades/{grade}', 'GradeDetails', [
        'pdfUrl' => '/demo/sample-grade-test.pdf',
        'comments' => [
            [
                'author' => 'Marc Dubois',
                'role' => 'Coach',
                'date' => '15.11.2025',
                'text' => 'Bon résultat sur la partie pratique. Pour le prochain test, revois la gestion des transactions et les jointures multiples.',
            ],
            [
                'author' => 'Sylvie Meier',
                'role' => 'Formateur',
                'date' => '17.11.2025',
                'text' => 'Vu en cours la semaine prochaine — on reprendra l\'exercice 4 ensemble.',
            ],
        ],
    ])->name('grades.show');

    Route::inertia('/apprentisdashboard', 'ApprentisDashboard')->name('apprentisdashboard');

    Route::inertia('/administration', 'Administration')->name('administration');

});

require __DIR__.'/profile.php';

require __DIR__.'/auth.php';
