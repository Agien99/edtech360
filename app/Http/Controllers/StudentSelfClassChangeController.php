<?php

namespace App\Http\Controllers;

use App\Models\SchoolClass;
use App\Models\StudentClassEnrollment;
use App\Models\StudentProfile;
use App\Services\StudentSelfClassChangeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class StudentSelfClassChangeController extends Controller
{
    public function index(Request $request) {
        abort_unless($request->user()->hasRole('student'), 403);
        $student = StudentProfile::query()->where('user_id', $request->user()->id)->firstOrFail();
        Gate::forUser($request->user())->authorize('viewOwn', $student);
        $today = now()->toDateString();
        $enrollments = StudentClassEnrollment::query()->where('student_profile_id', $student->id)
            ->where('status', 'active')->whereDate('enrolled_at', '<=', $today)
            ->where(function ($q) use ($today) {$q->whereNull('ended_at')->orWhereDate('ended_at', '>=', $today);})
            ->with(['schoolClass', 'academicSession', 'semester'])->get();
        $current = $enrollments->count() === 1 ? $enrollments->first() : null;
        $window = $current ? DB::table('class_change_windows')->where('academic_session_id', $current->academic_session_id)->first() : null;
        $open = $window && $window->is_enabled && now()->betweenIncluded($window->opens_at, $window->closes_at);
        $classes = $current ? SchoolClass::query()->where('academic_session_id', $current->academic_session_id)
            ->where('batch_id', $current->schoolClass->batch_id)->where('status', 'active')
            ->whereKeyNot($current->class_id)->orderBy('code')->get() : collect();
        return view('class-change-windows.student', compact('current', 'window', 'open', 'classes'));
    }
    public function store(Request $request, StudentSelfClassChangeService $service) {
        $validated = $request->validate(['class_id' => ['required', 'integer', 'exists:classes,id']]);
        $service->change($request->user(), (int) $validated['class_id']);
        return back()->with('success', 'Your class has been changed.');
    }
}
