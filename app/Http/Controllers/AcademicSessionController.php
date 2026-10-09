<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAcademicSessionRequest;
use App\Models\AcademicSession;
use App\Services\AcademicSessionService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AcademicSessionController extends Controller
{
    public function index(Request $request): View
    {
        $sessions = AcademicSession::query()
            ->with([
                'semesters' => fn ($query) =>
                    $query->orderBy('number'),
            ])
            ->orderByDesc('start_date')
            ->paginate(10)
            ->withQueryString();

        return view(
            'academic-sessions.index',
            compact('sessions')
        );
    }

    public function store(
        StoreAcademicSessionRequest $request,
        AcademicSessionService $service
    ): RedirectResponse {
        $service->create(
            $request->user(),
            $request->validated()
        );

        return redirect()
            ->route('academic-sessions.index')
            ->with(
                'success',
                'Academic session created successfully.'
            );
    }
}