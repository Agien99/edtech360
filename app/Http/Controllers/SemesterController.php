<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSemesterRequest;
use App\Http\Requests\UpdateSemesterRequest;
use App\Models\AcademicSession;
use App\Models\Semester;
use App\Services\SemesterManagementService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SemesterController extends Controller
{
    public function index(
        Request $request,
        AcademicSession $academicSession
    ): View {
        $semesters = $academicSession->semesters()
            ->orderBy('number')
            ->get();

        return view('semesters.index', [
            'academicSession' => $academicSession,
            'semesters' => $semesters,
        ]);
    }

    public function store(
        StoreSemesterRequest $request,
        AcademicSession $academicSession,
        SemesterManagementService $service
    ): RedirectResponse {
        $service->create(
            $request->user(),
            $academicSession,
            $request->validated()
        );

        return redirect()
            ->route(
                'academic-sessions.semesters.index',
                $academicSession
            )
            ->with('success', 'Semester created successfully.');
    }

    public function update(
        UpdateSemesterRequest $request,
        AcademicSession $academicSession,
        Semester $semester,
        SemesterManagementService $service
    ): RedirectResponse {
        abort_unless(
            $semester->academic_session_id === $academicSession->id,
            404
        );

        $service->update(
            $request->user(),
            $academicSession,
            $semester,
            $request->validated()
        );

        return redirect()
            ->route(
                'academic-sessions.semesters.index',
                $academicSession
            )
            ->with('success', 'Semester updated successfully.');
    }
}