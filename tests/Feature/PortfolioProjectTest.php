<?php

use App\Models\Project;
use App\Models\Skill;
use App\Models\User;
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
