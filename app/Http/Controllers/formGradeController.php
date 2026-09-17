<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Http\Requests\StoreGradeRequest;
use App\Models\Grade;
use App\Models\EvaluationNode;
use App\Models\User;

class formGradeController extends Controller
{
    public function store(StoreGradeRequest $request)
    {
        $validatedData = $request->validated();

        $date  = \Carbon\Carbon::createFromDate($validatedData['date-year'], $validatedData['date-month'], $validatedData['date-day']);

        $semester = $validatedData['date-month'] <= 6 ? 2 : 1;
        if ($validatedData['file']) {
            $file = $request->file('file');
            $fileName = $date->format('Y-m-d') . '_' . $semester . '_' . $validatedData['note'] . '_' . $validatedData['matiere'] . '_' . $User->name();
            $path = $file->storeAs($User->id() . '/grades', $fileName, 'public');
        }

        $grade = $Grade->create([
            'user_id' => $User->id(),
            'evaluation_node_id' => $validatedData['matiere'],
            'value' => $validatedData['note'],
            'test_date' => $date,
            'semester' => $semester,
            'file_path' => isset($path) ? $path : null,
            'original_filename' => isset($file) ? $file->getClientOriginalName() : null,
        ]);
    }
}
