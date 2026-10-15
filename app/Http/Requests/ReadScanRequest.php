<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ReadScanRequest extends FormRequest
{
    /**
     * Access is enforced by the route's `can:create,Grade` middleware.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'consent' => ['accepted'],
            'file' => ['required', 'file', 'mimetypes:application/pdf', 'max:'.GradeRequest::MAX_FILE_KB],
        ];
    }
}
