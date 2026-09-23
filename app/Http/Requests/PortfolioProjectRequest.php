<?php

namespace App\Http\Requests;

use App\Models\Project;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class PortfolioProjectRequest extends FormRequest
{
    /**
     * Shared by store and update: the bound project, when there is one, decides
     * which ProjectPolicy ability applies.
     */
    public function authorize(): bool
    {
        $user = $this->user();

        if ($user === null) {
            return false;
        }

        $project = $this->route('project');

        return $project instanceof Project
            ? $user->can('update', $project)
            : $user->can('create', Project::class);
    }

    /**
     * The form edits technologies as a tag list, but the column is a single
     * comma-separated string, so both shapes are accepted and normalized here.
     */
    protected function prepareForValidation(): void
    {
        $technologies = $this->input('technologies');

        if (is_array($technologies)) {
            $technologies = collect($technologies)
                ->filter(fn (mixed $technology): bool => is_string($technology) && trim($technology) !== '')
                ->map(fn (string $technology): string => trim($technology))
                ->unique()
                ->implode(', ');

            $this->merge(['technologies' => $technologies !== '' ? $technologies : null]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'organization' => ['nullable', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'responsibilities' => ['nullable', 'string', 'max:255'],
            'technologies' => ['nullable', 'string', 'max:255'],
            'repository_url' => ['nullable', 'url', 'max:255'],
            'demo_path' => ['nullable', 'url', 'max:255'],
            'date_start' => ['required', 'date'],
            'date_end' => ['nullable', 'date', 'after_or_equal:date_start'],
            'screenshots' => ['nullable', 'array'],
            'screenshots.*' => ['image', 'max:5120'],
            'skill_ids' => ['nullable', 'array'],
            'skill_ids.*' => ['integer', 'distinct', 'exists:skills,id'],
        ];
    }

    /**
     * The app has no French translation files yet and the form is in French.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'title.required' => 'Le titre du projet est obligatoire.',
            'description.required' => 'La description est obligatoire.',
            'date_start.required' => 'La date de début est obligatoire.',
            'date_start.date' => 'La date de début n\'est pas valide.',
            'date_end.date' => 'La date de fin n\'est pas valide.',
            'date_end.after_or_equal' => 'La date de fin doit suivre la date de début.',
            'repository_url.url' => 'Le lien doit être une URL complète (https://…).',
            'demo_path.url' => 'Le lien doit être une URL complète (https://…).',
            'technologies.max' => 'La liste des technologies ne doit pas dépasser :max caractères.',
            'skill_ids.*' => 'Une compétence sélectionnée n\'existe plus.',
            'screenshots.*' => 'Chaque capture doit être une image de 5 Mo maximum.',
            'max' => 'Ce champ ne doit pas dépasser :max caractères.',
        ];
    }
}
