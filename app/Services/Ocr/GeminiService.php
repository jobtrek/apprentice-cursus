<?php

namespace App\Services\Ocr;

use App\Enums\OcrSubject;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Attributes\MaxTokens;
use Laravel\Ai\Attributes\Model;
use Laravel\Ai\Attributes\Provider;
use Laravel\Ai\Attributes\Temperature;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Files\Image;
use Laravel\Ai\Promptable;
use Laravel\Ai\Responses\Data\FinishReason;
use Laravel\Ai\Responses\Data\TextUsage;
use Laravel\Ai\Responses\StructuredAgentResponse;

#[Provider('gemini')]
#[Model('gemini-3.5-flash-lite')]
#[Temperature(0)]
#[MaxTokens(8192)]
class GeminiService implements Agent, HasStructuredOutput
{
    use Promptable;

    public function instructions(): string
    {
        return '';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'text' => $schema->string()->required(),
            'subject' => $schema->string()->enum(array_column(OcrSubject::cases(), 'value'))->required(),
            'grade' => $schema->number()->nullable()->required(),
            'grade_box' => $schema->array()->items($schema->integer())->min(4)->max(4)->nullable()->required(),
        ];
    }

    /**
     * @return array{text: string|null, subject: string|null, grade: float|null, grade_box: list<int>|null, truncated: bool, usage: TextUsage}
     */
    public function read(string $png): array
    {
        /** @var StructuredAgentResponse $response */
        $response = $this->prompt(
            (string) file_get_contents(resource_path('prompts/ocr.md')),
            [Image::fromBase64(base64_encode($png), 'image/png')],
        );

        $data = $response->toArray();

        return [
            'text' => $data['text'] ?? null,
            'subject' => $data['subject'] ?? null,
            'grade' => isset($data['grade']) ? (float) $data['grade'] : null,
            'grade_box' => $data['grade_box'] ?? null,
            'truncated' => $response->steps->last()?->finishReason !== FinishReason::Stop,
            'usage' => $response->usage,
        ];
    }
}
