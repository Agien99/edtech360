@extends('layouts.app')
@section('title', 'Register Student')
@section('content')
<div class="page-heading action-heading">
    <div><p class="eyebrow">Home / Students / Register</p><h1>Register New Student</h1><p>Quick registration for teachers. Only essential information is required now.</p></div>
    <a href="{{ route('students.index', ['role' => $role]) }}" class="btn btn-light border"><i class="bi bi-arrow-left me-1"></i> Back to List</a>
</div>

<div class="form-note"><i class="bi bi-info-circle-fill"></i><div><strong>Designed for fast registration</strong><span>Teachers only create the student record. The student will be required to complete missing profile information after first login.</span></div></div>

<form class="panel form-panel" action="#" method="post" onsubmit="return false;">
    <div class="form-section-heading"><span>1</span><div><h2>Essential Information</h2><p>Fields marked * are required to register the student.</p></div></div>
    <div class="row g-3">
        <div class="col-lg-6"><label class="form-label">Full Name <b>*</b></label><input class="form-control" placeholder="Enter student full name"></div>
        <div class="col-lg-6"><label class="form-label">Student No. / IC <b>*</b></label><input class="form-control" placeholder="e.g. 060101-14-1234"></div>
        <div class="col-lg-6"><label class="form-label">Class <b>*</b></label><select class="form-select"><option selected disabled>Select class</option><option>Form 6A</option><option>Form 6B</option><option>Form 6C</option></select></div>
        <div class="col-lg-6"><label class="form-label">Academic Session <b>*</b></label><select class="form-select"><option>2026</option></select></div>
        <div class="col-lg-6"><label class="form-label">Email <span>(Optional)</span></label><input type="email" class="form-control" placeholder="Student email for login (optional)"></div>
        <div class="col-lg-6"><label class="form-label">Temporary Password</label><div class="input-group"><input class="form-control" value="Edtech@1234"><button class="btn btn-outline-secondary" type="button">Generate</button></div></div>
    </div>
    <div class="credential-option"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" id="sendCredentials" checked><label class="form-check-label" for="sendCredentials">Send account credentials to student when email is available</label></div><small>The student will be prompted to complete their profile after first login.</small></div>
    <div class="form-actions"><button type="button" class="btn btn-light border">Cancel</button><button type="submit" class="btn btn-primary"><i class="bi bi-person-plus me-1"></i> Register Student</button></div>
</form>
@endsection
