<?php

use App\Models\AverageNode;
use App\Models\FormationPlan;
use App\Models\Grade;
use App\Models\OcrScan;
use App\Models\User;
use App\Services\Ocr\GeminiService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Ai\Classification;
use Laravel\Ai\Responses\Data\ChoiceAnswer;

beforeEach(function () {
    config(['ocr.enabled' => true]);
    Storage::fake();
    $this->apprentice = makeApprentice(section(FormationPlan::IT));
});

/** A root and one leaf in the apprentice's plan, so a grade can be stored. */
function leafOf(User $apprentice): AverageNode
{
    $root = AverageNode::query()->create(['name' => 'Root']);
    $leaf = AverageNode::query()->create(['name' => 'Anglais', 'parent_id' => $root->id, 'weight' => 1]);
    $apprentice->formationPlan->averageNodes()->attach([$root->id, $leaf->id]);

    return $leaf;
}

/** A one-page blank PDF, so Imagick has a real page to render. */
function blankPdf(): UploadedFile
{
    $image = new Imagick;
    $image->newImage(100, 100, 'white');
    $image->setImageFormat('pdf');

    return UploadedFile::fake()->createWithContent('scan.pdf', $image->getImageBlob());
}

test('a scan is read by Gemini and classified by Jev', function () {
    GeminiService::fake([['text' => 'English Test', 'subject' => 'subjects/Anglais', 'grade' => 5.0, 'grade_box' => null]]);
    Classification::fake([['subject' => new ChoiceAnswer('subjects/Anglais', ['subjects/Anglais' => 0.9], 0.9)]]);

    $this->actingAs($this->apprentice)
        ->postJson(route('grades.read-scan'), ['consent' => true, 'file' => blankPdf()])
        ->assertOk()
        ->assertJson(['subject' => 'subjects/Anglais', 'confidence' => 0.9, 'grade' => 5, 'flagged' => false])
        ->assertJsonStructure(['duration_ms']);
});

test('scans are rate limited per user', function () {
    GeminiService::fake(array_fill(0, 10, ['text' => 'English Test', 'subject' => 'subjects/Anglais', 'grade' => 5.0, 'grade_box' => null]))->preventStrayPrompts();
    Classification::fake(array_fill(0, 10, ['subject' => new ChoiceAnswer('subjects/Anglais', ['subjects/Anglais' => 0.9], 0.9)]));

    foreach (range(1, 10) as $i) {
        $this->actingAs($this->apprentice)
            ->postJson(route('grades.read-scan'), ['consent' => true, 'file' => blankPdf()])
            ->assertOk();
    }

    $this->actingAs($this->apprentice)
        ->postJson(route('grades.read-scan'), ['consent' => true, 'file' => blankPdf()])
        ->assertJsonValidationErrors('file');

    expect(OcrScan::query()->count())->toBe(10);
});

test('a scan is not read while the OCR is disabled', function () {
    config(['ocr.enabled' => false]);
    GeminiService::fake()->preventStrayPrompts();

    $this->actingAs($this->apprentice)
        ->postJson(route('grades.read-scan'), ['consent' => true, 'file' => blankPdf()])
        ->assertNotFound();
});

test('a scan is not read without consent', function () {
    GeminiService::fake()->preventStrayPrompts();

    $this->actingAs($this->apprentice)
        ->postJson(route('grades.read-scan'), ['consent' => false, 'file' => blankPdf()])
        ->assertJsonValidationErrors('consent');
});

test('a scan is recorded and linked to the grade saved from it', function () {
    GeminiService::fake([['text' => 'English Test', 'subject' => 'subjects/Anglais', 'grade' => 5.0, 'grade_box' => null]]);
    Classification::fake([['subject' => new ChoiceAnswer('subjects/Anglais', ['subjects/Anglais' => 0.9], 0.9)]]);

    $scanId = $this->actingAs($this->apprentice)
        ->postJson(route('grades.read-scan'), ['consent' => true, 'file' => blankPdf()])
        ->json('scan_id');

    $scan = OcrScan::query()->findOrFail($scanId);
    expect($scan->user_id)->toBe($this->apprentice->id)
        ->and($scan->pages_read)->toBe(1)
        ->and($scan->grade)->toBe('5.0')
        ->and($scan->grade_id)->toBeNull();

    $node = leafOf($this->apprentice);

    $this->post(route('grades.store'), [
        'average_node_id' => $node->id,
        'value' => 4.5,
        'test_date' => '2026-01-15',
        'file' => blankPdf(),
        'ocr_scan_id' => $scanId,
        'ocr_node_id' => $node->id,
    ])->assertSessionHasNoErrors();

    expect($scan->refresh()->grade_id)->not->toBeNull()
        ->and($scan->suggested_node_id)->toBe($node->id);
});

