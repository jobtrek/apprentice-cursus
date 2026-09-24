<?php

namespace App\Http\Controllers;

use App\Http\Requests\PortfolioProjectRequest;
use App\Http\Resources\ProjectResource;
use App\Models\Project;
use App\Models\ProjectScreenshot;
use App\Models\Skill;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class DossierController extends Controller
{
    public function index(Request $request): Response
    {
        $projects = $request->user()->projects()
            ->with(['skills:id', 'screenshots'])
            ->orderByDesc('date_start')
            ->get();

        return Inertia::render('Portfolio', [
            'projects' => ProjectResource::collection($projects)->resolve(),
        ]);
    }

    public function preview(Request $request): Response
    {
        $user = $request->user()->load('apprenticeship');

        $projects = $user->projects()
            ->with(['skills:id', 'screenshots'])
            ->orderByDesc('date_start')
            ->get();

        return Inertia::render('PortfolioPreview', [
            'owner' => [
                'name' => $user->name,
                'track' => $user->apprenticeship?->name,
            ],
            'projects' => ProjectResource::collection($projects)->resolve(),
            'skills' => $this->skills(),
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('create', Project::class);

        return Inertia::render('PortfolioProjectForm', [
            'skills' => $this->skills(),
        ]);
    }

    public function store(PortfolioProjectRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $storedPaths = [];

        try {
            DB::transaction(function () use ($request, $data, &$storedPaths): void {
                $project = $request->user()->projects()->create($this->attributes($data));

                $project->skills()->sync($data['skill_ids'] ?? []);
                $this->storeScreenshots($project, $request->file('screenshots', []), $storedPaths);
            });
        } catch (Throwable $e) {
            Storage::delete($storedPaths);

            throw $e;
        }

        return redirect()->route('portfolio.index');
    }

    public function edit(Project $project): Response
    {
        Gate::authorize('update', $project);

        return Inertia::render('PortfolioProjectForm', [
            'project' => (new ProjectResource($project->load(['skills:id', 'screenshots'])))->resolve(),
            'skills' => $this->skills(),
        ]);
    }

    public function update(PortfolioProjectRequest $request, Project $project): RedirectResponse
    {
        $data = $request->validated();
        $storedPaths = [];

        try {
            DB::transaction(function () use ($request, $project, $data, &$storedPaths): void {
                $project->update($this->attributes($data));

                $project->skills()->sync($data['skill_ids'] ?? []);

                // Screenshots left out of `kept_screenshot_ids` were removed in the form.
                $project->screenshots()
                    ->whereNotIn('id', $data['kept_screenshot_ids'] ?? [])
                    ->get()
                    ->each->delete();
                $this->storeScreenshots($project, $request->file('screenshots', []), $storedPaths);
            });
        } catch (Throwable $e) {
            Storage::delete($storedPaths);

            throw $e;
        }

        return redirect()->route('portfolio.index');
    }

    public function destroy(Project $project): RedirectResponse
    {
        Gate::authorize('delete', $project);

        $project->delete();

        return redirect()->route('portfolio.index');
    }

    public function screenshot(ProjectScreenshot $screenshot): StreamedResponse
    {
        Gate::authorize('view', $screenshot->project);

        return Storage::response($screenshot->path);
    }

    /**
     * The disk does not take part in the transaction: every written path is
     * appended to `$storedPaths` so the caller can delete it on rollback.
     *
     * @param  array<int, UploadedFile>  $files
     * @param  list<string>  $storedPaths
     */
    private function storeScreenshots(Project $project, array $files, array &$storedPaths): void
    {
        foreach ($files as $file) {
            // The disk is configured with `throw => false`, so a failed write returns false.
            $path = $file->store("projects/{$project->id}");

            if ($path === false) {
                throw new RuntimeException("Could not store screenshot {$file->getClientOriginalName()}.");
            }

            $storedPaths[] = $path;
            $project->screenshots()->create(['path' => $path]);
        }
    }

    /**
     * Optional fields missing from the payload are written as null, so clearing
     * a field on update actually clears the column.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function attributes(array $data): array
    {
        return [
            'title' => $data['title'],
            'organization' => $data['organization'] ?? null,
            'description' => $data['description'],
            'responsibilities' => $data['responsibilities'] ?? null,
            'technologies' => $data['technologies'] ?? null,
            'repository_url' => $data['repository_url'] ?? null,
            'demo_path' => $data['demo_path'] ?? null,
            'date_start' => $data['date_start'],
            'date_end' => $data['date_end'] ?? null,
        ];
    }

    /**
     * @return Collection<int, Skill>
     */
    private function skills(): Collection
    {
        return Skill::query()->orderBy('name')->get(['id', 'name']);
    }
}
