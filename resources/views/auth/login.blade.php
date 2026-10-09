@extends('layouts.auth')

@section('title', 'Sign In')

@section('content')
<main class="ed-auth-shell">
    <section class="ed-auth-brand-panel">
        <img
            src="{{ asset('assets/branding/edtech360-logo-stacked.svg') }}"
            alt="EdTech360"
        >

        <div>
            <span>Comprehensive Educational Management System</span>
            <strong>Manage · Learn · Grow · Together</strong>
        </div>
    </section>

    <section class="ed-auth-form-panel">
        <div class="ed-auth-form-wrap">
            <div class="d-lg-none text-center mb-4">
                <img
                    class="ed-auth-mobile-logo"
                    src="{{ asset('assets/branding/edtech360-logo-stacked.svg') }}"
                    alt="EdTech360"
                >
            </div>

            <span class="ed-eyebrow">Welcome back</span>

            <h1>Sign in to EdTech360</h1>

            <p>
                Access your classes, learning tools and
                school management workspace.
            </p>

            <form
                class="mt-4"
                action="{{ route('login.store') }}"
                method="POST"
            >
                @csrf

                @if ($errors->any())
                    <div class="alert alert-danger" role="alert">
                        {{ $errors->first() }}
                    </div>
                @endif

                <div class="mb-3">
                    <label for="email" class="form-label">
                        Email Address
                    </label>

                    <div class="input-group ed-input-group">
                        <span class="input-group-text">
                            <i class="bi bi-envelope"></i>
                        </span>

                        <input
                            id="email"
                            name="email"
                            type="email"
                            class="form-control @error('email') is-invalid @enderror"
                            value="{{ old('email') }}"
                            autocomplete="username"
                            required
                            autofocus
                        >
                    </div>
                </div>

                <div class="mb-3">
                    <div class="d-flex justify-content-between">
                        <label for="password" class="form-label">
                            Password
                        </label>

                        <span class="small text-muted">
                            Password required
                        </span>
                    </div>

                    <div class="input-group ed-input-group">
                        <span class="input-group-text">
                            <i class="bi bi-lock"></i>
                        </span>

                        <input
                            id="password"
                            name="password"
                            type="password"
                            class="form-control"
                            autocomplete="current-password"
                            required
                        >
                    </div>
                </div>

                <div class="form-check mb-4">
                    <input
                        class="form-check-input"
                        type="checkbox"
                        id="remember"
                        name="remember"
                        value="1"
                        @checked(old('remember'))
                    >

                    <label class="form-check-label" for="remember">
                        Remember me
                    </label>
                </div>

                <button
                    type="submit"
                    class="btn btn-primary ed-btn-primary w-100 py-2"
                >
                    Sign In
                </button>
            </form>
        </div>
    </section>
</main>
@endsection