<?php

use App\Enums\Permission;
use App\Http\Controllers\ApprenticeController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\DossierController;
use App\Http\Controllers\GradeController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\SupervisionController;
use App\Models\Grade;
use Illuminate\Foundation\Http\Middleware\HandlePrecognitiveRequests;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/', HomeController::class)->name('home');
    Route::get('/notifications', NotificationController::class)->name('notifications.index');

    Route::prefix('grades')->name('grades.')->group(function () {
        Route::get('/dashboard', [GradeController::class, 'dashboard'])
            ->middleware('can:'.Permission::GradesViewOwn->value)
            ->name('dashboard');
        // GradePolicy::create / update are checked by GradeRequest.
        Route::post('/', [GradeController::class, 'store'])->name('store');
        Route::post('/read-scan', [GradeController::class, 'readScan'])
            ->middleware('can:create,'.Grade::class)
            ->name('read-scan');
        Route::put('/{grade}', [GradeController::class, 'update'])
            ->whereNumber('grade')
            ->name('update');
        Route::delete('/{grade}', [GradeController::class, 'destroy'])
            ->whereNumber('grade')
            ->middleware('can:delete,grade')
            ->name('destroy');
        Route::get('/{grade}', [GradeController::class, 'show'])
            ->whereNumber('grade')
            ->middleware('can:view,grade')
            ->name('show');
        Route::get('/{grade}/file', [GradeController::class, 'file'])
            ->whereNumber('grade')
            ->middleware('can:view,grade')
            ->name('file');
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
            // Created and edited in the portfolio page's dialog: no create/edit pages.
            Route::resource('projects', DossierController::class)
                ->only(['store', 'update', 'destroy'])
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
                // ApprenticeController::assign() checks the coach or trainer permission.
                Route::post('/assign', 'assign')->name('assign');
            });
    });

    Route::put('/apprentices/{apprentice}/coach', [SupervisionController::class, 'updateCoach'])
        ->whereNumber('apprentice')
        ->middleware('can:'.Permission::SupervisionManage->value)
        ->name('apprentices.coach.update');
});

require __DIR__.'/auth.php';
