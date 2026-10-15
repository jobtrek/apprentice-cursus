<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Inertia\Inertia;
use Inertia\Response;

class NotificationController extends Controller
{
    /**
     * Inbox. The notifications themselves are the `notifications` prop shared with every
     * page (see HandleInertiaRequests), as the navbar bell needs them too. Only the one to
     * open transits through the URL (`?notification=<id>`) so a click in the navbar menu
     * lands directly on it.
     */
    public function index(Request $request): Response
    {
        return Inertia::render('Notifications', [
            'selected' => $request->string('notification')->toString() ?: null,
        ]);
    }

    /**
     * Looked up through the user's own notifications: someone else's id is a 404.
     */
    public function markAsRead(Request $request, string $notification): RedirectResponse
    {
        $this->own($request, $notification)->markAsRead();

        return back();
    }

    public function markAsUnread(Request $request, string $notification): RedirectResponse
    {
        $this->own($request, $notification)->markAsUnread();

        return back();
    }

    public function markAllAsRead(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return back();
    }

    private function own(Request $request, string $id): DatabaseNotification
    {
        /** @var DatabaseNotification */
        return $request->user()->notifications()->findOrFail($id);
    }
}
