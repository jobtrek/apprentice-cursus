<?php

use App\Models\AverageNode;
use App\Models\FormationPlan;
use App\Models\Grade;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake();

    // A small tree: root -> domain -> two leaves, all part of the plan.
    $this->plan = section(FormationPlan::IT);
    $root = AverageNode::query()->create(['name' => 'Root']);
    $this->domain = AverageNode::query()->create(['name' => 'Compétences de base élargies', 'parent_id' => $root->id, 'weight' => 1]);
    $this->maths = AverageNode::query()->create(['name' => 'Mathématiques', 'parent_id' => $this->domain->id, 'weight' => 0.5]);
    $this->english = AverageNode::query()->create(['name' => 'Anglais', 'parent_id' => $this->domain->id, 'weight' => 0.5]);
    $this->plan->averageNodes()->attach([$root->id, $this->domain->id, $this->maths->id, $this->english->id]);

    $this->apprentice = makeApprentice($this->plan);
    $this->apprentice->forceFill(['name' => 'Bastien Nicoud'])->save();
});

/** @return array<string, mixed> */
function gradePayload(array $overrides = []): array
{
    return [
        'average_node_id' => test()->maths->id,
        'value' => 5.5,
        'test_date' => '2026-09-15',
        'file' => UploadedFile::fake()->create('scan.pdf', 200, 'application/pdf'),
        ...$overrides,
    ];
}

