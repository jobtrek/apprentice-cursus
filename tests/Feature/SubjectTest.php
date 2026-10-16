<?php

use App\Models\AverageNode;
use App\Models\FormationPlan;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Inertia\Testing\AssertableInertia as Assert;

function makeDomain(): array
{
    $plan = FormationPlan::query()->firstOrCreate(['name' => FormationPlan::IT]);
    $domain = AverageNode::query()->create(['name' => 'Domaine test', 'rounding_step' => 0.1]);
    $subject = AverageNode::query()->create(['name' => 'Matière test', 'parent_id' => $domain->id, 'weight' => 1]);
    $domain->formationPlans()->attach($plan);
    $subject->formationPlans()->attach($plan);

    return [$domain, $subject, $plan];
}

beforeEach(function () {
    // The local environment (needed for the admin bypass) does not skip CSRF like testing does.
    $this->withoutMiddleware(PreventRequestForgery::class);
    app()->detectEnvironment(fn () => 'local');
    $this->admin = User::factory()->admin()->create();
});

test('only the local admin reaches the subjects', function () {
    $this->actingAs(User::factory()->coach()->create())->get(route('subjects.index'))->assertForbidden();
    $this->actingAs(User::factory()->create())->get(route('subjects.index'))->assertForbidden();

    app()->detectEnvironment(fn () => 'production');
    $this->actingAs($this->admin)->get(route('subjects.index'))->assertForbidden();
});

