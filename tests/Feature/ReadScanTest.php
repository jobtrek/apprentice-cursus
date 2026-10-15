<?php

use App\Models\AverageNode;
use App\Models\FormationPlan;
use App\Models\OcrScan;
use App\Models\User;
use App\Services\Ocr\GeminiService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Ai\Classification;
use Laravel\Ai\Responses\Data\ChoiceAnswer;

beforeEach(function () {
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
