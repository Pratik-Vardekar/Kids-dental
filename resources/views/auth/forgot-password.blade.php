@extends('layouts.auth')

@section('title', 'Admin Reset Password')

@section('content')
    <div class="row justify-content-center">
        <div class="col-11 col-sm-9 col-md-7 col-lg-5 col-xl-4">
            <div class="card auth-card p-3 p-sm-4 border-0">
                <div class="card-body">

                    <!-- Logo & Header -->
                    <div class="text-center mb-4">
                        <a href="{{ url('/') }}" class="d-inline-block text-decoration-none">
                            <img src="{{ asset('backend/assets/images/KidseDental_Logo.png') }}" alt="Kids-e-Dental"
                                class="auth-logo-img mb-3" />
                        </a>
                        <h3 class="auth-form-title mb-1">Reset Password</h3>
                        <p class="text-muted fs-sm mb-0">Enter your administrative email to receive a password reset link
                        </p>
                    </div>

                    <!-- Status / Error Alerts -->
                    @if (session('status'))
                        <div class="alert alert-success alert-dismissible fade show text-center py-2 fs-sm rounded-3 border-0 shadow-sm"
                            role="alert">
                            {{ session('status') }}
                            <button type="button" class="btn-close py-2" data-bs-dismiss="alert"
                                aria-label="Close"></button>
                        </div>
                    @endif

                    @if ($errors->any())
                        <div class="alert alert-danger alert-dismissible fade show py-2 fs-sm rounded-3 border-0 shadow-sm"
                            role="alert">
                            <ul class="mb-0 ps-3">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                            <button type="button" class="btn-close py-2" data-bs-dismiss="alert"
                                aria-label="Close"></button>
                        </div>
                    @endif

                    <!-- Form -->
                    <form method="POST" action="{{ route('password.email') }}" class="mt-3">
                        @csrf

                        <div class="mb-4">
                            <label for="email" class="form-label fw-semibold text-dark fs-sm mb-1.5">
                                Email Address
                            </label>
                            <div class="input-group-modern">
                                <i class="ti ti-mail input-icon"></i>
                                <input type="email"
                                    class="form-control form-control-modern @error('email') is-invalid @enderror"
                                    name="email" id="email" value="{{ old('email') }}"
                                    placeholder="admin@kidseedental.com" required autofocus />
                            </div>
                            @error('email')
                                <div class="text-danger fs-xs mt-1.5 fw-medium">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="d-grid mb-3">
                            <button type="submit" class="btn btn-brand-yellow w-100">
                                <i class="ti ti-send me-1 fs-5 align-middle"></i> Send Password Reset Link
                            </button>
                        </div>

                        <div class="text-center">
                            <a href="{{ route('login') }}" class="brand-link fs-sm">
                                <i class="ti ti-arrow-left me-1"></i> Back to Sign In
                            </a>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>
@endsection
