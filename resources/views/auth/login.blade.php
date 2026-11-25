@extends('layouts.auth')

@section('title', 'Admin Panel Login')

@section('content')
    <div class="row justify-content-center align-items-center py-3">
        <div class="col-12 col-sm-10 col-md-7 col-lg-5 col-xl-4">
            <div class="card auth-card border-0 p-3 p-sm-4">
                <div class="card-body p-1 p-sm-2">

                    <!-- Logo & Header -->
                    <div class="text-center mb-4">
                        <a href="{{ url('/') }}" class="d-inline-block text-decoration-none mb-3">
                            <img src="{{ asset('backend/assets/images/KidseDental_Logo.png') }}" alt="Kids-e-Dental"
                                class="auth-logo-img" />
                        </a>
                        <h3 class="auth-form-title mb-1">Admin Portal Sign In</h3>
                        <p class="text-muted fs-sm mb-0">Enter your authorized administrative credentials</p>
                    </div>

                    <!-- Status / Success Alerts -->
                    @if (session('status'))
                        <div class="alert alert-success alert-dismissible fade show d-flex align-items-center py-2 px-3 fs-sm rounded-3 border-0 shadow-sm mb-3"
                            role="alert">
                            <i class="ti ti-circle-check fs-5 me-2 flex-shrink-0 text-success"></i>
                            <div>{{ session('status') }}</div>
                            <button type="button" class="btn-close py-2 ms-auto" data-bs-dismiss="alert"
                                aria-label="Close"></button>
                        </div>
                    @endif

                    @if (session('success'))
                        <div class="alert alert-success alert-dismissible fade show d-flex align-items-center py-2 px-3 fs-sm rounded-3 border-0 shadow-sm mb-3"
                            role="alert">
                            <i class="ti ti-circle-check fs-5 me-2 flex-shrink-0 text-success"></i>
                            <div>{{ session('success') }}</div>
                            <button type="button" class="btn-close py-2 ms-auto" data-bs-dismiss="alert"
                                aria-label="Close"></button>
                        </div>
                    @endif

                    <!-- Error Alerts -->
                    @if (session('error'))
                        <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center py-2 px-3 fs-sm rounded-3 border-0 shadow-sm mb-3"
                            role="alert">
                            <i class="ti ti-alert-circle fs-5 me-2 flex-shrink-0 text-danger"></i>
                            <div>{{ session('error') }}</div>
                            <button type="button" class="btn-close py-2 ms-auto" data-bs-dismiss="alert"
                                aria-label="Close"></button>
                        </div>
                    @endif

                    @if ($errors->any() && !session('error'))
                        <div class="alert alert-danger alert-dismissible fade show py-2 px-3 fs-sm rounded-3 border-0 shadow-sm mb-3"
                            role="alert">
                            <div class="d-flex align-items-start">
                                <i class="ti ti-alert-circle fs-5 me-2 mt-0.5 flex-shrink-0 text-danger"></i>
                                <ul class="mb-0 ps-1 list-unstyled">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                                <button type="button" class="btn-close py-2 ms-auto" data-bs-dismiss="alert"
                                    aria-label="Close"></button>
                            </div>
                        </div>
                    @endif

                    <!-- Login Form -->
                    <form method="POST" action="{{ route('login') }}" class="mt-2" id="loginForm">
                        @csrf

                        <!-- Email Field -->
                        <div class="mb-3">
                            <label for="email" class="form-label fw-semibold text-dark fs-sm mb-1.5">
                                Email Address
                            </label>
                            <div class="input-group-modern">
                                <i class="ti ti-mail input-icon"></i>
                                <input type="email"
                                    class="form-control form-control-modern @error('email') is-invalid @enderror"
                                    name="email" id="email" value="{{ old('email') }}"
                                    placeholder="admin@kidseedental.com" required autofocus autocomplete="username" />
                            </div>
                            @error('email')
                                <div class="text-danger fs-xs mt-1.5 fw-medium d-flex align-items-center">
                                    <i class="ti ti-circle-x me-1"></i> {{ $message }}
                                </div>
                            @enderror
                        </div>

                        <!-- Password Field -->
                        <div class="mb-3">
                            <label for="password" class="form-label fw-semibold text-dark fs-sm mb-1.5">
                                Password
                            </label>
                            <div class="input-group-modern position-relative">
                                <i class="ti ti-lock input-icon"></i>
                                <input type="password"
                                    class="form-control form-control-modern pe-5 @error('password') is-invalid @enderror"
                                    name="password" id="password" placeholder="Enter your password" required
                                    autocomplete="current-password" />
                                <button type="button" class="password-toggle-btn" id="togglePasswordBtn" tabindex="-1"
                                    aria-label="Toggle password visibility" title="Show / Hide Password">
                                    <i class="ti ti-eye fs-5" id="togglePasswordIcon"></i>
                                </button>
                            </div>
                            @error('password')
                                <div class="text-danger fs-xs mt-1.5 fw-medium d-flex align-items-center">
                                    <i class="ti ti-circle-x me-1"></i> {{ $message }}
                                </div>
                            @enderror
                        </div>

                        <!-- Remember Me & Forgot Password -->
                        {{-- <div class="d-flex justify-content-between align-items-center mb-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="remember"
                                    id="remember" {{ old('remember') ? 'checked' : '' }}>
                                <label class="form-check-label fs-sm text-dark user-select-none" for="remember">
                                    Remember me
                                </label>
                            </div>
                            @if (Route::has('password.request'))
                                <a href="{{ route('password.request') }}" class="brand-link fs-sm">
                                    Forgot Password?
                                </a>
                            @endif
                        </div> --}}

                        <!-- Submit Button with #ffcc00 -->
                        <div class="d-grid mb-3">
                            <button type="submit" class="btn btn-brand-yellow w-100" id="submitBtn">
                                <span class="btn-text me-2 fw-bold">Sign In to Dashboard</span>
                                <i class="ti ti-arrow-right fs-5 align-middle btn-icon"></i>
                            </button>
                        </div>
                    </form>

                    <!-- Footer Security Note -->
                    {{-- <div class="text-center mt-4 pt-3 border-top border-light-subtle">
                        <p class="text-muted fs-xs mb-0">
                            <i class="ti ti-shield-lock me-1" style="color: #d4a300;"></i> Authorized
                            Administrative Access &bull; Kids-e-Dental
                        </p>
                    </div> --}}

                </div>
            </div>

            <!-- Bottom Page Footer -->
            <div class="text-center mt-3">
                <p class="fs-xs mb-0" style="color: rgba(255, 255, 255, 0.75);">
                    <span class="fw-semibold text-white">&copy; {{ date('Y') }} Kids-e-Dental&reg;</span>
                    <span>&bull; All Rights Reserved</span>
                </p>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Password Show / Hide Toggle
            const toggleBtn = document.getElementById('togglePasswordBtn');
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.getElementById('togglePasswordIcon');

            if (toggleBtn && passwordInput && toggleIcon) {
                toggleBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    const isPassword = passwordInput.getAttribute('type') === 'password';
                    passwordInput.setAttribute('type', isPassword ? 'text' : 'password');
                    toggleIcon.classList.toggle('ti-eye', !isPassword);
                    toggleIcon.classList.toggle('ti-eye-off', isPassword);
                    passwordInput.focus();
                });
            }

            // Clean Form Submit Loading State
            const loginForm = document.getElementById('loginForm');
            const submitBtn = document.getElementById('submitBtn');

            if (loginForm && submitBtn) {
                loginForm.addEventListener('submit', function() {
                    submitBtn.classList.add('disabled');
                    submitBtn.setAttribute('disabled', 'disabled');
                    const btnText = submitBtn.querySelector('.btn-text');
                    const btnIcon = submitBtn.querySelector('.btn-icon');
                    if (btnText) btnText.textContent = 'Verifying credentials...';
                    if (btnIcon) {
                        btnIcon.className = 'spinner-border spinner-border-sm align-middle ms-1';
                    }
                });
            }
        });
    </script>
@endpush
