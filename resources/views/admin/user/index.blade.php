@extends('layouts.admin')

@section('content')

<div class="container-fluid my-2">

    <div class="mb-4">

        <h4 class="mb-1">Users</h4>
        <p class="text-muted mb-0">
            Manage all users
        </p>

    </div>

    <div class="card">

        <div class="card-body p-0">
<div class="card mb-3">

    <div class="card-body">

        <form method="GET"
            action="{{ route('user.index') }}"
            class="row g-2">

            <div class="col-md-3">

                <input type="text"
                    name="search"
                    class="form-control"
                    placeholder="Search name, email, phone"
                    value="{{ request('search') }}">

            </div>

            <div class="col-md-2">

                <select name="role"
                    class="form-select">

                    <option value="">All Roles</option>

                    <option value="admin"
                        {{ request('role') == 'admin' ? 'selected' : '' }}>
                        Admin
                    </option>

                    <option value="vendor"
                        {{ request('role') == 'vendor' ? 'selected' : '' }}>
                        Vendor
                    </option>

                    <option value="user"
                        {{ request('role') == 'user' ? 'selected' : '' }}>
                        User
                    </option>

                </select>

            </div>

            <div class="col-md-2">

                <select name="status"
                    class="form-select">

                    <option value="">All Status</option>

                    <option value="1"
                        {{ request('status') == '1' ? 'selected' : '' }}>
                        Active
                    </option>

                    <option value="0"
                        {{ request('status') == '0' ? 'selected' : '' }}>
                        Inactive
                    </option>

                </select>

            </div>

            <div class="col-md-2">

                <select name="sort"
                    class="form-select">

                    <option value="latest"
                        {{ request('sort') == 'latest' ? 'selected' : '' }}>
                        Latest
                    </option>

                    <option value="oldest"
                        {{ request('sort') == 'oldest' ? 'selected' : '' }}>
                        Oldest
                    </option>

                    <option value="name_asc"
                        {{ request('sort') == 'name_asc' ? 'selected' : '' }}>
                        Name A-Z
                    </option>

                    <option value="name_desc"
                        {{ request('sort') == 'name_desc' ? 'selected' : '' }}>
                        Name Z-A
                    </option>

                </select>

            </div>

            <div class="col-md-3 d-flex gap-2">

                <button type="submit"
                    class="btn btn-primary">

                    <i class="ti ti-search me-1"></i>
                    Filter

                </button>

                <a href="{{ route('user.index') }}"
                    class="btn btn-light">

                    Reset

                </a>

            </div>

        </form>

    </div>

</div>
            <div class="table-responsive">

                <table class="table table-hover align-middle mb-0">

                    <thead class="table-light">

                        <tr>

                            <th>#</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Role</th>
                            <th>Addresses</th>
                            <th>Status</th>
                            <th>Created</th>

                        </tr>

                    </thead>

                    <tbody>

                        @forelse($users as $user)

                        <tr>

                            <td>
                                {{ $loop->iteration }}
                            </td>

                            <td>
                                {{ $user->name }}
                            </td>

                            <td>
                                {{ $user->email }}
                            </td>

                            <td>
                                {{ $user->phone ?? '-' }}
                            </td>
<td>

    @if($user->role == 'admin')

        <span class="badge bg-danger">
            Admin
        </span>

    @elseif($user->role == 'vendor')

        <span class="badge bg-warning">
            Vendor
        </span>

    @else

        <span class="badge bg-primary">
            User
        </span>

    @endif

</td>
                         
                                <td>

    <div class="d-flex align-items-center gap-2">

        <span class="badge bg-info">

            {{ $user->addresses_count }}

        </span>

        <a href="{{ route('address.index', ['user_id' => $user->id]) }}"
            class="btn btn-sm btn-light">

            <i class="ti ti-eye"></i>

        </a>

    </div>

</td>
   <td>
                                @if($user->status == 1)

                                    <span class="badge badge-soft-success">
                                        Active
                                    </span>

                                @else

                                    <span class="badge badge-soft-danger">
                                        Inactive
                                    </span>

                                @endif

                            </td>

                            <td>
                                {{ $user->created_at->format('d M Y') }}
                            </td>

                        </tr>

                        @empty

                        <tr>

                            <td colspan="6"
                                class="text-center py-4">

                                No Users Found

                            </td>

                        </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>

        </div>

    </div>

</div>

@endsection