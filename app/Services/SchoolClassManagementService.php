<?php
namespace App\Services;
use App\Models\AcademicSession;
use App\Models\SchoolClass;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SchoolClassManagementService
{
    private function sessionAndBatch(int $id): array
    {
        $session = AcademicSession::query()->lockForUpdate()->findOrFail($id);
        if (!in_array($session->status, ['planned','active'],true)) {
            throw ValidationException::withMessages(['academic_session_id'=>'Session must be planned or active.']);
        }
        $batch = $session->batch()->lockForUpdate()->first();
        if (!$batch || $batch->status !== 'active') {
            throw ValidationException::withMessages(['academic_session_id'=>'Session must have an active linked batch.']);
        }
        return [$session, $batch];
    }
    private function uniqueCode(int $sessionId, string $code, ?int $except = null): void
    {
        if (SchoolClass::withTrashed()->where('academic_session_id',$sessionId)
            ->where('code',$code)->when($except !== null,fn ($q)=>$q->where('id','!=',$except))->exists()) {
            throw ValidationException::withMessages(['code'=>'Class code already exists in that session.']);
        }
    }
    public function create(User $actor, array $data): SchoolClass
    {
        return DB::transaction(function () use ($actor,$data) {
            [$session,$batch] = $this->sessionAndBatch((int)$data['academic_session_id']);
            $code = strtoupper(trim($data['code']));
            $this->uniqueCode($session->id,$code);
            $class = SchoolClass::create([
                'academic_session_id'=>$session->id,'batch_id'=>$batch->id,'code'=>$code,
                'name'=>trim($data['name']),'description'=>$data['description']??null,'status'=>'active'
            ]);
            $this->audit($actor,$class,'create',null);
            return $class;
        });
    }
    public function update(User $actor, SchoolClass $schoolClass, array $data): SchoolClass
    {
        return DB::transaction(function () use ($actor,$schoolClass,$data) {
            [$session,$batch] = $this->sessionAndBatch((int)$data['academic_session_id']);
            $class = SchoolClass::query()->lockForUpdate()->findOrFail($schoolClass->id);
            if ($class->status !== 'active') throw ValidationException::withMessages(['school_class'=>'Class is inactive.']);
            $changed = (int)$class->academic_session_id !== (int)$session->id || (int)$class->batch_id !== (int)$batch->id;
            if ($changed && ($class->studentEnrollments()->exists() || $class->teacherClassAssignments()->exists()
                || $class->teachingAssignments()->exists() || $class->attendanceSessions()->exists()
                || $class->timetables()->exists())) {
                throw ValidationException::withMessages(['academic_session_id'=>'Cannot move a class with academic records.']);
            }
            $code = strtoupper(trim($data['code']));
            $this->uniqueCode($session->id,$code,$class->id);
            $old = $class->getAttributes();
            $class->update(['academic_session_id'=>$session->id,'batch_id'=>$batch->id,
                'code'=>$code,'name'=>trim($data['name']),'description'=>$data['description']??null]);
            $this->audit($actor,$class,'update',$old);
            return $class;
        });
    }
    public function deactivate(User $actor, SchoolClass $schoolClass): SchoolClass
    {
        return DB::transaction(function () use ($actor,$schoolClass) {
            $class=SchoolClass::query()->lockForUpdate()->findOrFail($schoolClass->id);
            if ($class->status !== 'active') throw ValidationException::withMessages(['status'=>'Class is not active.']);
            if ($class->studentEnrollments()->where('status','active')->whereNull('ended_at')->exists()
                || $class->teacherClassAssignments()->where('status','active')->exists()
                || $class->teachingAssignments()->where('status','active')->exists()) {
                throw ValidationException::withMessages(['status'=>'Close active enrollments and assignments first.']);
            }
            $old=$class->getAttributes();
            $class->update(['status'=>'inactive']);
            $this->audit($actor,$class,'change_status',$old);
            return $class;
        });
    }
    private function audit(User $actor, SchoolClass $model, string $action, ?array $old): void
    {
        $r=app()->bound('request')?request():null;
        DB::table('audit_logs')->insert([
            'actor_user_id'=>$actor->id,'actor_identifier'=>$actor->email,
            'actor_role_snapshot'=>$actor->getRoleNames()->implode(', '),
            'event_category'=>'academic_management','action'=>$action,'module'=>'classes',
            'auditable_type'=>$model->getMorphClass(),'auditable_id'=>$model->id,
            'old_values'=>$old===null?null:json_encode($old,JSON_THROW_ON_ERROR),
            'new_values'=>json_encode($model->getAttributes(),JSON_THROW_ON_ERROR),
            'metadata'=>null,'ip_address'=>$r?->ip(),'user_agent'=>$r?->userAgent(),
            'request_id'=>$r?->headers->get('X-Request-ID')??(string)Str::uuid(),
            'outcome'=>'success','created_at'=>now(),
        ]);
    }
}
