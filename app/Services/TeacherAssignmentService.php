<?php
namespace App\Services;

use App\Models\AcademicSession;
use App\Models\SchoolClass;
use App\Models\Semester;
use App\Models\Subject;
use App\Models\SubjectGroup;
use App\Models\SubjectGroupMember;
use App\Models\TeacherClassAssignment;
use App\Models\TeacherProfile;
use App\Models\TeacherSubjectClass;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class TeacherAssignmentService
{
    private function fail(string $field, string $message): never
    {
        throw ValidationException::withMessages([$field => $message]);
    }
    private function teacher(int $id): TeacherProfile
    {
        $teacher = TeacherProfile::query()->with('user')->lockForUpdate()->findOrFail($id);
        if ($teacher->status !== 'active' || ! $teacher->user?->is_active) {
            $this->fail('teacher_profile_id', 'Teacher profile and linked account must be active.');
        }
        return $teacher;
    }
    private function session(int $id): AcademicSession
    {
        $session = AcademicSession::query()->lockForUpdate()->findOrFail($id);
        if (! in_array($session->status, ['planned', 'active'], true)) {
            $this->fail('academic_session_id', 'Academic session must be planned or active.');
        }
        return $session;
    }
    private function schoolClass(int $id): SchoolClass
    {
        $class = SchoolClass::query()->lockForUpdate()->findOrFail($id);
        $session = $this->session($class->academic_session_id);
        if ($class->status !== 'active' || ! $session->batch || $session->batch->status !== 'active') {
            $this->fail('class_id', 'Select an active class with an active linked batch.');
        }
        return $class;
    }
    private function dates(?string $start, ?string $end, string $lower, string $upper): array
    {
        $start ??= $lower;
        $end ??= $upper;
        if ($start < $lower || $end > $upper || $start > $end) {
            $this->fail('start_date', 'Assignment date range must fit the academic period.');
        }
        return [$start, $end];
    }
    private function overlaps($query, string $start, string $end): bool
    {
        return $query->where('status', 'active')
            ->where(fn ($q) => $q->whereNull('start_date')->orWhereDate('start_date', '<=', $end))
            ->where(fn ($q) => $q->whereNull('end_date')->orWhereDate('end_date', '>=', $start))
            ->exists();
    }
    private function audit(User $actor, Model $model, string $module, string $action, ?array $old = null): void
    {
        $request = app()->bound('request') ? request() : null;
        DB::table('audit_logs')->insert([
            'actor_user_id' => $actor->id,
            'actor_identifier' => $actor->email,
            'actor_role_snapshot' => $actor->getRoleNames()->implode(', '),
            'event_category' => 'academic_management',
            'action' => $action, 'module' => $module,
            'auditable_type' => $model->getMorphClass(), 'auditable_id' => $model->getKey(),
            'old_values' => $old === null ? null : json_encode($old, JSON_THROW_ON_ERROR),
            'new_values' => json_encode($model->getAttributes(), JSON_THROW_ON_ERROR),
            'metadata' => null, 'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
            'request_id' => $request?->headers->get('X-Request-ID') ?? (string) Str::uuid(),
            'outcome' => 'success', 'created_at' => now(),
        ]);
    }
    public function assignClass(User $actor, array $data): TeacherClassAssignment
    {
        return DB::transaction(function () use ($actor, $data) {
            $class = $this->schoolClass((int)$data['class_id']);
            $teacher = $this->teacher((int)$data['teacher_profile_id']);
            $session = $class->academicSession;
            [$start, $end] = $this->dates($data['start_date'] ?? null, $data['end_date'] ?? null,
                $session->start_date->toDateString(), $session->end_date->toDateString());
            $position = $data['position'];
            if (! in_array($position, ['class_teacher', 'assistant_class_teacher'], true)) $this->fail('position','Invalid position.');
            if ($this->overlaps(TeacherClassAssignment::query()->where('class_id',$class->id)
                ->where('teacher_profile_id',$teacher->id)->where('position',$position), $start, $end)) {
                $this->fail('teacher_profile_id','This teacher already holds this responsibility during the selected dates.');
            }
            if ($position === 'class_teacher' && $this->overlaps(TeacherClassAssignment::query()
                ->where('class_id',$class->id)->where('position','class_teacher'), $start, $end)) {
                $this->fail('position','Another class teacher is already assigned for these dates.');
            }
            $item = TeacherClassAssignment::create(['teacher_profile_id'=>$teacher->id, 'class_id'=>$class->id,
                'position'=>$position, 'start_date'=>$start, 'end_date'=>$end, 'status'=>'active']);
            $this->audit($actor,$item,'teacher_class_assignments','create');
            return $item;
        },3);
    }
    public function assignSubject(User $actor, array $data): TeacherSubjectClass
    {
        return DB::transaction(function () use ($actor, $data) {
            $class = $this->schoolClass((int)$data['class_id']);
            $teacher = $this->teacher((int)$data['teacher_profile_id']);
            $semester = Semester::findOrFail((int)$data['semester_id']);
            $subject = Subject::findOrFail((int)$data['subject_id']);
            if ($semester->academic_session_id !== $class->academic_session_id || $semester->status === 'closed') {
                $this->fail('semester_id','Select an open semester belonging to the class academic session.');
            }
            if (! $subject->is_active) $this->fail('subject_id','Selected subject is inactive.');
            [$start,$end]=$this->dates($data['start_date']??null,$data['end_date']??null,
                $semester->start_date->toDateString(),$semester->end_date->toDateString());
            if ($this->overlaps(TeacherSubjectClass::query()->where('teacher_profile_id',$teacher->id)
                ->where('class_id',$class->id)->where('semester_id',$semester->id)
                ->where('subject_id',$subject->id),$start,$end)) {
                $this->fail('teacher_profile_id','Overlapping teaching assignment already exists.');
            }
            $item=TeacherSubjectClass::create(['teacher_profile_id'=>$teacher->id,'subject_id'=>$subject->id,
                'academic_session_id'=>$class->academic_session_id,'class_id'=>$class->id,
                'semester_id'=>$semester->id,'start_date'=>$start,'end_date'=>$end,'status'=>'active']);
            $this->audit($actor,$item,'teacher_subject_class','create');
            return $item;
        },3);
    }
    public function assignGroup(User $actor, array $data): SubjectGroupMember
    {
        return DB::transaction(function () use ($actor,$data) {
            $teacher=$this->teacher((int)$data['teacher_profile_id']);
            $session=$this->session((int)$data['academic_session_id']);
            $group=SubjectGroup::findOrFail((int)$data['subject_group_id']);
            if (! $group->is_active) $this->fail('subject_group_id','Selected group is inactive.');
            [$start,$end]=$this->dates($data['start_date']??null,$data['end_date']??null,
                $session->start_date->toDateString(),$session->end_date->toDateString());
            $position=$data['position'];
            if ($this->overlaps(SubjectGroupMember::query()->where('teacher_profile_id',$teacher->id)
                ->where('subject_group_id',$group->id)->where('academic_session_id',$session->id)
                ->where('position',$position),$start,$end)) {
                $this->fail('teacher_profile_id','Overlapping group membership already exists.');
            }
            if ($position==='coordinator' && $this->overlaps(SubjectGroupMember::query()
                ->where('subject_group_id',$group->id)->where('academic_session_id',$session->id)
                ->where('position','coordinator'),$start,$end)) {
                $this->fail('position','A coordinator already exists for these dates.');
            }
            $item=SubjectGroupMember::create(['subject_group_id'=>$group->id,'teacher_profile_id'=>$teacher->id,
                'academic_session_id'=>$session->id,'position'=>$position,'start_date'=>$start,
                'end_date'=>$end,'status'=>'active']);
            $this->audit($actor,$item,'subject_group_members','create');
            return $item;
        },3);
    }
    public function end(User $actor, string $kind, int $id): void
    {
        $types = [
            'class' => [TeacherClassAssignment::class,'teacher_class_assignments'],
            'subject' => [TeacherSubjectClass::class,'teacher_subject_class'],
            'group' => [SubjectGroupMember::class,'subject_group_members'],
        ];
        if (! isset($types[$kind])) $this->fail('assignment','Invalid assignment category.');
        [$model,$module]=$types[$kind];
        DB::transaction(function () use ($actor,$model,$module,$id) {
            $item=$model::query()->lockForUpdate()->findOrFail($id);
            if ($item->status !== 'active') $this->fail('assignment','Assignment already ended.');
            $old=$item->getAttributes();
            $today=now()->toDateString();
            $date=$item->start_date?->toDateString();
            $end=$item->end_date?->toDateString();
            $item->update(['status'=>'inactive', 'end_date'=>$end && $end < $today ? $end : ($date && $date > $today ? $date : $today)]);
            $this->audit($actor,$item,$module,'change_status',$old);
        },3);
    }
}
