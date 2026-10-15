<?php

use App\Models\FormationPlan;
use App\Services\Ocr\GeminiService;
use Illuminate\Http\UploadedFile;
use Laravel\Ai\Classification;
use Laravel\Ai\Responses\Data\ChoiceAnswer;

beforeEach(function () {
    $this->apprentice = makeApprentice(section(FormationPlan::IT));
});

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
