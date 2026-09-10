<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('/', 'Home')->name('home');
    Route::inertia('/grades/create', 'CreateGrade')->name('grades.create');
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
});

require __DIR__.'/profile.php';
