<?php

namespace App\Http\Controllers;

use App\Http\Requests\SubjectRequest;
use App\Models\AverageNode;
use App\Services\SubjectService;
use App\Support\SubjectList;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SubjectController extends Controller
{
    public function __construct(private readonly SubjectService $subjects) {}

    public function index(Request $request): Response
    {
        return Inertia::render('Subjects', SubjectList::for($request->query()));
    }

    public function store(SubjectRequest $request): RedirectResponse
    {
        $this->subjects->create($request->validated());

        return redirect()->route('subjects.index');
    }

    public function update(SubjectRequest $request, AverageNode $subject): RedirectResponse
    {
        abort_unless($this->subjects->isSubject($subject), 404);

        $this->subjects->update($subject, $request->validated());

        return redirect()->route('subjects.index');
    }

    public function destroy(AverageNode $subject): RedirectResponse
    {
        abort_unless($this->subjects->isSubject($subject), 404);

        $this->subjects->delete($subject);

        return redirect()->route('subjects.index');
    }
}
