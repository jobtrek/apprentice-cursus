<?php

namespace App\Http\Requests;

use App\Models\Grade;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class CommentRequest extends FormRequest
{
    /**
     * GradePolicy::comment requires the permission, supervision of the apprentice
     * and an active apprentice account.
     */
    public function authorize(): bool
    {
        $grade = $this->route('grade');

        return $grade instanceof Grade
            && $this->user()?->can('comment', $grade) === true;
    }

    /**
     * 2000 matches the comments_body_length_check constraint.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'max:2000'],
        ];
    }

    /**
     * The app has no French translation files yet and the page is in French.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'body.required' => 'Le commentaire ne peut pas être vide.',
            'body.max' => 'Le commentaire ne doit pas dépasser :max caractères.',
        ];
    }
}
