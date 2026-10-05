@extends('layouts.auth')
@section('title', 'Sign In')
@section('content')
<div class="auth-shell">
    <section class="auth-brand-panel">
        <div class="auth-brand-overlay"></div>
        <div class="auth-brand-content"><img src="{{ asset('assets/branding/edtech360-logo-stacked-dark.svg') }}" alt="EdTech360"><p>Manage · Learn · Grow · Together</p></div>
        <div class="school-illustration"><i class="bi bi-buildings"></i><span>A modern digital environment for your entire school community.</span></div>
    </section>
    <section class="auth-form-panel">
        <div class="auth-form-card">
            <img class="auth-mobile-logo d-lg-none" src="{{ asset('assets/branding/edtech360-logo-horizontal.svg') }}" alt="EdTech360">
            <p class="eyebrow">Secure access</p><h1>Welcome Back</h1><p class="auth-subtitle">Sign in to access your EdTech360 account.</p>
            <form onsubmit="return false;"><label class="form-label">Email Address</label><input type="email" class="form-control" placeholder="name@school.edu.my"><label class="form-label mt-3">Password</label><div class="input-group"><input type="password" class="form-control" placeholder="Enter your password"><button class="btn btn-outline-secondary"><i class="bi bi-eye"></i></button></div><div class="auth-options"><label><input type="checkbox" checked> Remember me</label><a href="#">Forgot password?</a></div><button class="btn btn-primary w-100 auth-submit">Sign In</button></form>
            <div class="auth-footer"><span>EdTech360 CEMS v2.0</span><small>© {{ now()->year }}. All rights reserved.</small></div>
        </div>
    </section>
</div>
@endsection
