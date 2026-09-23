<?php

namespace App\Http\Resources;

use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Matches the `PortfolioProject` type in resources/js/types/portfolio.ts.
 *
 * @mixin Project
 */
class ProjectResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'organization' => $this->organization,
            'description' => $this->description,
            'responsibilities' => $this->responsibilities,
            'technologies' => $this->technologies,
            'repository_url' => $this->repository_url,
            'demo_path' => $this->demo_path,
            'date_start' => $this->date_start->toDateString(),
            'date_end' => $this->date_end?->toDateString(),
            // No storage for screenshots yet; the frontend type still expects the key.
            'screenshots' => [],
            'skill_ids' => $this->whenLoaded('skills', fn () => $this->skills->modelKeys()),
        ];
    }
}
