<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreGradeRequest;
use App\Models\Grade;
use Illuminate\Support\Facades\Auth;

class FormGradeController extends Controller
{
    public function store(StoreGradeRequest $request)
    {
        $validatedData = $request->validated();

        $user = Auth::user();

        $date = \Carbon\Carbon::createFromDate(
            $validatedData['date-year'],
            $validatedData['date-month'],
            $validatedData['date-day']
        );

        $semester = $validatedData['date-month'] <= 6 ? 2 : 1;

        $path = null;
        $file = null;

        if ($request->hasFile('file')) {
            $file = $request->file('file');

            $fileName = $date->format('Y-m-d')
                . '_' . $semester
                . '_' . $validatedData['note']
                . '_' . $validatedData['matiere']
                . '_' . $user->name
                . '.' . $file->getClientOriginalExtension();

            $path = $file->storeAs(
                $user->id . '/grades',
                $fileName,
                'public'
            );
        }

        $grade = Grade::create([
            'user_id' => $user->id,
            'evaluation_node_id' => $validatedData['matiere'],
            'value' => $validatedData['note'],
            'test_date' => $date,
            'semester' => $semester,
            'file_path' => $path,
            'original_filename' => $file?->getClientOriginalName(),
        ]);

        return redirect()->back();
    }
}
