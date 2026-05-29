@extends('layouts.auth')

@section('title', 'Login')

@section('content')
<style>
    body {
    min-height: 100vh;
    background: linear-gradient(
        to right,
        #b2d76d 0%,
        #91cd7b 35%,
        #88e49a 65%,
        #f1cf8e 100%
    );

    background-attachment: fixed;
}
</style>


<div class="auth-box d-flex align-items-center min-vh-100">

    <div class="container-xxl">

        <div class="row align-items-center justify-content-center">

            <div class="col-xl-10">

                <div class="card border-0 shadow-lg overflow-hidden">

                    <div class="row justify-content-between g-0">

                        <!-- Left Side -->
                        <div class="col-lg-6">

                            <div class="card-body p-4 p-xl-5">

                                <div class="auth-brand text-center mb-4 position-relative">

                                    <a href="{{ url('/') }}" class="logo-dark">

                                        <img src="{{ asset('backend/assets/images/logo.png') }}"
                                            alt="dark logo"
                                            style="max-height:70px;" />

                                    </a>

                                    <h4 class="fw-bold text-dark mt-4">
                                        Great to see you here 👋
                                    </h4>

                                    <p class="text-muted w-lg-75 mx-auto">
                                        Let’s get you signed in.
                                        Enter your email and password to continue.
                                    </p>

                                </div>

                                <!-- Validation Errors -->
                                @if ($errors->any())

                                    <div class="alert alert-danger">

                                        {{ $errors->first() }}

                                    </div>

                                @endif

                                <!-- Session Status -->
                                @if (session('status'))

                                    <div class="alert alert-success">

                                        {{ session('status') }}

                                    </div>

                                @endif

                                <form method="POST"
                                    action="{{ route('login') }}">

                                    @csrf

                                    <!-- Email -->
                                    <div class="mb-3">

                                        <label for="email"
                                            class="form-label">

                                            Email Address
                                            <span class="text-danger">*</span>

                                        </label>

                                        <div class="app-search">

                                            <input
                                                type="email"
                                                class="form-control @error('email') is-invalid @enderror"
                                                id="email"
                                                name="email"
                                                placeholder="you@example.com"
                                                value="{{ old('email') }}"
                                                required
                                                autofocus>

                                            <i class="ti ti-mail app-search-icon text-muted"></i>

                                        </div>

                                        @error('email')

                                            <div class="invalid-feedback d-block">

                                                {{ $message }}

                                            </div>

                                        @enderror

                                    </div>

                                    <!-- Password -->
                                  <!-- Password -->
<div class="mb-3">

    <label for="password" class="form-label">
        Password
        <span class="text-danger">*</span>
    </label>

    <div class="app-search position-relative">

        <input
            type="password"
            class="form-control @error('password') is-invalid @enderror pe-5"
            id="password"
            name="password"
            placeholder="••••••••"
            required>

        <!-- Lock Icon -->
        <i class="ti ti-lock-password app-search-icon text-muted"></i>

        <!-- Show/Hide Icon -->
        <span
            id="togglePassword"
            style="
                position:absolute;
                right:15px;
                top:50%;
                transform:translateY(-50%);
                cursor:pointer;
                z-index:5;
            ">

            <i class="ti ti-eye text-muted" id="eyeIcon"></i>

        </span>

    </div>

    @error('password')

        <div class="invalid-feedback d-block">
            {{ $message }}
        </div>

    @enderror

</div>

<!-- Script -->
<script>
    const togglePassword = document.getElementById('togglePassword');
    const password = document.getElementById('password');
    const eyeIcon = document.getElementById('eyeIcon');

    togglePassword.addEventListener('click', function () {

        const type = password.getAttribute('type') === 'password'
            ? 'text'
            : 'password';

        password.setAttribute('type', type);

        // Toggle icon
        eyeIcon.classList.toggle('ti-eye');
        eyeIcon.classList.toggle('ti-eye-off');

    });
</script>
                                    <!-- Remember -->
                                   

                                    <!-- Submit -->
                                    <div class="d-grid">

                                        <button type="submit"
                                            class="btn btn-primary fw-semibold py-2">

                                            Sign In

                                        </button>

                                    </div>

                                </form>

                            </div>

                        </div>

                        <!-- Right Side Image -->
                        <div class="col-lg-6 d-none d-lg-block">

                            <div class="h-100 position-relative card-side-img rounded-end overflow-hidden"
                                style="background-image: url('{{ asset('backend/assets/images/img.jpg') }}');
                                       background-size: cover;
                                       background-position: center;">

                                <div class="p-4 card-img-overlay rounded-end auth-overlay d-flex align-items-end justify-content-center">
                                </div>

                            </div>

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>

</div>

@endsection