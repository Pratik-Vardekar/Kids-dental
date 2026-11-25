@extends('layouts.front')
@section('content')

<main class="pageWrapper loginMainWrap">
    <div class="cloudWrap position-relative d-none d-lg-block">
        <div class="container clouds position-relative"></div>
    </div>
    <section class="py-5 mb-5 overflow-hidden">
        <div class="container py-5">
            <div class="row justify-content-center"> <!-- Center the row horizontally -->
                <div class="col-12 col-md-8 col-lg-6 d-flex justify-content-center">
                    <div class="rounded-4 animateThis slideTop loginWrap shadow-lg p-3 p-md-5"
                        style="max-width: 500px; width: 100%; margin: 0 auto;"> <!-- Remove ms-0 ms-md-4 -->
                        <h1 class="sectTitle mb-3 lh-1 fw-bold animateThis slideTop text-md-center text-center">Sign Up</h1>
                        @if ($errors->any())
                            <div class="alert alert-danger py-2 mb-3" role="alert">
                                {{ $errors->first() }}
                            </div>
                        @endif

                        <form method="POST" action="{{ url('/register') }}" class="row g-3 formFormat animateThis fadeGrow">
                            @csrf
                            <div class="col-12">
                                <input
                                    type="text"
                                    class="form-control @error('name') is-invalid @enderror"
                                    name="name"
                                    id="name"
                                    placeholder="Full Name*"
                                    value="{{ old('name') }}"
                                    required
                                    style="height: 48px;"
                                />
                                @error('name')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-12">
                                <input
                                    type="email"
                                    class="form-control @error('email') is-invalid @enderror"
                                    name="email"
                                    id="email"
                                    placeholder="Email Id*"
                                    value="{{ old('email') }}"
                                    required
                                    style="height: 48px;"
                                />
                                @error('email')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-12">
                                <input
                                    type="tel"
                                    class="form-control @error('phone') is-invalid @enderror"
                                    name="phone"
                                    id="country"
                                    placeholder="Mobile No."
                                    value="{{ old('phone') ? ltrim(preg_replace('/\D+/', '', preg_replace('/^(\+?91)/', '', old('phone'))), '0') : '' }}"
                                    inputmode="numeric"
                                    maxlength="10"
                                    style="height: 48px;"
                                />
                                @error('phone')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-12">
                                <div class="passwordField position-relative">
                                    <input
                                        type="password"
                                        class="form-control @error('password') border-danger @enderror"
                                        name="password"
                                        id="password"
                                        placeholder="Password*"
                                        required
                                        autocomplete="new-password"
                                    />
                                    <button type="button" class="passwordBtn" data-bs-toggle="button" tabindex="-1" aria-label="Show/Hide password"></button>
                                </div>
                                @error('password')
                                    <div class="text-danger mt-1" style="font-size:.875rem;">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-12">
                                <div class="passwordField position-relative">
                                    <input
                                        type="password"
                                        class="form-control @error('password_confirmation') border-danger @enderror"
                                        name="password_confirmation"
                                        id="password_confirmation"
                                        placeholder="Confirm Password*"
                                        required
                                        autocomplete="new-password"
                                    />
                                    <button type="button" class="passwordBtn" data-bs-toggle="button" tabindex="-1" aria-label="Show/Hide password"></button>
                                </div>
                            </div>
                            <div class="col-12" style="font-size: .95rem;">
                                By continuing, you agree to our <a href="#" class="fw-medium">Terms of Service</a> and <a href="#" class="fw-medium">Privacy Policy</a>.
                            </div>
                            <div class="col-12 text-center">
                                <button type="submit" class="ctaBtn" style="max-width: 300px;">
                                    <span class="btnTxt" style="text-align: center;">Continue</span>
                                    <span class="arw"></span>
                                </button>
                            </div>
                       
                            <div class="col-12 mt-4 text-center">
                                Already have an account?
                                <a href="{{ route('login') }}" class="fw-medium">Back to Sign In</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div> <!-- end row -->
        </div>
    </section>
</main>

<style>
.formFormat .iti {
    display: block;
    width: 100%;
}
.formFormat .iti > input,
.formFormat .iti input[type="tel"] {
    width: 100%;
    height: 48px;
}
.formFormat .iti__country-list {
    z-index: 20;
}
.passwordField .form-control.is-invalid,
.passwordField .form-control:invalid {
    background-image: none !important;
}

@media (max-width: 575.98px) {
    .loginWrap {
        padding: 1rem !important;
        border-radius: 1rem !important;
        margin: 0 auto !important;
    }
    .ctaBtn {
        width: 100% !important;
        min-width: unset !important;
        max-width: unset !important;
    }
    .sectTitle {
        font-size: 2rem !important;
    }
    .formFormat input.form-control {
        font-size: .98rem;
    }
}
</style>

@endsection