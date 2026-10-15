<?php

namespace App\Http\Requests;

use App\Models\AverageNode;
use App\Models\Grade;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class GradeRequest extends FormRequest
{
    /** 10 MB, in kilobytes for the `max` file rule. */
    public const MAX_FILE_KB = 10240;

    /**
     * Shared by store and update: the bound grade, when there is one, decides
     * which GradePolicy ability applies.
     */
    public function authorize(): bool
    {
        $user = $this->user();

        if ($user === null) {
            return false;
        }

        $grade = $this->grade();

        return $grade !== null ? $user->can('update', $grade) : $user->can('create', Grade::class);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // A node of the apprentice's own plan; that it is a leaf is checked in after().
            'average_node_id' => [
                'required',
                'integer',
                Rule::exists('average_node_formation_plan', 'average_node_id')
                    ->where('formation_plan_id', $this->user()?->formation_plan_id),
            ],
            'value' => ['required', ...Grade::VALUE_RULES],
            'test_date' => ['required', 'date', 'before_or_equal:today'],
            'is_oral' => ['boolean'],
            // An oral exam has nothing to scan: a file sent with it is dropped. On update,
            // a written test keeps its scan unless a new one replaces it.
            'file' => [
                'exclude_if:is_oral,true',
                Rule::requiredIf($this->grade()?->file_path === null),
                'nullable',
                'file',
                'mimetypes:application/pdf',
                'max:'.self::MAX_FILE_KB,
            ],
            // Set when the grade comes from the OCR dialog; only feeds the OCR dashboard.
            'ocr_scan_id' => ['nullable', 'integer'],
        ];
    }

    /**
     * Grades go on leaves only: an inner node's average comes from its children.
     *
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->has('average_node_id')) {
                    return;
                }

                $hasChildren = AverageNode::query()
                    ->where('parent_id', $this->integer('average_node_id'))
                    ->exists();

                if ($hasChildren) {
                    $validator->errors()->add('average_node_id', 'Choisissez une branche précise, pas un domaine.');
                }
            },
        ];
    }

    /** Null on store. */
    private function grade(): ?Grade
    {
        $grade = $this->route('grade');

        return $grade instanceof Grade ? $grade : null;
    }

    /**
     * The app has no French translation files yet and the form is in French.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'average_node_id.required' => 'Sélectionnez une branche.',
            'average_node_id.*' => 'Cette branche ne fait pas partie de votre formation.',
            'value.required' => 'La note est obligatoire.',
            'value.*' => 'La note doit être comprise entre 1.0 et 6.0, par pas de 0.5.',
            'test_date.required' => 'La date du test est obligatoire.',
            'test_date.date' => 'La date du test n\'est pas valide.',
            'test_date.before_or_equal' => 'La date du test ne peut pas être dans le futur.',
            'file.required' => 'Ajoutez le scan du test, ou indiquez une épreuve orale.',
            'file.uploaded' => 'Le fichier n\'a pas pu être envoyé, réessayez.',
            'file.max' => 'Le justificatif ne doit pas dépasser 10 Mo.',
            'file.*' => 'Le justificatif doit être un fichier PDF.',
        ];
    }
}
