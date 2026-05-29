@extends('layouts.admin')

@section('content')

<div class="container-fluid my-3">

    {{-- Page Title --}}
    <div class="mb-4">

        <h4 class="mb-1">
            My Profile
        </h4>

        <p class="text-muted mb-0">
            Manage your account information and password
        </p>

    </div>

    <div class="row">

        {{-- Profile Information --}}
        <div class="col-lg-7">

            <div class="card">

                <div class="card-header">

                    <h5 class="card-title mb-0">
                        Profile Information
                    </h5>

                </div>

                <div class="card-body">

                    {{-- Success Message --}}
                    @if(session('success'))

                        <div class="alert alert-success">

                            {{ session('success') }}

                        </div>

                    @endif

                    {{-- Error Message --}}
                    @if(session('error'))

                        <div class="alert alert-danger">

                            {{ session('error') }}

                        </div>

                    @endif

                    <form action="{{ route('admin.profile.update') }}"
                          method="POST">

                        @csrf

                        <div class="row">

                            {{-- Name --}}
                            <div class="col-md-6 mb-3">

                                <label class="form-label">
                                    Full Name
                                </label>

                                <input type="text"
                                       name="name"
                                       class="form-control"
                                       value="{{ auth()->user()->name }}">

                                @error('name')

                                    <small class="text-danger">
                                        {{ $message }}
                                    </small>

                                @enderror

                            </div>

                            {{-- Email --}}
                            <div class="col-md-6 mb-3">

                                <label class="form-label">
                                    Email Address
                                </label>

                                <input readonly type="email"
                                       name="email"
                                       class="form-control"
                                       value="{{ auth()->user()->email }}">

                                @error('email')

                                    <small class="text-danger">
                                        {{ $message }}
                                    </small>

                                @enderror

                            </div>

                            {{-- Phone --}}
                            <div class="col-md-6 mb-3">

                                <label class="form-label">
                                    Phone Number
                                </label>

                                <input type="text"
                                       name="phone"
                                       class="form-control"
                                       value="{{ auth()->user()->phone }}">

                                @error('phone')

                                    <small class="text-danger">
                                        {{ $message }}
                                    </small>

                                @enderror

                            </div>

                            {{-- Role --}}
                            <div class="col-md-6 mb-3">

                                <label class="form-label">
                                    Role
                                </label>

                                <input type="text"
                                       class="form-control"
                                       value="{{ auth()->user()->role }}"
                                       readonly>

                            </div>

                        </div>

                        <button type="submit"
                                class="btn btn-primary">

                            <i class="ti ti-device-floppy me-1"></i>

                            Update Profile

                        </button>

                    </form>

                </div>

            </div>

        </div>

        {{-- Change Password --}}
        <div class="col-lg-5">

            <div class="card">

                <div class="card-header">

                    <h5 class="card-title mb-0">
                        Change Password
                    </h5>

                </div>

                <div class="card-body">

                    <form action="{{ route('admin.profile.password') }}"
                          method="POST">

                        @csrf

                        {{-- Current Password --}}
                        <div class="mb-3">

                            <label class="form-label">
                                Current Password
                            </label>

                            <input type="password"
                                   name="current_password"
                                   class="form-control">

                            @error('current_password')

                                <small class="text-danger">
                                    {{ $message }}
                                </small>

                            @enderror

                        </div>

                        {{-- New Password --}}
                        <div class="mb-3">

                            <label class="form-label">
                                New Password
                            </label>

                            <input type="password"
                                   name="password"
                                   class="form-control">

                            @error('password')

                                <small class="text-danger">
                                    {{ $message }}
                                </small>

                            @enderror

                        </div>

                        {{-- Confirm Password --}}
                        <div class="mb-3">

                            <label class="form-label">
                                Confirm Password
                            </label>

                            <input type="password"
                                   name="password_confirmation"
                                   class="form-control">

                        </div>

                        <button type="submit"
                                class="btn btn-dark">

                            <i class="ti ti-lock me-1"></i>

                            Update Password

                        </button>

                    </form>

                </div>

            </div>

        </div>

    </div>

</div>

@endsection