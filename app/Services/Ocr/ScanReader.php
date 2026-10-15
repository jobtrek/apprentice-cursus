<?php

namespace App\Services\Ocr;

use App\Models\AverageNode;
use App\Models\Grade;
use App\Models\OcrScan;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use Imagick;

/**
 * Reads a test PDF: Gemini finds the grade, Jev classifies the subject, and the
 * subject is matched to a leaf of the apprentice's plan. Every reading is
 * recorded as an OcrScan, for the OCR dashboard.
 */
class ScanReader
{
    /** Pages 2–3 are read only when the page before has no grade or was cut off. */
    private const MAX_PAGES = 3;

    public function __construct(
        private readonly GeminiService $gemini,
        private readonly JevService $jev,
    ) {}

    // ponytail: synchronous; the cache and queue are in docs/ocr_specs/tasks.md.
    public function read(UploadedFile $file, User $user): OcrScan
    {
        $start = hrtime(true);
        $prices = config('ocr.prices');

        $path = $file->getRealPath();
        $pdf = new Imagick;
        $pdf->pingImage($path);
        $pages = min(self::MAX_PAGES, $pdf->getNumberImages());

        $firstText = null;
        $grade = null;
        $geminiCost = 0.0;
        $pagesRead = 0;

        for ($n = 0; $n < $pages; $n++) {
            $reading = $this->gemini->read($this->pagePng($path, $n));
            $firstText ??= $reading['text'];
            $grade = $reading['grade'];
            $pagesRead = $n + 1;
            $geminiCost += ((float) $reading['usage']->inputTokens * $prices['gemini']['input']
                + (float) $reading['usage']->outputTokens * $prices['gemini']['output']) / 1e6;

            if ($grade !== null && ! $reading['truncated']) {
                break;
            }
        }

        $classification = $this->jev->classify(['first_page_text' => (string) $firstText]);

        if ($grade !== null && Validator::make(['value' => $grade], ['value' => Grade::VALUE_RULES])->fails()) {
            $grade = null;
        }

        return OcrScan::query()->create([
            'user_id' => $user->id,
            'subject' => $classification['subject']->value,
            'suggested_node_id' => $this->leafFor($user, $classification['subject']->branchName())?->id,
            'grade' => $grade,
            'confidence' => $classification['confidence'],
            'flagged' => $grade === null || ($classification['confidence'] ?? 0) < 0.5,
            'pages_read' => $pagesRead,
            'gemini_cost' => $geminiCost,
            'jev_cost' => (float) $classification['usage']->inputTokens * $prices['jev']['input'] / 1e6,
            'duration_ms' => (int) round((float) (hrtime(true) - $start) / 1e6),
        ]);
    }

    private function pagePng(string $path, int $n): string
    {
        $page = new Imagick;
        $page->setResolution(200, 200);
        $page->readImage("{$path}[{$n}]");
        $page->setImageBackgroundColor('white');
        $page = $page->mergeImageLayers(Imagick::LAYERMETHOD_FLATTEN);
        $page->setImageFormat('png');

        return $page->getImageBlob();
    }

    /** The leaf of the apprentice's plan named after the subject, if there is one. */
    private function leafFor(User $user, ?string $branchName): ?AverageNode
    {
        if ($branchName === null) {
            return null;
        }

        return $user->formationPlan?->averageNodes()
            ->where('name', $branchName)
            ->whereDoesntHave('children')
            ->first();
    }
}
