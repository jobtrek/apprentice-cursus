<?php

namespace App\Support\Demo;

/**
 * Données de démonstration, en attendant le modèle Grade côté serveur.
 * Exposées uniquement en environnement local.
 */
final class DemoGrade
{
    /**
     * Demo payload in the local environment, empty props everywhere else.
     *
     * @return array{pdfUrl: string|null, comments: list<array{author: string, role: string, date: string, text: string}>}
     */
    public static function props(): array
    {
        if (! app()->environment('local')) {
            return ['pdfUrl' => null, 'comments' => []];
        }

        return [
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
    }
}
