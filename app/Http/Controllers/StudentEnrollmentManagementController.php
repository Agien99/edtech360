<?php
namespace App\Http\Controllers;

use App\Models\AcademicSession;
use App\Models\SchoolClass;
use App\Models\Semester;
use App\Models\StudentBatch;
use App\Models\StudentClassEnrollment;
use App\Models\StudentProfile;
use App\Services\StudentPlacementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;

class StudentEnrollmentManagementController extends Controller
{
    public function index(Request $request): View
    {
        $sessions = AcademicSession::query()->whereIn('status', ['planned','active'])
            ->with('batch')->orderByDesc('start_date')->get();
        $students = StudentProfile::query()->where('status', 'active')->orderBy('full_name')->get(['id','student_number','full_name']);
        $classes = SchoolClass::query()->where('status', 'active')
            ->with('academicSession:id,name')->orderBy('name')->get();
        $semesters = Semester::query()->where('status', '!=', 'closed')
            ->with('academicSession:id,name')->orderBy('number')->get();
        $memberships = StudentBatch::query()->with(['student:id,full_name,student_number', 'batch.academicSession'])
            ->latest('id')->paginate(15, ['*'], 'memberships_page');
        $enrollments = StudentClassEnrollment::query()->with([
            'student:id,student_number,full_name', 'academicSession:id,name',
            'schoolClass:id,code,name', 'semester:id,number,name',
        ])->latest('id')->paginate(15, ['*'], 'enrollments_page');
        return view('student-enrollments.index', compact('sessions','students','classes','semesters','memberships','enrollments'));
    }

    public function store(Request $request, StudentPlacementService $service): RedirectResponse
    {
        $validated = $request->validate([
            'student_profile_id' => ['required', 'integer', Rule::exists('student_profiles','id')->whereNull('deleted_at')],
            'class_id' => ['required', 'integer', Rule::exists('classes','id')->whereNull('deleted_at')],
            'semester_id' => ['required', 'integer', Rule::exists('semesters','id')],
            'enrolled_at' => ['required', 'date_format:Y-m-d'],
        ]);
        $service->place($request->user(), $validated);
        return redirect()->route('student-enrollments.index')->with('success', 'Student placed successfully.');
    }
}
