<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Notifications\DatabaseNotification;

/**
 * Matches the `AppNotification` type in resources/js/types/notification.ts.
 *
 * @mixin DatabaseNotification
 */
class NotificationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'author' => $this->data['author'],
            'action' => $this->data['action'],
            'target' => $this->data['target'],
            'url' => $this->data['url'],
            'created_at' => $this->created_at->toIso8601String(),
            'read' => $this->read_at !== null,
        ];
    }
}
