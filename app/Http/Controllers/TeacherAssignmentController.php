<?php
namespace App\Http\Controllers;
use App\Models\AcademicSession;
use App\Models\SchoolClass;
use App\Models\Semester;
use App\Models\Subject;
use App\Models\SubjectGroup;
use App\Models\SubjectGroupMember;
use App\Models\TeacherClassAssignment;
use App\Models\TeacherProfile;
use App\Models\TeacherSubjectClass;
use App\Services\TeacherAssignmentService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class TeacherAssignmentController extends Controller
{
    public function index(Request $request): View
    {
        $canManage = $request->user()->can('teachers.assign') || $request->user()->can('classes.assign_teachers');
        abort_unless($canManage || $request->user()->can('teachers.view'),403);
        $sessions=AcademicSession::query()->whereIn('status',['planned','active'])->orderByDesc('start_date')->get();
        $classes=SchoolClass::query()->with('academicSession:id,name')->where('status','active')->orderBy('name')->get();
        $teachers=TeacherProfile::query()->with('user:id,name,is_active')->where('status','active')->orderBy('full_name')->get();
        $semesters=Semester::query()->with('academicSession:id,name')->whereIn('status',['planned','active'])->orderBy('number')->get();
        $subjects=Subject::query()->where('is_active',true)->orderBy('name')->get();
        $groups=SubjectGroup::query()->where('is_active',true)->orderBy('name')->get();
        $classAssignments=TeacherClassAssignment::query()->with(['teacher','schoolClass.academicSession'])
            ->orderByDesc('id')->paginate(10,['*'],'classes_page');
        $subjectAssignments=TeacherSubjectClass::query()->with(['teacher','schoolClass','subject','semester'])
            ->orderByDesc('id')->paginate(10,['*'],'subjects_page');
        $groupAssignments=SubjectGroupMember::query()->with(['teacher','subjectGroup','academicSession'])
            ->orderByDesc('id')->paginate(10,['*'],'groups_page');
        return view('teacher-assignments.index',compact('sessions','classes','teachers','semesters','subjects','groups',
            'classAssignments','subjectAssignments','groupAssignments','canManage'));
    }
    public function storeClass(Request $request, TeacherAssignmentService $service): RedirectResponse
    {
        abort_unless($request->user()->can('classes.assign_teachers'),403);
        $data=$request->validate([
            'teacher_profile_id'=>['required','integer',Rule::exists('teacher_profiles','id')],
            'class_id'=>['required','integer',Rule::exists('classes','id')],
            'position'=>['required',Rule::in(['class_teacher','assistant_class_teacher'])],
            'start_date'=>['nullable','date_format:Y-m-d'], 'end_date'=>['nullable','date_format:Y-m-d'],
        ]);
        $service->assignClass($request->user(),$data);
        return back()->with('success','Class responsibility assigned.');
    }
    public function storeSubject(Request $request, TeacherAssignmentService $service): RedirectResponse
    {
        abort_unless($request->user()->can('teachers.assign'),403);
        $data=$request->validate([
            'teacher_profile_id'=>['required','integer',Rule::exists('teacher_profiles','id')],
            'class_id'=>['required','integer',Rule::exists('classes','id')],
            'semester_id'=>['required','integer',Rule::exists('semesters','id')],
            'subject_id'=>['required','integer',Rule::exists('subjects','id')],
            'start_date'=>['nullable','date_format:Y-m-d'], 'end_date'=>['nullable','date_format:Y-m-d'],
        ]);
        $service->assignSubject($request->user(),$data);
        return back()->with('success','Subject teaching assignment created.');
    }
    public function storeGroup(Request $request, TeacherAssignmentService $service): RedirectResponse
    {
        abort_unless($request->user()->can('teachers.assign') && $request->user()->can('subject_groups.manage'),403);
        $data=$request->validate([
            'teacher_profile_id'=>['required','integer',Rule::exists('teacher_profiles','id')],
            'academic_session_id'=>['required','integer',Rule::exists('academic_sessions','id')],
            'subject_group_id'=>['required','integer',Rule::exists('subject_groups','id')],
            'position'=>['required',Rule::in(['member','coordinator','head'])],
            'start_date'=>['nullable','date_format:Y-m-d'], 'end_date'=>['nullable','date_format:Y-m-d'],
        ]);
        $service->assignGroup($request->user(),$data);
        return back()->with('success','Subject group responsibility created.');
    }
    public function end(Request $request, string $kind, int $id, TeacherAssignmentService $service): RedirectResponse
    {
        $authorized = match ($kind) {
            'class' => $request->user()->can('classes.assign_teachers'),
            'subject' => $request->user()->can('teachers.assign'),
            'group' => $request->user()->can('teachers.assign') && $request->user()->can('subject_groups.manage'),
            default => false,
        };
        abort_unless($authorized,403);
        $service->end($request->user(),$kind,$id);
        return back()->with('success','Assignment ended.');
    }
}
