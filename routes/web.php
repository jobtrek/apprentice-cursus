<?php

use App\Enums\Permission;
use App\Http\Controllers\ApprenticeController;
use App\Http\Controllers\DossierController;
use App\Http\Controllers\GradeController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\SupervisionController;
use Illuminate\Foundation\Http\Middleware\HandlePrecognitiveRequests;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/', HomeController::class)->name('home');
    Route::get('/notifications', NotificationController::class)->name('notifications.index');

    Route::prefix('grades')->name('grades.')->group(function () {
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

    Route::put('/apprentices/{apprentice}/coach', [SupervisionController::class, 'updateCoach'])
        ->whereNumber('apprentice')
        ->middleware('can:'.Permission::SupervisionManage->value)
        ->name('apprentices.coach.update');
});

require __DIR__.'/auth.php';