describe('store', function () {
    test('an apprentice adds a grade with its scan', function () {
        $this->actingAs($this->apprentice)
            ->post(route('grades.store'), gradePayload())
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $grade = Grade::query()->sole();

        expect($grade->user_id)->toBe($this->apprentice->id)
            ->and($grade->average_node_id)->toBe($this->maths->id)
            ->and($grade->value)->toBe('5.5')
            ->and($grade->test_date->toDateString())->toBe('2026-09-15')
            ->and($grade->file_path)->toStartWith("grades/{$this->apprentice->id}/");

        Storage::assertExists($grade->file_path);
    });

    test('an oral exam is recorded without a scan, and a file sent with it is dropped', function () {
        $this->actingAs($this->apprentice)
            ->post(route('grades.store'), gradePayload(['is_oral' => true]))
            ->assertSessionHasNoErrors();

        expect(Grade::query()->sole()->file_path)->toBeNull()
            ->and(Storage::allFiles())->toBe([]);
    });

    test('a written test needs its scan', function () {
        $this->actingAs($this->apprentice)
            ->post(route('grades.store'), gradePayload(['file' => null]))
            ->assertSessionHasErrors('file');

        expect(Grade::query()->count())->toBe(0);
    });

    test('invalid input is rejected', function (array $overrides, string $field) {
        $this->actingAs($this->apprentice)
            ->post(route('grades.store'), gradePayload($overrides))
            ->assertSessionHasErrors($field);

        expect(Grade::query()->count())->toBe(0)
            ->and(Storage::allFiles())->toBe([]);
    })->with([
        'grade below 1' => [['value' => 0.5], 'value'],
        'grade above 6' => [['value' => 6.5], 'value'],
        'grade off the 0.5 step' => [['value' => 4.3], 'value'],
        'date in the future' => [['test_date' => '2999-01-01'], 'test_date'],
        'not a PDF' => [['file' => UploadedFile::fake()->create('scan.docx', 200, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document')], 'file'],
        'PDF over 10 MB' => [['file' => UploadedFile::fake()->create('scan.pdf', 10241, 'application/pdf')], 'file'],
        'missing branch' => [['average_node_id' => null], 'average_node_id'],
    ]);

    test('a grade goes on a leaf, not on a domain', function () {
        $this->actingAs($this->apprentice)
            ->post(route('grades.store'), gradePayload(['average_node_id' => $this->domain->id]))
            ->assertSessionHasErrors('average_node_id');
    });

    test('a branch of another formation plan is rejected', function () {
        $other = AverageNode::query()->create(['name' => 'Other plan leaf']);
        section(FormationPlan::EC)->averageNodes()->attach($other->id);

        $this->actingAs($this->apprentice)
            ->post(route('grades.store'), gradePayload(['average_node_id' => $other->id]))
            ->assertSessionHasErrors('average_node_id');
    });

    test('a coach cannot add a grade', function () {
        $this->actingAs(User::factory()->coach()->create())
            ->post(route('grades.store'), gradePayload())
            ->assertForbidden();

        expect(Grade::query()->count())->toBe(0);
    });
});

describe('update', function () {
    beforeEach(function () {
        $this->actingAs($this->apprentice)->post(route('grades.store'), gradePayload());
        $this->grade = Grade::query()->sole();
        $this->oldPath = $this->grade->file_path;
    });

    test('an apprentice corrects a grade and keeps its scan', function () {
        $this->actingAs($this->apprentice)
            ->put(route('grades.update', $this->grade), gradePayload([
                'average_node_id' => $this->english->id,
                'value' => 4.0,
                'test_date' => '2026-09-20',
                'file' => null,
            ]))
            ->assertSessionHasNoErrors();

        $grade = $this->grade->fresh();

        expect($grade->average_node_id)->toBe($this->english->id)
            ->and($grade->value)->toBe('4.0')
            ->and($grade->test_date->toDateString())->toBe('2026-09-20')
            ->and($grade->file_path)->toBe($this->oldPath);

        Storage::assertExists($this->oldPath);
    });

    test('a new scan replaces the old one', function () {
        $this->actingAs($this->apprentice)
            ->put(route('grades.update', $this->grade), gradePayload())
            ->assertSessionHasNoErrors();

        $newPath = $this->grade->fresh()->file_path;

        expect($newPath)->not->toBe($this->oldPath);
        Storage::assertExists($newPath);
        Storage::assertMissing($this->oldPath);
    });

    test('turning it into an oral exam removes the scan', function () {
        $this->actingAs($this->apprentice)
            ->put(route('grades.update', $this->grade), gradePayload(['is_oral' => true]))
            ->assertSessionHasNoErrors();

        expect($this->grade->fresh()->file_path)->toBeNull();
        Storage::assertMissing($this->oldPath);
    });

    test('an oral exam turned into a written test needs a scan', function () {
        $this->grade->update(['file_path' => null]);

        $this->actingAs($this->apprentice)
            ->put(route('grades.update', $this->grade), gradePayload(['file' => null]))
            ->assertSessionHasErrors('file');
    });

    test('invalid input leaves the grade untouched', function () {
        $this->actingAs($this->apprentice)
            ->put(route('grades.update', $this->grade), gradePayload(['value' => 7]))
            ->assertSessionHasErrors('value');

        expect($this->grade->fresh()->value)->toBe('5.5');
        Storage::assertExists($this->oldPath);
    });

    test('comments stay on an edited grade', function () {
        $coach = User::factory()->coach()->create();
        $this->grade->comments()->forceCreate(['author_id' => $coach->id, 'body' => 'Bravo']);

        $this->actingAs($this->apprentice)
            ->put(route('grades.update', $this->grade), gradePayload(['value' => 4.5, 'file' => null]));

        expect($this->grade->comments()->count())->toBe(1);
    });

    test('nobody else can edit the grade', function (string $who) {
        $user = match ($who) {
            'another apprentice' => makeApprentice($this->plan),
            'the coach' => tap(User::factory()->coach()->create(), fn (User $coach) => $this->apprentice->forceFill(['coach_id' => $coach->id])->save()),
        };

        $this->actingAs($user)
            ->put(route('grades.update', $this->grade), gradePayload(['value' => 1.0]))
            ->assertForbidden();

        expect($this->grade->fresh()->value)->toBe('5.5');
    })->with(['another apprentice', 'the coach']);
});

describe('destroy', function () {
    beforeEach(function () {
        $this->actingAs($this->apprentice)->post(route('grades.store'), gradePayload());
        $this->grade = Grade::query()->sole();
    });

    test('an apprentice deletes a grade with its scan and comments', function () {
        $coach = User::factory()->coach()->create();
        $comment = $this->grade->comments()->forceCreate(['author_id' => $coach->id, 'body' => 'Bravo']);
        $path = $this->grade->file_path;

        $this->actingAs($this->apprentice)
            ->delete(route('grades.destroy', $this->grade))
            ->assertRedirect(route('grades.dashboard'));

        expect(Grade::query()->count())->toBe(0)
            ->and($comment->fresh())->toBeNull();
        Storage::assertMissing($path);
    });

    test('nobody else can delete the grade', function (string $who) {
        $user = match ($who) {
            'another apprentice' => makeApprentice($this->plan),
            'the coach' => tap(User::factory()->coach()->create(), fn (User $coach) => $this->apprentice->forceFill(['coach_id' => $coach->id])->save()),
        };

        $this->actingAs($user)
            ->delete(route('grades.destroy', $this->grade))
            ->assertForbidden();

        expect(Grade::query()->count())->toBe(1);
    })->with(['another apprentice', 'the coach']);

    test('the grade page offers edit and delete to its owner only', function () {
        $this->actingAs($this->apprentice)->get(route('grades.show', $this->grade))
            ->assertInertia(fn ($page) => $page
                ->where('can.update', true)
                ->where('can.delete', true)
                ->has('tree.nodes'));

        $coach = User::factory()->coach()->create();
        $this->apprentice->forceFill(['coach_id' => $coach->id])->save();

        $this->actingAs($coach)->get(route('grades.show', $this->grade))
            ->assertInertia(fn ($page) => $page
                ->where('can.update', false)
                ->where('can.delete', false)
                ->where('tree', null));
    });
});

describe('scan', function () {
    beforeEach(function () {
        $this->actingAs($this->apprentice)->post(route('grades.store'), gradePayload());
        $this->grade = Grade::query()->sole();
    });

    test('the owner sees the scan inline under the conventional name', function () {
        $response = $this->actingAs($this->apprentice)->get(route('grades.file', $this->grade))->assertOk();

        expect($response->headers->get('Content-Disposition'))
            ->toContain('inline')
            ->toContain('1_1_Math');
    });

    test('the grade page links to the scan', function () {
        $this->actingAs($this->apprentice)->get(route('grades.show', $this->grade))
            ->assertInertia(fn ($page) => $page->where('pdfUrl', route('grades.file', $this->grade)));
    });

    test('the apprentice\'s coach can open the scan', function () {
        $coach = User::factory()->coach()->create();
        $this->apprentice->forceFill(['coach_id' => $coach->id])->save();

        $this->actingAs($coach)->get(route('grades.file', $this->grade))->assertOk();
    });

    test('another apprentice cannot open the scan', function () {
        $this->actingAs(makeApprentice($this->plan))
            ->get(route('grades.file', $this->grade))
            ->assertForbidden();
    });

    test('a grade without a scan has no file and no link', function () {
        $this->grade->update(['file_path' => null]);

        $this->actingAs($this->apprentice)->get(route('grades.file', $this->grade))->assertNotFound();
        $this->actingAs($this->apprentice)->get(route('grades.show', $this->grade))
            ->assertInertia(fn ($page) => $page->where('pdfUrl', null));
    });
});

describe('file name', function () {
    test('it follows Jobtrek\'s convention, numbered per branch and semester', function () {
        $grade = fn (AverageNode $node, string $value, string $date): Grade => Grade::query()->create([
            'user_id' => $this->apprentice->id,
            'average_node_id' => $node->id,
            'value' => $value,
            'test_date' => $date,
        ]);

        $first = $grade($this->maths, '5.5', '2025-09-01');
        $english = $grade($this->english, '4.5', '2025-09-10');
        $second = $grade($this->maths, '5.0', '2025-10-01');
        $nextSemester = $grade($this->maths, '6.0', '2026-03-01');

        expect($first->fileName())->toBe('1_1_Mathématiques_5.5_Bastien Nicoud.pdf')
            ->and($english->fileName())->toBe('1_1_Anglais_4.5_Bastien Nicoud.pdf')
            ->and($second->fileName())->toBe('1_2_Mathématiques_5_Bastien Nicoud.pdf')
            ->and($nextSemester->fileName())->toBe('2_1_Mathématiques_6_Bastien Nicoud.pdf');
    });

    test('characters a file name cannot hold are replaced', function () {
        $node = AverageNode::query()->create(['name' => 'TPI / Documentation', 'parent_id' => $this->domain->id, 'weight' => 0.5]);
        $grade = Grade::query()->create([
            'user_id' => $this->apprentice->id,
            'average_node_id' => $node->id,
            'value' => '4.0',
            'test_date' => '2025-09-01',
        ]);

        expect($grade->fileName())->toBe('1_1_TPI - Documentation_4_Bastien Nicoud.pdf');
    });
});
