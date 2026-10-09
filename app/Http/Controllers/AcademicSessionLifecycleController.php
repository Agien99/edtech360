<?php

namespace App\Http\Controllers;

use App\Models\AcademicSession;
use App\Services\AcademicSessionLifecycleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AcademicSessionLifecycleController extends Controller
{
    public function activate(
        Request $request,
        AcademicSession $academicSession,
        AcademicSessionLifecycleService $service
    ): RedirectResponse {
        $service->activate($request->user(), $academicSession);

        return redirect()
            ->route('academic-sessions.index')
            ->with('success', 'Academic session activated.');
    }

    public function advanceSemester(
        Request $request,
        AcademicSession $academicSession,
        AcademicSessionLifecycleService $service
    ): RedirectResponse {
        $service->advanceSemester($request->user(), $academicSession);

        return redirect()
            ->route('academic-sessions.semesters.index', $academicSession)
            ->with('success', 'Semester progression completed.');
    }

    public function close(
        Request $request,
        AcademicSession $academicSession,
        AcademicSessionLifecycleService $service
    ): RedirectResponse {
        $service->close($request->user(), $academicSession);

        return redirect()
            ->route('academic-sessions.index')
            ->with('success', 'Academic session closed.');
    }
}