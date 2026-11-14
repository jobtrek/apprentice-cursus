<?php

namespace App\Notifications;

use App\Models\Grade;
use App\Models\User;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;

/**
 * Mail sent to the shared mailbox when an apprentice's grades were imported by emails:fetch.
 */
class GradesReceivedNotification extends Notification
{
    /**
     * @param  Collection<int, Grade>  $grades  with `averageNode` loaded
     */
    public function __construct(private User $apprentice, private Collection $grades) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $count = $this->grades->count();

        $message = (new MailMessage)
            ->subject("{$this->apprentice->name} a envoyé {$count} note(s)")
            ->line("{$this->apprentice->name} a envoyé {$count} note(s) par e-mail :");

        foreach ($this->grades as $grade) {
            $message->line("- {$grade->averageNode->name} : {$grade->value}");
        }

        return $message;
    }
}
