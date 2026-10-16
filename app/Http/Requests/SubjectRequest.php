<?php

namespace App\Http\Requests;

use App\Enums\Permission;
use App\Models\AverageNode;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SubjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(Permission::SubjectsManage->value) === true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'parent_id' => [
                'required',
                'integer',
                Rule::exists('average_nodes', 'id')->where(
                    fn ($query) => $query->whereIn('id', AverageNode::query()->select('parent_id')->whereNotNull('parent_id')),
                ),
            ],
            // Percent of the parent's average; the gradebook normalizes weights by their sum.
            'weight' => ['required', 'integer', 'between:1,100'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Le nom de la matière est obligatoire.',
            'name.max' => 'Le nom ne doit pas dépasser :max caractères.',
            'parent_id.required' => 'Choisissez le domaine de la matière.',
            'parent_id.exists' => 'Ce domaine est introuvable.',
            'weight.required' => 'La pondération est obligatoire.',
            'weight.between' => 'La pondération doit être comprise entre :min et :max %.',
        ];
    }
}
