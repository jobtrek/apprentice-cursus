<?php

namespace App\Services\Ocr;

use App\Enums\OcrSubject;
use Laravel\Ai\Classification;
use Laravel\Ai\Classification\Choice;
use Laravel\Ai\Responses\Data\ChoiceAnswer;

class JevService
{
    /**
     * @return array{subject: OcrSubject, confidence: float|null}
     */
    public function classify(mixed $data): array
    {
        $response = Classification::of($data)->questions([
            'subject' => new Choice(
                'Which school subject or IT module is the test in `first_page_text` for? Pick the subject being taught, not the language the test is written in: most tests are written in French.',
                OcrSubject::choices(),
            ),
        ])->classify(provider: 'typesafe', model: 'jev-1.13.0');

        /** @var ChoiceAnswer $answer */
        $answer = $response->answer('subject');

        // Confidence drives the flag rule (docs/ocr_specs/stack.md, "Flag rule").
        return ['subject' => OcrSubject::from($answer->choice), 'confidence' => $answer->confidence];
    }
}
