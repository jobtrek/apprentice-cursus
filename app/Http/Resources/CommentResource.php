<?php

namespace App\Http\Resources;

use App\Enums\UserRole;
use App\Models\Comment;
use App\Models\Grade;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Matches the `Comment` type in resources/js/pages/GradeDetails.vue.
 *
 * Expects `CommentResource::RELATIONS` to be eager loaded.
 *
 * @mixin Comment
 */
class CommentResource extends JsonResource
{
    /** Eager-load path for the author and the Spatie role behind the role label. */
    public const RELATIONS = 'author.roles';

    /**
     * The grade's comments, oldest first, as the page payload.
     *
     * @return list<array<string, mixed>>
     */
    public static function forGrade(Grade $grade): array
    {
        return array_values(self::collection(
            $grade->comments()->with(self::RELATIONS)->oldest()->oldest('id')->get(),
        )->resolve());
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'author' => $this->author->name,
            'role' => $this->roleLabel(),
            // Same format as GradeResource.
            'date' => $this->created_at->format('d.m.Y'),
            'text' => $this->body,
        ];
    }

    /** French label used by the front to style the author's dot and role. */
    private function roleLabel(): string
    {
        return match ($this->author->role) {
            UserRole::Coach => 'Coach',
            UserRole::Trainer => 'Formateur',
            UserRole::Apprentice => 'Apprenti',
            default => 'Intervenant',
        };
    }
}
