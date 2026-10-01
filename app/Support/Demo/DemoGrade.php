<?php

namespace App\Support\Demo;

/**
 * Données de démonstration, en attendant le modèle Grade côté serveur.
 * Exposées uniquement en environnement local.
 */
final class DemoGrade
{
    /**
     * Demo PDF in the local environment, null everywhere else.
     *
     * @return array{pdfUrl: string|null}
     */
    public static function props(): array
    {
        return [
            'pdfUrl' => app()->environment('local') ? '/demo/sample-grade-test.pdf' : null,
        ];
    }
}
