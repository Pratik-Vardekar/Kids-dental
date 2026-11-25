@extends('layouts.admin')

@section('content')
<div class="container-fluid my-2">

    <!-- Page Header (Matches Image 1) -->
    <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between mb-4 gap-3">
        <div>
            <h4 class="mb-1 text-dark fw-bold">User Cart Details</h4>
            <p class="text-muted mb-0">View users with cart, wishlist and buy now items.</p>
        </div>
        <div>
            <a href="{{ route('customer.activities.export', request()->all()) }}" class="btn text-white d-inline-flex align-items-center gap-1 px-3 py-2 fw-semibold" style="background-color: #0d9488; border-color: #0d9488; border-radius: 6px;">
                <i class="ti ti-file-export fs-5"></i> CSV
            </a>
        </div>
    </div>

    <!-- Filter Card (Matches Image 1) -->
    <div class="card mb-4 border-0 shadow-sm" style="border-radius: 10px;">
        <div class="card-body p-4">
            <form action="{{ route('customer.activities.index') }}" method="GET" class="row g-3 align-items-end">
                <!-- Search User -->
                <div class="col-12 col-md-4">
                    <label class="form-label text-muted fs-xs fw-semibold mb-1">Search User</label>
                    <input type="text" name="search" class="form-control py-2" placeholder="Search by name, email or phone" value="{{ request('search') }}" style="border-radius: 6px;">
                </div>

                <!-- From Date -->
                <div class="col-12 col-md-3">
                    <label class="form-label text-muted fs-xs fw-semibold mb-1">From date</label>
                    <input type="date" name="from_date" class="form-control py-2" value="{{ request('from_date') }}" style="border-radius: 6px;">
                </div>

                <!-- To Date -->
                <div class="col-12 col-md-3">
                    <label class="form-label text-muted fs-xs fw-semibold mb-1">To date</label>
                    <input type="date" name="to_date" class="form-control py-2" value="{{ request('to_date') }}" style="border-radius: 6px;">
                </div>

                <!-- Filter Button -->
                <div class="col-12 col-md-2 d-flex gap-2">
                    <button type="submit" class="btn text-white w-100 py-2 d-flex align-items-center justify-content-center gap-1 fw-semibold" style="background-color: #16a34a; border-color: #16a34a; border-radius: 6px;">
                        <i class="ti ti-search fs-5"></i> Filter
                    </button>
                    @if(request()->hasAny(['search', 'from_date', 'to_date']))
                        <a href="{{ route('customer.activities.index') }}" class="btn btn-light border py-2 px-3" title="Clear Filters" style="border-radius: 6px;">
                            <i class="ti ti-refresh"></i>
                        </a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <!-- Users Table Card (Matches Image 1) -->
    <div class="card border-0 shadow-sm" style="border-radius: 10px;">
        <div class="card-header bg-white border-bottom py-3 px-4">
            <h5 class="card-title mb-0 fw-semibold text-dark">Users</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-centered mb-0 align-middle">
                    <thead class="bg-light bg-opacity-50">
                        <tr class="text-muted fs-xs">
                            <th class="ps-4 py-3" style="width: 60px;">#</th>
                            <th class="py-3" style="min-width: 220px;">User</th>
                            <th class="py-3" style="min-width: 240px;">Email</th>
                            <th class="py-3" style="min-width: 140px;">Phone</th>
                            <th class="py-3" style="min-width: 170px;">Last Activity</th>
                            <th class="text-center py-3" style="width: 90px;">Cart</th>
                            <th class="text-center py-3" style="width: 90px;">Wishlist</th>
                            <th class="text-center py-3" style="width: 100px;">Buy Now</th>
                            <th class="text-center pe-4 py-3" style="width: 90px;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($users as $index => $user)
                            <tr>
                                <!-- Index -->
                                <td class="ps-4 align-middle text-muted fs-sm">
                                    {{ $users->firstItem() + $index }}
                                </td>

                                <!-- User Name + ID -->
                                <td class="align-middle">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="avatar-sm rounded-circle d-flex align-items-center justify-content-center"
                                             style="width: 40px; height: 40px; min-width: 40px; background-color: #e0f2fe; color: #0284c7;">
                                            <i class="ti ti-user fs-4"></i>
                                        </div>
                                        <div>
                                            <a href="{{ route('customer.activities.show', $user->id) }}" class="text-dark fw-semibold text-decoration-none fs-sm d-block">
                                                {{ $user->name }}
                                            </a>
                                            <span class="text-muted fs-xxs">ID: {{ $user->id }}</span>
                                        </div>
                                    </div>
                                </td>

                                <!-- Email -->
                                <td class="align-middle text-muted fs-sm">
                                    <a href="mailto:{{ $user->email }}" class="text-muted text-decoration-none">
                                        {{ $user->email }}
                                    </a>
                                </td>

                                <!-- Phone -->
                                <td class="align-middle text-muted fs-sm">
                                    {{ $user->phone ?: '-' }}
                                </td>

                                <!-- Last Activity -->
                                <td class="align-middle">
                                    @if($user->last_activity)
                                        @php
                                            $lastAct = \Illuminate\Support\Carbon::parse($user->last_activity)->setTimezone('Asia/Kolkata');
                                        @endphp
                                        <div class="text-dark fs-sm fw-medium">
                                            {{ $lastAct->format('d M Y') }}
                                        </div>
                                        <small class="text-muted fs-xxs d-block">
                                            {{ $lastAct->format('h:i A') }} IST
                                        </small>
                                    @else
                                        <span class="text-muted fs-sm">-</span>
                                    @endif
                                </td>

                                <!-- Cart Count -->
                                <td class="align-middle text-center">
                                    @if($user->cart_count > 0)
                                        <span class="badge text-white px-2 py-1 fs-xs fw-semibold" style="background-color: #059669; border-radius: 4px;">
                                            {{ $user->cart_count }}
                                        </span>
                                    @else
                                        <span class="text-muted fs-sm">0</span>
                                    @endif
                                </td>

                                <!-- Wishlist Count -->
                                <td class="align-middle text-center">
                                    @if($user->wishlist_count > 0)
                                        <span class="badge text-white px-2 py-1 fs-xs fw-semibold" style="background-color: #059669; border-radius: 4px;">
                                            {{ $user->wishlist_count }}
                                        </span>
                                    @else
                                        <span class="text-muted fs-sm">0</span>
                                    @endif
                                </td>

                                <!-- Buy Now Count (Matches Green badge in Image 1) -->
                                <td class="align-middle text-center">
                                    @if($user->buy_now_count > 0)
                                        <span class="badge text-white px-2 py-1 fs-xs fw-semibold" style="background-color: #059669; border-radius: 4px;">
                                            {{ $user->buy_now_count }}
                                        </span>
                                    @else
                                        <span class="text-muted fs-sm">0</span>
                                    @endif
                                </td>

                                <!-- Action Button (Matches Cyan Eye Button in Image 1) -->
                                <td class="pe-4 align-middle text-center">
                                    <a href="{{ route('customer.activities.show', $user->id) }}"
                                       class="btn btn-sm text-white rounded d-inline-flex align-items-center justify-content-center p-2"
                                       style="background-color: #38bdf8; border-color: #38bdf8; width: 34px; height: 32px;"
                                       title="View User Cart Details">
                                        <i class="ti ti-eye fs-5"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-5">
                                    <div class="d-flex flex-column align-items-center py-4">
                                        <div class="avatar-lg rounded-circle bg-light d-flex align-items-center justify-content-center text-muted mb-3" style="width: 60px; height: 60px;">
                                            <i class="ti ti-shopping-cart-off fs-2"></i>
                                        </div>
                                        <h5 class="text-dark fw-semibold mb-1">No users found</h5>
                                        <p class="text-muted fs-xs mb-3">No users with cart, wishlist, or buy now items matching your search.</p>
                                        @if(request()->hasAny(['search', 'from_date', 'to_date']))
                                            <a href="{{ route('customer.activities.index') }}" class="btn btn-sm btn-outline-primary">
                                                <i class="ti ti-refresh me-1"></i> Reset Filters
                                            </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @if($users->hasPages())
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-center p-3 border-top gap-2">
                    <div class="text-muted small">
                        Showing <strong>{{ $users->firstItem() }}</strong> to <strong>{{ $users->lastItem() }}</strong> of <strong>{{ number_format($users->total()) }}</strong> users
                    </div>
                    <div>
                        {{ $users->onEachSide(1)->links('pagination::bootstrap-5') }}
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
