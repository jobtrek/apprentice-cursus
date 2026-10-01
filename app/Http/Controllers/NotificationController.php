<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class NotificationController extends Controller
{
    /**
     * Boîte de réception. Les notifications elles-mêmes viennent encore du
     * JSON de démo côté client : seule la notification à ouvrir transite par
     * l'URL (`?notification=3`) pour qu'un clic dans le menu de la navbar
     * arrive directement dessus.
     */
    public function __invoke(Request $request): Response
    {
        return Inertia::render('Notifications', [
            'selected' => $request->integer('notification') ?: null,
        ]);
    }
}
