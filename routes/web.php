<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('/', 'Home')->name('home');
    Route::inertia('/grades/create', 'CreateGrade')->name('grades.create');
    Route::inertia('/grades/dashboard', 'GradesDashboard')->name('grades.dashboard');
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

    // Données de démonstration, en attendant le modèle Grade côté serveur.
    $demoGrade = [
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
    ];

    Route::get(
        '/grades/{grade}',
        fn (int $grade) => Inertia::render('GradeDetails', [
            ...$demoGrade,
            'gradeId' => $grade,
        ])
    )->whereNumber('grade')->name('grades.show');

    // Parcours coach/formateur : liste → apprenti → carnet de notes → épreuve.
    // Mêmes rôles que `SUPERVISORS` dans resources/js/constants/navigation.ts.
    // TODO: limiter les formateurs à leur section quand les apprentis viendront
    // de la base (aujourd'hui des données de démo côté frontend).
    Route::middleware('role:coach,trainer,admin,super_admin')->group(function () use ($demoGrade) {
        Route::inertia('/apprentisdashboard', 'ApprentisDashboard')->name('apprentisdashboard');

        Route::get(
            '/apprentices/{apprentice}',
            fn (int $apprentice) => Inertia::render('ApprenticeShow', [
                'apprenticeId' => $apprentice,
            ])
        )->whereNumber('apprentice')->name('apprentices.show');

        Route::get(
            '/apprentices/{apprentice}/grades/{grade}',
            fn (int $apprentice, int $grade) => Inertia::render('GradeDetails', [
                ...$demoGrade,
                'apprenticeId' => $apprentice,
                'gradeId' => $grade,
            ])
        )->whereNumber(['apprentice', 'grade'])->name('apprentices.grades.show');
    });
});

require __DIR__.'/profile.php';

require __DIR__.'/auth.php';
