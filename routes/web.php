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

    Route::prefix('grades')->name('grades.')->group(function () {
        Route::inertia('/create', 'CreateGrade')
            ->middleware('can:create,'.Grade::class)
            ->name('create');
        Route::inertia('/dashboard', 'GradesDashboard')
            ->middleware('can:'.Permission::GradesViewOwn->value)
            ->name('dashboard');
        Route::get('/{grade}', [GradeController::class, 'show'])
            ->whereNumber('grade')
            ->middleware('can:view,grade')
            ->name('show');
    });

    Route::middleware('can:portfolio.manage-own')
        ->prefix('portfolio')
        ->name('portfolio.')
        ->controller(DossierController::class)
        ->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/preview', 'preview')->name('preview');
            Route::resource('projects', DossierController::class)
                ->except(['index', 'show'])
                ->middlewareFor(['store', 'update'], HandlePrecognitiveRequests::class);
        });

    // Outside portfolio.manage-own: supervisors load screenshots too; ProjectPolicy::view decides.
    Route::get('/portfolio/screenshots/{screenshot}', [DossierController::class, 'screenshot'])
        ->name('portfolio.screenshots.show');

    Route::middleware('can:apprentices.view-list')->group(function () {
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
