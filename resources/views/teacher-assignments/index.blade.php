@extends('layouts.app')
@section('title','Teacher Assignments')
@section('content')
<x-ui.page-header title="Teacher Assignments" description="Manage class teachers, subject teaching, and subject-group responsibilities across Form 6 intakes." />
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
@if($canManage)
<div class="row g-3 mb-4">
@can('classes.assign_teachers')
<div class="col-12 col-xl-4"><div class="ed-card p-4 h-100"><h2 class="h5">Class Responsibility</h2>
<form method="POST" action="{{ route('teacher-assignments.class.store') }}">@csrf
<label class="form-label">Teacher</label><select class="form-select mb-2" name="teacher_profile_id" required><option value="">Choose teacher</option>@foreach($teachers as $t)<option value="{{ $t->id }}">{{ $t->full_name }}</option>@endforeach</select>
<label class="form-label">Class / Session</label><select class="form-select mb-2" name="class_id" required><option value="">Choose class</option>@foreach($classes as $c)<option value="{{ $c->id }}">{{ $c->name }} ({{ $c->academicSession?->name }})</option>@endforeach</select>
<label class="form-label">Position</label><select class="form-select mb-2" name="position"><option value="class_teacher">Class Teacher</option><option value="assistant_class_teacher">Assistant Class Teacher</option></select>
<label class="form-label">Start (optional: session start)</label><input class="form-control mb-2" type="date" name="start_date">
<label class="form-label">End (optional: session end)</label><input class="form-control mb-3" type="date" name="end_date">
<button class="btn btn-primary" type="submit">Assign to Class</button></form></div></div>
@endcan
@can('teachers.assign')
<div class="col-12 col-xl-4"><div class="ed-card p-4 h-100"><h2 class="h5">Subject Teaching</h2>
<form method="POST" action="{{ route('teacher-assignments.subject.store') }}">@csrf
<label class="form-label">Teacher</label><select class="form-select mb-2" name="teacher_profile_id" required><option value="">Choose teacher</option>@foreach($teachers as $t)<option value="{{ $t->id }}">{{ $t->full_name }}</option>@endforeach</select>
<label class="form-label" for="subject-teaching-class">Class</label><select class="form-select mb-2" name="class_id" id="subject-teaching-class" required><option value="">Choose class</option>@foreach($classes as $c)<option value="{{ $c->id }}" data-session-id="{{ $c->academic_session_id }}">{{ $c->name }} ({{ $c->academicSession?->name }})</option>@endforeach</select>
<label class="form-label" for="subject-teaching-semester">Semester</label><select class="form-select mb-2" name="semester_id" id="subject-teaching-semester" required disabled><option value="">Choose a class first</option>@foreach($semesters as $s)<option value="{{ $s->id }}" data-session-id="{{ $s->academic_session_id }}">{{ $s->name }} — {{ $s->academicSession?->name ?? 'Academic Session' }}</option>@endforeach</select><div class="form-text mb-2">Only semesters belonging to the selected class are available.</div>
<label class="form-label">Subject</label><select class="form-select mb-2" name="subject_id" required><option value="">Choose subject</option>@foreach($subjects as $s)<option value="{{ $s->id }}">{{ $s->name }}</option>@endforeach</select>
<label class="form-label">Start (optional)</label><input class="form-control mb-2" type="date" name="start_date">
<label class="form-label">End (optional)</label><input class="form-control mb-3" type="date" name="end_date">
<button class="btn btn-primary" type="submit">Assign Subject</button></form></div></div>
@can('subject_groups.manage')
<div class="col-12 col-xl-4"><div class="ed-card p-4 h-100"><h2 class="h5">Subject Group</h2>
<form method="POST" action="{{ route('teacher-assignments.group.store') }}">@csrf
<label class="form-label">Teacher</label><select class="form-select mb-2" name="teacher_profile_id" required><option value="">Choose teacher</option>@foreach($teachers as $t)<option value="{{ $t->id }}">{{ $t->full_name }}</option>@endforeach</select>
<label class="form-label">Academic Session</label><select class="form-select mb-2" name="academic_session_id" required><option value="">Choose session</option>@foreach($sessions as $s)<option value="{{ $s->id }}">{{ $s->name }}</option>@endforeach</select>
<label class="form-label">Subject Group</label><select class="form-select mb-2" name="subject_group_id" required><option value="">Choose group</option>@foreach($groups as $g)<option value="{{ $g->id }}">{{ $g->name }}</option>@endforeach</select>
<label class="form-label">Position</label><select class="form-select mb-2" name="position"><option value="member">Member</option><option value="coordinator">Coordinator</option><option value="head">Head</option></select>
<label class="form-label">Start (optional)</label><input class="form-control mb-2" type="date" name="start_date">
<label class="form-label">End (optional)</label><input class="form-control mb-3" type="date" name="end_date">
<button class="btn btn-primary" type="submit">Assign Group Role</button></form></div></div>
@endcan
@endcan
</div>
@endif
<div class="ed-card p-4 mb-3"><h2 class="h5">Class Responsibilities</h2><div class="table-responsive"><table class="table table-sm align-middle"><thead><tr><th>Teacher</th><th>Class</th><th>Position</th><th>Dates</th><th>Status</th><th>Action</th></tr></thead><tbody>
@forelse($classAssignments as $a)<tr><td>{{ $a->teacher?->full_name }}</td><td>{{ $a->schoolClass?->name }}</td><td>{{ str_replace('_',' ',ucwords($a->position,'_')) }}</td><td>{{ $a->start_date?->format('Y-m-d') }} – {{ $a->end_date?->format('Y-m-d') ?? 'Open' }}</td><td>{{ $a->status }}</td><td>@can('classes.assign_teachers')@if($a->status==='active')<form method="POST" action="{{ route('teacher-assignments.end',['kind'=>'class','id'=>$a->id]) }}">@csrf @method('PATCH')<button class="btn btn-outline-danger btn-sm">End</button></form>@endif @endcan</td></tr>
@empty<tr><td colspan="6">No class assignments</td></tr>@endforelse</tbody></table></div>{{ $classAssignments->links() }}</div>
<div class="ed-card p-4 mb-3"><h2 class="h5">Subject Teaching</h2><div class="table-responsive"><table class="table table-sm align-middle"><thead><tr><th>Teacher</th><th>Class</th><th>Subject</th><th>Semester</th><th>Status</th><th>Action</th></tr></thead><tbody>
@forelse($subjectAssignments as $a)<tr><td>{{ $a->teacher?->full_name }}</td><td>{{ $a->schoolClass?->name }}</td><td>{{ $a->subject?->name }}</td><td>{{ $a->semester?->name }}</td><td>{{ $a->status }}</td><td>@can('teachers.assign')@if($a->status==='active')<form method="POST" action="{{ route('teacher-assignments.end',['kind'=>'subject','id'=>$a->id]) }}">@csrf @method('PATCH')<button class="btn btn-outline-danger btn-sm">End</button></form>@endif @endcan</td></tr>
@empty<tr><td colspan="6">No subject assignments</td></tr>@endforelse</tbody></table></div>{{ $subjectAssignments->links() }}</div>
<div class="ed-card p-4"><h2 class="h5">Subject-Group Responsibilities</h2><div class="table-responsive"><table class="table table-sm align-middle"><thead><tr><th>Teacher</th><th>Group</th><th>Session</th><th>Position</th><th>Status</th><th>Action</th></tr></thead><tbody>
@forelse($groupAssignments as $a)<tr><td>{{ $a->teacher?->full_name }}</td><td>{{ $a->subjectGroup?->name }}</td><td>{{ $a->academicSession?->name }}</td><td>{{ ucfirst($a->position) }}</td><td>{{ $a->status }}</td><td>@can('teachers.assign')@can('subject_groups.manage')@if($a->status==='active')<form method="POST" action="{{ route('teacher-assignments.end',['kind'=>'group','id'=>$a->id]) }}">@csrf @method('PATCH')<button class="btn btn-outline-danger btn-sm">End</button></form>@endif @endcan @endcan</td></tr>
@empty<tr><td colspan="6">No subject group assignments</td></tr>@endforelse</tbody></table></div>{{ $groupAssignments->links() }}</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const classSelect = document.getElementById('subject-teaching-class');
    const semesterSelect = document.getElementById('subject-teaching-semester');
    if (!classSelect || !semesterSelect) return;
    const placeholder = semesterSelect.options[0];
    const semesters = Array.from(semesterSelect.options).slice(1);

    function filterSemesters() {
        const selectedClass = classSelect.options[classSelect.selectedIndex];
        const sessionId = selectedClass ? selectedClass.dataset.sessionId : null;
        let count = 0;
        semesters.forEach(option => {
            const matches = !!sessionId && option.dataset.sessionId === sessionId;
            option.hidden = !matches;
            option.disabled = !matches;
            if (matches) count++;
        });
        // Always reset stale semester selections when class changes.
        semesterSelect.value = '';
        semesterSelect.disabled = !sessionId || count === 0;
        placeholder.textContent = !sessionId ? 'Choose a class first'
            : count === 0 ? 'No available semesters for this class' : 'Choose matching semester';
    }
    classSelect.addEventListener('change', filterSemesters);
    filterSemesters();
});
</script>
@endsection
