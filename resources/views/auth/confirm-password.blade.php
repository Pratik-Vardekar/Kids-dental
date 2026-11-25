@extends('layouts.auth')

@section('title', 'Confirm Password')

@section('content')
    <div class="row justify-content-center">
        <div class="col-11 col-sm-9 col-md-7 col-lg-5 col-xl-4">
            <div class="card auth-card p-3 p-sm-4 border-0">
                <div class="card-body">

                    <div class="text-center mb-4">
                        <a href="{{ url('/') }}" class="d-inline-block text-decoration-none">
                            <img src="{{ asset('backend/assets/images/KidseDental_Logo.png') }}" alt="Kids-e-Dental"
                                class="auth-logo-img mb-3" />
                        </a>
                        <h3 class="auth-form-title mb-1">Confirm Password</h3>
                        <p class="text-muted fs-sm mb-0">Please confirm your password before continuing</p>
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

                    <form method="POST" action="{{ route('password.confirm') }}" class="mt-3">
                        @csrf

                        <div class="mb-3">
                            <label for="password" class="form-label fw-semibold text-dark fs-sm mb-1.5">
                                Password
                            </label>
                            <div class="input-group-modern position-relative">
                                <i class="ti ti-lock input-icon"></i>
                                <input type="password"
                                    class="form-control form-control-modern pe-5 @error('password') is-invalid @enderror"
                                    name="password" id="password" placeholder="Enter password" required
                                    autocomplete="current-password" />
                                <button type="button" class="password-toggle-btn" id="togglePasswordBtn" tabindex="-1">
                                    <i class="ti ti-eye fs-5" id="togglePasswordIcon"></i>
                                </button>
                            </div>
                            @error('password')
                                <div class="text-danger fs-xs mt-1.5 fw-medium">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="d-grid mb-3">
                            <button type="submit" class="btn btn-brand-yellow w-100">
                                <i class="ti ti-check me-1 fs-5 align-middle"></i> Confirm Password
                            </button>
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
