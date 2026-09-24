<?php

namespace App\Http\Resources;

use App\Models\Project;
use App\Models\ProjectScreenshot;
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
            'technologies' => $this->technologyList(),
            'repository_url' => $this->repository_url,
            'demo_path' => $this->demo_path,
            'date_start' => $this->date_start->toDateString(),
            'date_end' => $this->date_end?->toDateString(),
            'screenshots' => $this->whenLoaded('screenshots', fn () => $this->screenshots->map(fn (ProjectScreenshot $screenshot) => [
                'id' => $screenshot->id,
                'url' => route('portfolio.screenshots.show', $screenshot),
            ])),
            'skill_ids' => $this->whenLoaded('skills', fn () => $this->skills->modelKeys()),
        ];
    }

    /**
     * Sent as the same list the form submits, so the front never has to split
     * the comma-separated column (see PortfolioProjectRequest::prepareForValidation()).
     *
     * @return list<string>
     */
    private function technologyList(): array
    {
        if ($this->technologies === null) {
            return [];
        }

        $technologies = array_map(trim(...), explode(',', $this->technologies));

        return array_values(array_filter($technologies, fn (string $technology): bool => $technology !== ''));
    }
}
