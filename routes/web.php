<?php

use App\Enums\Permission;
use App\Http\Controllers\ApprenticeController;
use App\Http\Controllers\DossierController;
use App\Http\Controllers\GradeController;
use App\Models\Grade;
use Illuminate\Foundation\Http\Middleware\HandlePrecognitiveRequests;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('/', 'Home')->name('home');
    Route::inertia('/grades/dashboard', 'GradesDashboard')->name('grades.dashboard');
    // Le portfolio appartient à l'apprenti ; la ProjectPolicy vérifie en plus la propriété du projet.
    Route::middleware('role:apprentice')->group(function () {
        Route::get('/portfolio', [DossierController::class, 'index'])->name('portfolio.index');
        Route::get('/portfolio/preview', [DossierController::class, 'preview'])->name('portfolio.preview');
        Route::resource('portfolio/projects', DossierController::class)
            ->except(['index', 'show'])
            ->names('portfolio.projects')
            ->middlewareFor(['store', 'update'], HandlePrecognitiveRequests::class)
            ->whereNumber('project');
        Route::get('/portfolio/screenshots/{screenshot}', [DossierController::class, 'screenshot'])
            ->whereNumber('screenshot')
            ->name('portfolio.screenshots.show');
    });

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

    Route::prefix('grades')->name('grades.')->group(function () {
        Route::inertia('/create', 'CreateGrade')
            ->middleware('can:create,'.Grade::class)
            ->name('create');
        Route::get('/dashboard', [GradeController::class, 'dashboard'])
            ->middleware('can:'.Permission::GradesViewOwn->value)
            ->name('dashboard');
        Route::get('/{grade}', [GradeController::class, 'show'])
            ->whereNumber('grade')
            ->middleware('can:view,grade')
            ->name('show');
    });

    Route::middleware('can:'.Permission::PortfolioManageOwn->value)
        ->prefix('portfolio')
        ->name('portfolio.')
        ->controller(DossierController::class)
        ->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/preview', 'preview')->name('preview');
            Route::resource('projects', DossierController::class)
                ->except(['index', 'show'])
                ->middlewareFor(['store', 'update'], HandlePrecognitiveRequests::class)
                ->whereNumber('project');
        });

    Route::get('/portfolio/screenshots/{screenshot}', [DossierController::class, 'screenshot'])
        ->whereNumber('screenshot')
        ->name('portfolio.screenshots.show');

    Route::middleware('can:'.Permission::ApprenticesViewList->value)->group(function () {
        Route::inertia('/apprentisdashboard', 'ApprentisDashboard')->name('apprentisdashboard');

        Route::prefix('apprentices/{apprentice}')
            ->whereNumber('apprentice')
            ->name('apprentices.')
            ->controller(ApprenticeController::class)
            ->group(function () {
                Route::get('/', 'show')->name('show');
                Route::get('/grades/{grade}', 'grade')
                    ->whereNumber('grade')
                    ->name('grades.show');
            });
    });
});

require __DIR__.'/profile.php';
require __DIR__.'/auth.php';
