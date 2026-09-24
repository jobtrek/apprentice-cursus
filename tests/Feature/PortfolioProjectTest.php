<?php

use App\Models\Project;
use App\Models\ProjectScreenshot;
use App\Models\Skill;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

function projectPayload(array $overrides = []): array
{
    return [
        'title' => 'Refonte du site',
        'organization' => 'ACME',
        'description' => 'Refonte complète en Vue.',
        'responsibilities' => 'Développeur',
        'technologies' => ['Laravel', 'Vue.js', 'Laravel'],
        'repository_url' => 'https://github.com/acme/site',
        'demo_path' => 'https://acme.test',
        'date_start' => '2026-01-01',
        'date_end' => '2026-03-01',
        'skill_ids' => [],
        ...$overrides,
    ];
}

test('apprentices only see their own projects', function () {
    $apprentice = User::factory()->create();
    $own = Project::factory()->for($apprentice)->create();
    Project::factory()->create();

    $this->actingAs($apprentice)
        ->get(route('portfolio.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Portfolio')
            ->has('projects', 1)
            ->where('projects.0.id', $own->id));
});

test('non-apprentices cannot access the portfolio', function () {
    $this->actingAs(User::factory()->coach()->create())
        ->get(route('portfolio.index'))
        ->assertForbidden();
});

test('an apprentice can create a project with skills', function () {
    $apprentice = User::factory()->create();
    $skills = Skill::factory()->count(2)->create();

    $this->actingAs($apprentice)
        ->post(route('portfolio.projects.store'), projectPayload([
            'skill_ids' => $skills->modelKeys(),
        ]))
        ->assertRedirect(route('portfolio.index'));

    $project = $apprentice->projects()->sole();

    expect($project->technologies)->toBe('Laravel, Vue.js')
        ->and($project->skills->modelKeys())->toEqualCanonicalizing($skills->modelKeys());
});

test('the end date cannot precede the start date', function () {
    $this->actingAs(User::factory()->create())
        ->post(route('portfolio.projects.store'), projectPayload(['date_end' => '2025-12-01']))
        ->assertSessionHasErrors('date_end');
});

test('an apprentice can update their project and clear optional fields', function () {
    $apprentice = User::factory()->create();
    $project = Project::factory()->for($apprentice)->create();
    $project->skills()->attach(Skill::factory()->create());

    $this->actingAs($apprentice)
        ->put(route('portfolio.projects.update', $project), projectPayload([
            'title' => 'Nouveau titre',
            'organization' => '',
        ]))
        ->assertRedirect(route('portfolio.index'));

    $project->refresh();

    expect($project->title)->toBe('Nouveau titre')
        ->and($project->organization)->toBeNull()
        ->and($project->skills)->toBeEmpty();
});

test('an apprentice can delete their project', function () {
    $apprentice = User::factory()->create();
    $project = Project::factory()->for($apprentice)->create();

    $this->actingAs($apprentice)
        ->delete(route('portfolio.projects.destroy', $project))
        ->assertRedirect(route('portfolio.index'));

    $this->assertModelMissing($project);
});

test('an apprentice cannot touch another apprentice\'s project', function () {
    $project = Project::factory()->create();

    $this->actingAs(User::factory()->create());

    $this->get(route('portfolio.projects.edit', $project))->assertForbidden();
    $this->put(route('portfolio.projects.update', $project), projectPayload())->assertForbidden();
    $this->delete(route('portfolio.projects.destroy', $project))->assertForbidden();

    $this->assertModelExists($project);
});

test('screenshots are stored with the project and served to its owner', function () {
    Storage::fake();
    $apprentice = User::factory()->create();

    $this->actingAs($apprentice)
        ->post(route('portfolio.projects.store'), projectPayload([
            'screenshots' => [UploadedFile::fake()->image('capture.png')],
        ]))
        ->assertRedirect(route('portfolio.index'));

    $screenshot = $apprentice->projects()->sole()->screenshots()->sole();
    Storage::assertExists($screenshot->path);

    $this->get(route('portfolio.screenshots.show', $screenshot))->assertOk();
});

test('updating keeps listed screenshots and deletes the others', function () {
    Storage::fake();
    $apprentice = User::factory()->create();
    $project = Project::factory()->for($apprentice)->create();
    [$kept, $removed] = collect(['kept.png', 'removed.png'])->map(fn (string $name) => $project->screenshots()->create([
        'path' => UploadedFile::fake()->image($name)->store("projects/{$project->id}"),
    ]))->all();

    $this->actingAs($apprentice)
        ->post(route('portfolio.projects.update', $project), projectPayload([
            '_method' => 'put',
            'kept_screenshot_ids' => [$kept->id],
            'screenshots' => [UploadedFile::fake()->image('new.png')],
        ]))
        ->assertRedirect(route('portfolio.index'));

    expect($project->screenshots()->count())->toBe(2);
    $this->assertModelMissing($removed);
    Storage::assertMissing($removed->path);
    Storage::assertExists($kept->path);
});

test('another project\'s screenshot cannot be kept or viewed', function () {
    Storage::fake();
    $apprentice = User::factory()->create();
    $project = Project::factory()->for($apprentice)->create();
    $foreign = Project::factory()->create()->screenshots()->create([
        'path' => UploadedFile::fake()->image('foreign.png')->store('projects/other'),
    ]);

    $this->actingAs($apprentice);

    $this->put(route('portfolio.projects.update', $project), projectPayload([
        'kept_screenshot_ids' => [$foreign->id],
    ]))->assertSessionHasErrors('kept_screenshot_ids.0');

    $this->get(route('portfolio.screenshots.show', $foreign))->assertForbidden();
    expect(ProjectScreenshot::find($foreign->id))->not->toBeNull();
});

test('deleting a project removes its screenshot files', function () {
    Storage::fake();
    $apprentice = User::factory()->create();
    $project = Project::factory()->for($apprentice)->create();
    $screenshot = $project->screenshots()->create([
        'path' => UploadedFile::fake()->image('capture.png')->store("projects/{$project->id}"),
    ]);

    $this->actingAs($apprentice)->delete(route('portfolio.projects.destroy', $project));

    Storage::assertMissing($screenshot->path);
});
