@extends('layouts.auth')

@section('title', 'Set New Password')

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
                        <h3 class="auth-form-title mb-1">Set New Password</h3>
                        <p class="text-muted fs-sm mb-0">Create a secure new password for your admin account</p>
                    </div>

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

                    <form method="POST" action="{{ route('password.store') }}" class="mt-3">
                        @csrf

                        <input type="hidden" name="token" value="{{ $request->route('token') }}">

                        <div class="mb-3">
                            <label for="email" class="form-label fw-semibold text-dark fs-sm mb-1.5">
                                Email Address
                            </label>
                            <div class="input-group-modern">
                                <i class="ti ti-mail input-icon"></i>
                                <input type="email"
                                    class="form-control form-control-modern @error('email') is-invalid @enderror"
                                    name="email" id="email" value="{{ old('email', $request->email) }}"
                                    placeholder="admin@kidseedental.com" required autofocus autocomplete="username" />
                            </div>
                            @error('email')
                                <div class="text-danger fs-xs mt-1.5 fw-medium">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label fw-semibold text-dark fs-sm mb-1.5">
                                New Password
                            </label>
                            <div class="input-group-modern position-relative">
                                <i class="ti ti-lock input-icon"></i>
                                <input type="password"
                                    class="form-control form-control-modern pe-5 @error('password') is-invalid @enderror"
                                    name="password" id="password" placeholder="Enter new password" required
                                    autocomplete="new-password" />
                                <button type="button" class="password-toggle-btn" id="togglePasswordBtn" tabindex="-1">
                                    <i class="ti ti-eye fs-5" id="togglePasswordIcon"></i>
                                </button>
                            </div>
                            @error('password')
                                <div class="text-danger fs-xs mt-1.5 fw-medium">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="password_confirmation" class="form-label fw-semibold text-dark fs-sm mb-1.5">
                                Confirm Password
                            </label>
                            <div class="input-group-modern">
                                <i class="ti ti-lock-check input-icon"></i>
                                <input type="password"
                                    class="form-control form-control-modern @error('password_confirmation') is-invalid @enderror"
                                    name="password_confirmation" id="password_confirmation"
                                    placeholder="Confirm new password" required autocomplete="new-password" />
                            </div>
                            @error('password_confirmation')
                                <div class="text-danger fs-xs mt-1.5 fw-medium">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="d-grid mb-3">
                            <button type="submit" class="btn btn-brand-yellow w-100">
                                <i class="ti ti-check me-1 fs-5 align-middle"></i> Update Password
                            </button>
                        </div>

                        <div class="text-center mt-3">
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

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const toggleBtn = document.getElementById('togglePasswordBtn');
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.getElementById('togglePasswordIcon');

            if (toggleBtn && passwordInput && toggleIcon) {
                toggleBtn.addEventListener('click', function() {
                    const isPassword = passwordInput.getAttribute('type') === 'password';
                    passwordInput.setAttribute('type', isPassword ? 'text' : 'password');
                    toggleIcon.classList.toggle('ti-eye', !isPassword);
                    toggleIcon.classList.toggle('ti-eye-off', isPassword);
                });
            }
        });
    </script>
@endpush
