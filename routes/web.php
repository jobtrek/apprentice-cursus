<?php

use App\Enums\Permission;
use App\Http\Controllers\ApprenticeController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\DossierController;
use App\Http\Controllers\GradeController;
use App\Http\Controllers\HomeController;
use Illuminate\Foundation\Http\Middleware\HandlePrecognitiveRequests;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/', HomeController::class)->name('home');

    Route::prefix('grades')->name('grades.')->group(function () {
        Route::get('/dashboard', [GradeController::class, 'dashboard'])
            ->middleware('can:'.Permission::GradesViewOwn->value)
            ->name('dashboard');
        Route::get('/{grade}', [GradeController::class, 'show'])
            ->whereNumber('grade')
            ->middleware('can:view,grade')
            ->name('show');
        Route::post('/{grade}/comments', [CommentController::class, 'store'])
            ->whereNumber('grade')
            ->name('comments.store');
    });

    Route::put('/comments/{comment}', [CommentController::class, 'update'])
        ->whereNumber('comment')
        ->middleware('can:update,comment')
        ->name('comments.update');

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
        Route::get('/apprentisdashboard', [ApprenticeController::class, 'index'])->name('apprentisdashboard');

        Route::prefix('apprentices/{apprentice}')
            ->whereNumber('apprentice')
            ->name('apprentices.')
            ->controller(ApprenticeController::class)
            ->group(function () {
                Route::get('/', 'show')->name('show');
                Route::get('/grades/{grade}', 'grade')
                    ->whereNumber('grade')
                    ->name('grades.show');
                Route::post('/assign', 'assign')
                    ->middleware('can:'.Permission::CoachingAssignSelf->value)
                    ->name('assign');
            });
    });
});

require __DIR__.'/profile.php';
require __DIR__.'/auth.php';
