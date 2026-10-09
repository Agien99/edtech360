<?php

namespace App\Http\Controllers;

use App\Models\StudentProfile;
use App\Services\StudentEnrollmentAccess;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ScopedStudentController extends Controller
{
    public function index(Request $request, StudentEnrollmentAccess $access): View
    {
        Gate::authorize('viewAny', StudentProfile::class);

        $students = $access->visibleTo($request->user())
            ->orderBy('full_name')
            ->paginate(15)
            ->withQueryString();

        return view('students.index', compact('students'));
    }
}