test('the admin sees the leaves with their tracks and grade count', function () {
    [, $subject] = makeDomain();
    makeGrade(makeApprentice())->update(['average_node_id' => $subject->id]);

    $this->actingAs($this->admin)->get(route('subjects.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('Subjects')
            ->has('subjects', 1)
            ->where('subjects.0.name', 'Matière test')
            ->where('subjects.0.parent', 'Domaine test')
            ->where('subjects.0.tracks', ['IT'])
            ->where('subjects.0.grades_count', 1)
            ->has('parents', 1)
            ->where('parents.0.label', 'Domaine test (IT)')
            ->where('subjects.0.can_delete', false));
});

test('a subject is created under a domain and joins its plans', function () {
    [$domain, , $plan] = makeDomain();

    $this->actingAs($this->admin)
        ->post(route('subjects.store'), ['name' => 'Nouvelle', 'parent_id' => $domain->id, 'weight' => 25])
        ->assertRedirect(route('subjects.index'));

    $subject = AverageNode::query()->where('name', 'Nouvelle')->firstOrFail();

    expect($subject->parent_id)->toBe($domain->id)
        ->and($subject->weight)->toBe('0.25')
        ->and($subject->formationPlans->pluck('id')->all())->toBe([$plan->id]);
});

test('a subject cannot be created under a leaf or with a wrong weight', function () {
    [, $leaf] = makeDomain();

    $this->actingAs($this->admin)
        ->post(route('subjects.store'), ['name' => 'X', 'parent_id' => $leaf->id, 'weight' => 10])
        ->assertSessionHasErrors('parent_id');

    $this->actingAs($this->admin)
        ->post(route('subjects.store'), ['name' => 'X', 'parent_id' => $leaf->parent_id, 'weight' => 0])
        ->assertSessionHasErrors('weight');
});

test('a subject is renamed, reweighted and moved with its plans', function () {
    [$domain, $subject] = makeDomain();
    $ec = FormationPlan::query()->create(['name' => FormationPlan::EC]);
    $other = AverageNode::query()->create(['name' => 'Autre domaine']);
    $other->formationPlans()->attach($ec);
    AverageNode::query()->create(['name' => 'Voisine', 'parent_id' => $domain->id, 'weight' => 0.5]);
    AverageNode::query()->create(['name' => 'Enfant', 'parent_id' => $other->id, 'weight' => 1]);

    $this->actingAs($this->admin)
        ->put(route('subjects.update', $subject), ['name' => 'Renommée', 'parent_id' => $other->id, 'weight' => 40])
        ->assertRedirect(route('subjects.index'));

    $subject->refresh();

    expect($subject->name)->toBe('Renommée')
        ->and($subject->parent_id)->toBe($other->id)
        ->and($subject->weight)->toBe('0.40')
        ->and($subject->formationPlans->pluck('id')->all())->toBe([$ec->id]);
});

test('a subject with grades only moves to a domain of the same tracks', function () {
    [$domain, $subject] = makeDomain();
    AverageNode::query()->create(['name' => 'Voisine', 'parent_id' => $domain->id, 'weight' => 0.5]);
    makeGrade(makeApprentice())->update(['average_node_id' => $subject->id]);
    $ec = FormationPlan::query()->create(['name' => FormationPlan::EC]);
    $other = AverageNode::query()->create(['name' => 'Autre domaine']);
    $other->formationPlans()->attach($ec);
    AverageNode::query()->create(['name' => 'Enfant', 'parent_id' => $other->id, 'weight' => 1]);

    $this->actingAs($this->admin)
        ->put(route('subjects.update', $subject), ['name' => 'Matière test', 'parent_id' => $other->id, 'weight' => 10])
        ->assertSessionHasErrors('parent_id');

    expect($subject->fresh()->parent_id)->toBe($domain->id);
});

test('the last subject of a domain can neither move nor be deleted', function () {
    [$domain, $subject] = makeDomain();
    $other = AverageNode::query()->create(['name' => 'Autre domaine']);
    AverageNode::query()->create(['name' => 'Enfant', 'parent_id' => $other->id, 'weight' => 1]);

    $this->actingAs($this->admin)
        ->put(route('subjects.update', $subject), ['name' => 'X', 'parent_id' => $other->id, 'weight' => 10])
        ->assertSessionHasErrors('parent_id');

    $this->actingAs($this->admin)->delete(route('subjects.destroy', $subject))->assertSessionHasErrors('subject');

    expect($subject->fresh()->parent_id)->toBe($domain->id);
});

test('a domain is not a subject', function () {
    [$domain] = makeDomain();

    $this->actingAs($this->admin)->put(route('subjects.update', $domain), ['name' => 'X', 'parent_id' => $domain->id, 'weight' => 10])->assertNotFound();
    $this->actingAs($this->admin)->delete(route('subjects.destroy', $domain))->assertNotFound();
});

test('a subject without grades is deleted, one with grades is kept', function () {
    [$domain, $subject] = makeDomain();
    AverageNode::query()->create(['name' => 'Voisine', 'parent_id' => $domain->id, 'weight' => 0.5]);

    $this->actingAs($this->admin)->delete(route('subjects.destroy', $subject))
        ->assertRedirect(route('subjects.index'));
    expect(AverageNode::query()->whereKey($subject->id)->exists())->toBeFalse();

    [$usedDomain, $used] = makeDomain();
    AverageNode::query()->create(['name' => 'Voisine 2', 'parent_id' => $usedDomain->id, 'weight' => 0.5]);
    makeGrade(makeApprentice())->update(['average_node_id' => $used->id]);

    $this->actingAs($this->admin)->delete(route('subjects.destroy', $used))
        ->assertSessionHasErrors('subject');
    expect($used->fresh())->not->toBeNull();
});

test('the server searches and filters the subjects', function () {
    [, , $plan] = makeDomain();

    $this->actingAs($this->admin)->get(route('subjects.index', ['search' => 'zzz']))
        ->assertInertia(fn (Assert $page) => $page->has('subjects', 0)->where('total', 1));

    $this->actingAs($this->admin)->get(route('subjects.index', ['track' => 'EC', 'domain' => 'Domaine test']))
        ->assertInertia(fn (Assert $page) => $page
            ->has('subjects', 0)
            ->where('filters.domain', 'all'));

    $this->actingAs($this->admin)->get(route('subjects.index', ['track' => 'IT', 'search' => 'matière', 'domain' => 'Domaine test']))
        ->assertInertia(fn (Assert $page) => $page->has('subjects', 1)->where('subjects.0.can_delete', true));
});
