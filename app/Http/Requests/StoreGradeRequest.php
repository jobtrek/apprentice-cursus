<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreGradeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'matiere' => ['required', 'string', 'max:255'],

            'date-month' => ['required', 'integer', 'between:1,12'],
            'date-day' => ['required', 'integer', 'between:1,31'],
            'date-year' => ['required', 'integer', 'digits:4'],

            'note' => [
                'required',
                'numeric',
                'between:1,6',
                'multiple_of:0.5',
            ],

            'file' => [
                'nullable',
                'file',
                'mimes:pdf',
                'max:10240',
            ],
        ];
    }
}
