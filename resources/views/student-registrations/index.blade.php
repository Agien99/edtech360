@extends('layouts.app')
@section('title', 'Student Registration')
@section('content')
<x-ui.page-header title="Student Registration" description="Create student logins and assign intakes. Students choose classes after onboarding." />
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
@if(session('issued_credentials'))
<section class="ed-card p-4 mb-4 border border-warning" id="credentials">
  <h2 class="h5">New Login Credentials — One-time display</h2>
  <p class="small text-danger">Copy these credentials now and deliver privately to each student. They will not be shown again. Do not share screenshots or store the file on a public device. Password change on first login must be implemented before production use.</p>
  <div class="table-responsive"><table class="table"><thead><tr><th>Student</th><th>Email</th><th>Temporary password</th></tr></thead><tbody>
  @foreach(session('issued_credentials') as $credential)<tr><td>{{ $credential['full_name'] }}</td><td>{{ $credential['email'] }}</td><td><code>{{ $credential['temporary_password'] }}</code></td></tr>@endforeach
  </tbody></table></div>
</section>
@endif
<div class="ed-card p-4 mb-4">
  <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
    <div><h2 class="h5 mb-1">Register Students</h2><p class="text-muted small mb-0">Add students directly with IC, name and email. Student numbers remain unassigned.</p></div>
    <button class="btn btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#addStudentsModal"> <i class="bi bi-plus-lg"></i> Add Students</button>
  </div>
</div>
<div class="modal fade" id="addStudentsModal" tabindex="-1" aria-labelledby="addStudentsTitle" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable"><div class="modal-content">
    <div class="modal-header"><h2 class="modal-title h5" id="addStudentsTitle">Add Students</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
    <form method="POST" action="{{ route('student-registrations.store') }}">@csrf
      <div class="modal-body">
        <div class="mb-3"><label class="form-label">Academic Session / Intake</label><select name="academic_session_id" class="form-select" required><option value="">Choose intake</option>@foreach($sessions as $session)<option value="{{ $session->id }}" @selected(old('academic_session_id') == $session->id)>{{ $session->name }} — {{ $session->batch?->code }}</option>@endforeach</select></div>
        <p class="text-muted small">Passwords are randomly generated. Do not enter or use IC numbers as passwords.</p>
        <div class="table-responsive"><table class="table table-sm align-middle"><thead><tr><th>IC Number</th><th>Full Name</th><th>Email</th><th></th></tr></thead><tbody id="student-rows">
        @foreach(old('students', [['ic_number'=>'','full_name'=>'','email'=>'']]) as $i => $student)
        <tr><td><input class="form-control" name="students[{{ $i }}][ic_number]" value="{{ $student['ic_number'] ?? '' }}" placeholder="12 digits" inputmode="numeric" pattern="[0-9]{6}-?[0-9]{2}-?[0-9]{4}" required></td><td><input class="form-control" name="students[{{ $i }}][full_name]" value="{{ $student['full_name'] ?? '' }}" maxlength="150" required></td><td><input class="form-control" type="email" name="students[{{ $i }}][email]" value="{{ $student['email'] ?? '' }}" required></td><td><button type="button" class="btn btn-sm btn-outline-danger remove-student" aria-label="Remove row"><i class="bi bi-trash"></i></button></td></tr>
        @endforeach
        </tbody></table></div>
        <button type="button" class="btn btn-outline-secondary btn-sm" id="add-student-row"><i class="bi bi-plus"></i> Add Row</button>
        <p class="small text-muted mt-2">Maximum 200 students in a single submission. All rows must pass validation before any account is created.</p>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary">Register All</button></div>
    </form>
  </div></div>
</div>
<section class="ed-card p-4 mb-4">
  <h2 class="h5">Optional CSV Import</h2><p class="small text-muted">Use <code>ic_number,full_name,email</code> as the header; maximum 200 students per file. Import is all-or-nothing.</p>
  <form method="POST" action="{{ route('student-registrations.import') }}" enctype="multipart/form-data">@csrf
  <div class="row g-3"><div class="col-md-5"><label class="form-label">Academic Session</label><select name="academic_session_id" class="form-select" required><option value="">Choose intake</option>@foreach($sessions as $session)<option value="{{ $session->id }}">{{ $session->name }} — {{ $session->batch?->code }}</option>@endforeach</select></div><div class="col-md-7"><label class="form-label">CSV File</label><input type="file" class="form-control" name="csv_file" accept=".csv,text/csv" required></div></div>
  <div class="form-check my-3"><input type="checkbox" class="form-check-input" id="confirm_import" name="confirm_import" value="1" required><label class="form-check-label" for="confirm_import">I have reviewed the data and want to create these accounts.</label></div><button type="submit" class="btn btn-outline-primary">Import CSV</button>
  </form>
</section>
<section class="ed-card p-4"><h2 class="h5">Recently Registered Students</h2><div class="table-responsive"><table class="table table-hover"><thead><tr><th>Student Number</th><th>Name</th><th>Email</th><th>Intake</th></tr></thead><tbody>
@forelse($students as $student)<tr><td>{{ $student->student_number ?? 'Not assigned' }}</td><td>{{ $student->full_name }}</td><td>{{ $student->user?->email }}</td><td>{{ $student->batchMemberships->map(fn($m) => $m->batch?->academicSession?->name)->filter()->join(', ') }}</td></tr>
@empty<tr><td colspan="4" class="text-muted text-center">No students registered yet.</td></tr>@endforelse
</tbody></table></div>{{ $students->links() }}</section>
<script>
document.addEventListener('DOMContentLoaded', () => {
  const tbody = document.getElementById('student-rows');
  const add = document.getElementById('add-student-row');
  let next = tbody.querySelectorAll('tr').length;
  add.addEventListener('click', () => {
    if (tbody.querySelectorAll('tr').length >= 200) return;
    const i = next++;
    const row = document.createElement('tr');
    row.innerHTML = `<td><input class="form-control" name="students[${i}][ic_number]" placeholder="12 digits" inputmode="numeric" pattern="[0-9]{6}-?[0-9]{2}-?[0-9]{4}" required></td><td><input class="form-control" name="students[${i}][full_name]" maxlength="150" required></td><td><input type="email" class="form-control" name="students[${i}][email]" required></td><td><button type="button" class="btn btn-sm btn-outline-danger remove-student" aria-label="Remove row"><i class="bi bi-trash"></i></button></td>`;
    tbody.appendChild(row);
  });
  tbody.addEventListener('click', e => {
    const button = e.target.closest('.remove-student');
    if (button && tbody.querySelectorAll('tr').length > 1) button.closest('tr').remove();
  });
  @if($errors->any() && old('students'))
  if (window.bootstrap) new bootstrap.Modal(document.getElementById('addStudentsModal')).show();
  @endif
});
</script>
@endsection
