<?php

namespace App\Notifications;

use App\Enums\UserRole;
use App\Models\Grade;
use App\Models\User;
use Illuminate\Notifications\Notification;

/**
 * In-app (database) notification about a grade. The stored payload matches the
 * `AppNotification` type in resources/js/types/notification.ts (minus id, date and read
 * state, which come from the notifications row).
 *
 * The payload is a snapshot built when the notification is created: a deleted grade
 * must still be describable, and a later rename must not rewrite history.
 */
abstract class GradeActivityNotification extends Notification
{
    /** @var array{author: array{name: string, role: string}, action: string, target: string, url: string|null} */
    private array $payload;

    /**
     * @param  User  $author  Who did it: the apprentice, or the coach/trainer commenting.
     * @param  string|null  $url  Where to open the grade, null when it no longer exists.
     */
    public function __construct(User $author, Grade $grade, string $action, ?string $url)
    {
        $this->payload = [
            'author' => [
                'name' => $author->name,
                'role' => self::roleLabel($author->role),
            ],
            'action' => $action,
            'target' => $grade->averageNode->name,
            'url' => $url,
        ];
    }

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array{author: array{name: string, role: string}, action: string, target: string, url: string|null}
     */
    public function toArray(object $notifiable): array
    {
        return $this->payload;
    }

    private static function roleLabel(?UserRole $role): string
    {
        return match ($role) {
            UserRole::Coach => 'Coach',
            UserRole::Trainer => 'Formateur',
            UserRole::Apprentice => 'Apprenti',
            default => '',
        };
    }
}