test('another apprentice cannot claim a scan', function () {
    $scan = OcrScan::query()->create([
        'user_id' => makeApprentice()->id, 'subject' => 'OTHER', 'flagged' => true,
        'pages_read' => 1, 'gemini_cost' => 0, 'jev_cost' => 0, 'duration_ms' => 1,
    ]);
    $node = leafOf($this->apprentice);

    $this->actingAs($this->apprentice)->post(route('grades.store'), [
        'average_node_id' => $node->id, 'value' => 4.5, 'test_date' => '2026-01-15',
        'file' => blankPdf(), 'ocr_scan_id' => $scan->id,
    ]);

    expect($scan->refresh()->grade_id)->toBeNull();
});

test('only the local admin sees the OCR dashboard', function () {
    $this->actingAs($this->apprentice)->get(route('ocr-scans.index'))->assertForbidden();

    app()->detectEnvironment(fn () => 'local');

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('ocr-scans.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('OcrScans')->where('stats.scans', 0));
});

test('the OCR dashboard bins scans for the charts', function () {
    app()->detectEnvironment(fn () => 'local');
    $base = ['user_id' => $this->apprentice->id, 'flagged' => false, 'pages_read' => 1, 'gemini_cost' => 0.01, 'jev_cost' => 0.002];
    OcrScan::query()->create([...$base, 'subject' => 'OTHER', 'confidence' => 1.0, 'duration_ms' => 45_000]);
    OcrScan::query()->create([...$base, 'subject' => 'OTHER', 'confidence' => 0.42, 'duration_ms' => 3_500]);
    // A confidence on a bin edge lands in the bin above.
    OcrScan::query()->create([...$base, 'subject' => 'OTHER', 'confidence' => 0.3, 'duration_ms' => 1_000]);

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('ocr-scans.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('charts.confidence.3', ['label' => '30–40 %', 'count' => 1, 'warn' => true])
            ->where('charts.confidence.4.count', 1)
            ->where('charts.confidence.9', ['label' => '90–100 %', 'count' => 1, 'warn' => false])
            ->where('charts.duration.1.count', 1)
            ->where('charts.duration.15', ['label' => '30 s+', 'count' => 1, 'warn' => true])
            ->has('charts.spend', 30)
            ->where('charts.spend.29.gemini', 0.03)
            ->etc());
});

test('the OCR dashboard splits saved scans by what the apprentice corrected', function () {
    app()->detectEnvironment(fn () => 'local');
    $node = leafOf($this->apprentice);
    $saved = fn (string $value): int => Grade::query()->create([
        'user_id' => $this->apprentice->id, 'average_node_id' => $node->id, 'value' => $value, 'test_date' => '2026-01-15',
    ])->id;
    $scan = fn (array $attributes) => (new OcrScan)->forceFill([
        'user_id' => $this->apprentice->id, 'subject' => 'subjects/Anglais', 'flagged' => false,
        'pages_read' => 1, 'gemini_cost' => 0.01, 'jev_cost' => 0.002, 'duration_ms' => 4_000, ...$attributes,
    ])->save();

    $scan(['grade' => '5.0', 'grade_id' => $saved('5.0'), 'suggested_node_id' => $node->id]);
    $scan(['grade' => '4.0', 'grade_id' => $saved('4.5'), 'suggested_node_id' => $node->id]);
    // Branch not recognised, picked by the apprentice: no suggestion stored.
    $scan(['grade' => '5.0', 'grade_id' => $saved('5.0'), 'flagged' => true]);
    $scan(['grade' => '5.0']);

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('ocr-scans.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('charts.accuracy', ['correct' => 1, 'wrongGrade' => 1, 'wrongBranch' => 1])
            ->where('stats.saved', 3)
            ->where('stats.flaggedRate', 0.25)
            ->where('stats.unflaggedAccuracy', 0.5)
            ->where('stats.averageCost', 0.012)
            ->where('stats.averageDurationMs', 4000)
            ->etc());
});

test('a grade saved on a picked branch stores no OCR suggestion', function () {
    $scan = OcrScan::query()->create([
        'user_id' => $this->apprentice->id, 'subject' => 'OTHER', 'flagged' => true,
        'pages_read' => 1, 'gemini_cost' => 0, 'jev_cost' => 0, 'duration_ms' => 1,
    ]);
    $node = leafOf($this->apprentice);

    $this->actingAs($this->apprentice)->post(route('grades.store'), [
        'average_node_id' => $node->id, 'value' => 4.5, 'test_date' => '2026-01-15',
        'file' => blankPdf(), 'ocr_scan_id' => $scan->id, 'ocr_node_id' => null,
    ])->assertSessionHasNoErrors();

    expect($scan->refresh()->grade_id)->not->toBeNull()
        ->and($scan->suggested_node_id)->toBeNull();
});
