@extends('layouts.app')
@section('title', 'Complete Your Profile')
@section('content')
<div class="profile-completion-shell">
    <section class="completion-summary panel">
        <div class="completion-hero"><span class="completion-icon"><i class="bi bi-person-exclamation"></i></span><div><p class="eyebrow">First-login requirement</p><h1>Complete Your Profile</h1><p>Your account has been created, but some required information is still missing. Complete it before continuing to the full system.</p></div></div>
        <div class="completion-progress"><div><span>Profile Completion</span><strong>45%</strong></div><div class="progress"><div class="progress-bar" style="width:45%"></div></div></div>
        <div class="completion-checklist"><div class="done"><i class="bi bi-check-circle-fill"></i><span><strong>Account Information</strong><small>Created by teacher</small></span></div><div><i class="bi bi-circle"></i><span><strong>Personal Information</strong><small>Required</small></span></div><div><i class="bi bi-circle"></i><span><strong>Contact & Address</strong><small>Required</small></span></div><div><i class="bi bi-circle"></i><span><strong>Parent / Guardian</strong><small>Required</small></span></div><div><i class="bi bi-circle"></i><span><strong>Additional Information</strong><small>Optional</small></span></div></div>
    </section>

    <form class="panel form-panel profile-full-form" onsubmit="return false;">
        <div class="form-section-heading"><span>1</span><div><h2>Personal Information</h2><p>Complete all required fields to continue.</p></div></div>
        <div class="row g-3"><div class="col-md-6"><label class="form-label">Full Name <b>*</b></label><input class="form-control" value="Ahmad Firdaus"></div><div class="col-md-6"><label class="form-label">IC / Passport <b>*</b></label><input class="form-control" value="060101-14-1234"></div><div class="col-md-6"><label class="form-label">Gender <b>*</b></label><select class="form-select"><option>Male</option><option>Female</option></select></div><div class="col-md-6"><label class="form-label">Date of Birth <b>*</b></label><input type="date" class="form-control"></div><div class="col-md-6"><label class="form-label">Nationality <b>*</b></label><input class="form-control" value="Malaysian"></div><div class="col-md-6"><label class="form-label">Race</label><input class="form-control" placeholder="Enter race"></div><div class="col-md-6"><label class="form-label">Email <b>*</b></label><input type="email" class="form-control" placeholder="student@example.com"></div><div class="col-md-6"><label class="form-label">Phone <b>*</b></label><input class="form-control" placeholder="01X-XXXXXXX"></div></div>
        <div class="form-actions"><button class="btn btn-primary">Save & Continue <i class="bi bi-arrow-right ms-1"></i></button></div>
    </form>
</div>
@endsection
