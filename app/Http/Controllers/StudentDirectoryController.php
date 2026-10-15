<?php
namespace App\Http\Controllers;

use App\Models\StudentProfile;
use App\Models\User;
use App\Services\StudentAuditService;
use App\Services\StudentEnrollmentAccess;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class StudentDirectoryController extends Controller
{
    public function index(Request $request, StudentEnrollmentAccess $access): View
    {
        Gate::authorize('viewAny', StudentProfile::class);
        $query = $access->visibleTo($request->user())->with(['user:id,name,email,is_active', 'batchMemberships.batch.academicSession']);
        if ($search = trim((string) $request->query('q', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', '%'.$search.'%')
                    ->orWhere('student_number', 'like', '%'.$search.'%')
                    ->orWhere('ic_number', 'like', '%'.$search.'%')
                    ->orWhereHas('user', fn ($u) => $u->where('email', 'like', '%'.$search.'%'));
            });
        }
        if ($intake = $request->query('intake')) {
            $query->whereHas('batchMemberships.batch', fn ($b) => $b->where('academic_session_id', $intake));
        }
        $students = $query->latest('id')->paginate(15)->withQueryString();
        $intakes = \App\Models\AcademicSession::query()->orderByDesc('start_date')->get(['id', 'name']);
        return view('students.index', compact('students', 'intakes'));
    }

    public function show(Request $request, StudentProfile $student, StudentEnrollmentAccess $access): View
    {
        abort_unless($access->canView($request->user(), $student), 403);
        $student->load(['user', 'batchMemberships.batch.academicSession', 'classEnrollments.schoolClass', 'classEnrollments.semester', 'classEnrollments.academicSession']);
        $canAdmin = $this->canAdmin($request->user());
        return view('students.show', compact('student', 'canAdmin'));
    }

    public function update(Request $request, StudentProfile $student, StudentAuditService $audit): RedirectResponse
    {
        abort_unless($this->canAdmin($request->user()), 403);
        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:150'],
            'ic_number' => ['required', 'regex:/^\d{6}-?\d{2}-?\d{4}$/'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'student_number' => ['nullable', 'string', 'max:50'],
        ]);
        $data['ic_number'] = preg_replace('/\D/', '', $data['ic_number']);
        $data['email'] = mb_strtolower(trim($data['email']));
        $request->validate([
            'ic_number' => [
                'required',
                Rule::unique('student_profiles', 'ic_number')->ignore($student->id),
            ],
            'student_number' => [
                'nullable',
                Rule::unique('student_profiles', 'student_number')->ignore($student->id),
            ],
            'email' => [
                'required',
                Rule::unique('users', 'email')->ignore($student->user_id),
            ],
        ]);
        DB::transaction(function () use ($request, $student, $data, $audit) {
            $locked = StudentProfile::query()->lockForUpdate()->findOrFail($student->id);
            $user = User::query()->lockForUpdate()->findOrFail($locked->user_id);
            $old = ['full_name' => $locked->full_name, 'ic_number' => $locked->ic_number, 'student_number' => $locked->student_number, 'phone' => $locked->phone, 'email' => $user->email];
            $locked->fill(collect($data)->only(['full_name','ic_number','student_number','phone'])->all());
            $locked->save();
            $user->name = $locked->full_name;
            $user->email = $data['email'];
            $user->save();
            $new = ['full_name' => $locked->full_name, 'ic_number' => $locked->ic_number, 'student_number' => $locked->student_number, 'phone' => $locked->phone, 'email' => $user->email];
            if ($old !== $new) $audit->record($request->user(), $locked, 'admin_update', $old, $new);
        }, 3);
        return back()->with('success', 'Student details updated.');
    }

    public function status(Request $request, StudentProfile $student, StudentAuditService $audit): RedirectResponse
    {
        abort_unless($this->canAdmin($request->user()), 403);
        $data = $request->validate(['status' => ['required', Rule::in(['active', 'inactive'])]]);
        DB::transaction(function () use ($request, $student, $audit, $data) {
            $locked = StudentProfile::query()->lockForUpdate()->findOrFail($student->id);
            $user = User::query()->lockForUpdate()->findOrFail($locked->user_id);
            $old = ['student_status' => $locked->status, 'account_active' => $user->is_active];
            $locked->status = $data['status'];
            $locked->save();
            $user->is_active = $data['status'] === 'active';
            $user->save();
            $new = ['student_status' => $locked->status, 'account_active' => $user->is_active];
            if ($old !== $new) $audit->record($request->user(), $locked, 'admin_status', $old, $new);
        }, 3);
        return back()->with('success', 'Student account status updated.');
    }

    private function canAdmin(User $user): bool
    {
        return (bool) $user->is_active && $user->hasAnyRole(['super_admin', 'school_admin']) && $user->can('students.update');
    }
}
