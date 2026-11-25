@extends('layouts.admin')
@section('content')

<div class="container-fluid my-2">

    <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
        <div>
            <h4 class="mb-1">Contact Enquiries</h4>
            <p class="text-muted mb-0">Manage all contact enquiries</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.contact-enquiries.export', request()->all()) }}" class="btn btn-info text-white">
                <i class="ti ti-file-export me-1"></i>
                Export CSV
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card">
        <div class="card-body">
            <!-- FILTERS -->
            <form method="GET" action="{{ route('admin.contact-enquiries.index') }}" class="mb-4">
                <div class="row g-3 align-items-end">
                    <!-- Search -->
                    <div class="col-auto">
                        <label class="form-label small text-muted mb-1">Search</label>
                        <input type="text"
                            name="search"
                            class="form-control"
                            placeholder="Name, email, phone..."
                            value="{{ request('search') }}"
                            style="width: 250px;">
                    </div>

                    <!-- Status Filter -->
                    <div class="col-auto">
                        <label class="form-label small text-muted mb-1">Status</label>
                        <select name="status" class="form-select" style="width: 150px;">
                            <option value="">All Status</option>
                            <option value="1" {{ request('status') === '1' ? 'selected' : '' }}>Read</option>
                            <option value="0" {{ request('status') === '0' ? 'selected' : '' }}>Unread</option>
                        </select>
                    </div>

                    <!-- Date Filter -->
                    <div class="col-auto">
                        <label class="form-label small text-muted mb-1">Date</label>
                        <input type="date"
                            name="date"
                            class="form-control"
                            value="{{ request('date') }}"
                            style="width: 180px;">
                    </div>

                    <!-- Buttons -->
                    <div class="col-auto">
                        <div class="d-flex gap-2 flex-wrap">
                            <button type="submit" class="btn btn-primary">
                                <i class="ti ti-filter me-1"></i>
                                Filter
                            </button>

                            <a href="{{ route('admin.contact-enquiries.index') }}" class="btn btn-secondary">
                                <i class="ti ti-refresh me-1"></i>
                                Reset
                            </a>

                            <a href="{{ route('admin.contact-enquiries.export', request()->all()) }}" class="btn btn-info text-white">
                                <i class="ti ti-file-export me-1"></i>
                                CSV
                            </a>
                        </div>
                    </div>
                </div>
            </form>

            <!-- Active Filters Display -->
            @if(request('search') || request('status') !== null && request('status') !== '' || request('date'))
                <div class="mb-3 d-flex flex-wrap gap-2">
                    <span class="text-muted small">Active filters:</span>
                    @if(request('search'))
                        <span class="badge bg-secondary">
                            Search: {{ request('search') }}
                            <a href="{{ route('admin.contact-enquiries.index', array_merge(request()->except('search', 'page'))) }}" class="text-white ms-1 text-decoration-none">&times;</a>
                        </span>
                    @endif
                    @if(request('status') !== null && request('status') !== '')
                        <span class="badge bg-secondary">
                            Status: {{ request('status') === '1' ? 'Read' : 'Unread' }}
                            <a href="{{ route('admin.contact-enquiries.index', array_merge(request()->except('status', 'page'))) }}" class="text-white ms-1 text-decoration-none">&times;</a>
                        </span>
                    @endif
                    @if(request('date'))
                        <span class="badge bg-secondary">
                            Date: {{ request('date') }}
                            <a href="{{ route('admin.contact-enquiries.index', array_merge(request()->except('date', 'page'))) }}" class="text-white ms-1 text-decoration-none">&times;</a>
                        </span>
                    @endif
                </div>
            @endif

            <div class="table-responsive">
                <table class="table table-bordered w-100">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Contact No.</th>
                            <th>Comment</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($contactEnquiries as $contactEnquiry)
                        <tr>
                            <td>{{ $contactEnquiries->firstItem() ? $contactEnquiries->firstItem() + $loop->index : $loop->iteration }}</td>
                            <td>{{ $contactEnquiry->full_name }}</td>
                            <td>{{ $contactEnquiry->email }}</td>
                            <td>{{ $contactEnquiry->contact_no }}</td>
                            <td>{{ \Illuminate\Support\Str::limit($contactEnquiry->comment, 60) }}</td>
                            <td>
                                @if($contactEnquiry->status)
                                    <span class="badge bg-success">Read</span>
                                @else
                                    <span class="badge bg-warning text-dark">Unread</span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('admin.contact-enquiries.edit', $contactEnquiry->id) }}" class="btn btn-sm btn-primary">Edit</a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">
                                @if(request('search') || (request('status') !== null && request('status') !== '') || request('date'))
                                    <div>No contact enquiries match your filters.</div>
                                    <a href="{{ route('admin.contact-enquiries.index') }}" class="btn btn-sm btn-outline-primary mt-2">
                                        <i class="ti ti-refresh me-1"></i> Reset Filters
                                    </a>
                                @else
                                    <div>No contact enquiries found.</div>
                                @endif
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if(method_exists($contactEnquiries, 'hasPages') && $contactEnquiries->hasPages())
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mt-4 pt-2 border-top">
                    <div class="text-muted small mb-3 mb-md-0">
                        Showing
                        <span class="fw-semibold">{{ $contactEnquiries->firstItem() }}</span>
                        to
                        <span class="fw-semibold">{{ $contactEnquiries->lastItem() }}</span>
                        of
                        <span class="fw-semibold">{{ $contactEnquiries->total() }}</span>
                        entries
                    </div>

                    <nav aria-label="Contact enquiry pagination">
                        <ul class="pagination pagination-sm flex-wrap justify-content-center mb-0 gap-1">
                            {{-- Previous Page Link --}}
                            @if ($contactEnquiries->onFirstPage())
                                <li class="page-item disabled">
                                    <span class="page-link rounded-3"><i class="ti ti-chevron-left"></i></span>
                                </li>
                            @else
                                <li class="page-item">
                                    <a class="page-link rounded-3"
                                        href="{{ $contactEnquiries->appends(request()->query())->previousPageUrl() }}"
                                        rel="prev">
                                        <i class="ti ti-chevron-left"></i>
                                    </a>
                                </li>
                            @endif

                            {{-- Pagination Elements --}}
                            @php
                                $currentPage = $contactEnquiries->currentPage();
                                $lastPage = $contactEnquiries->lastPage();
                                $start = max(1, $currentPage - 2);
                                $end = min($lastPage, $currentPage + 2);
                            @endphp

                            {{-- First page link --}}
                            @if ($start > 1)
                                <li class="page-item d-none d-sm-block">
                                    <a class="page-link rounded-3"
                                        href="{{ $contactEnquiries->appends(request()->query())->url(1) }}">1</a>
                                </li>
                                @if ($start > 2)
                                    <li class="page-item disabled d-none d-sm-block">
                                        <span class="page-link rounded-3">...</span>
                                    </li>
                                @endif
                            @endif

                            {{-- Page numbers --}}
                            @for ($i = $start; $i <= $end; $i++)
                                @if ($i == $currentPage)
                                    <li class="page-item active" aria-current="page">
                                        <span class="page-link rounded-3">{{ $i }}</span>
                                    </li>
                                @else
                                    <li class="page-item d-none d-sm-block">
                                        <a class="page-link rounded-3"
                                            href="{{ $contactEnquiries->appends(request()->query())->url($i) }}">{{ $i }}</a>
                                    </li>
                                @endif
                            @endfor

                            {{-- Last page link --}}
                            @if ($end < $lastPage)
                                @if ($end < $lastPage - 1)
                                    <li class="page-item disabled d-none d-sm-block">
                                        <span class="page-link rounded-3">...</span>
                                    </li>
                                @endif
                                <li class="page-item d-none d-sm-block">
                                    <a class="page-link rounded-3"
                                        href="{{ $contactEnquiries->appends(request()->query())->url($lastPage) }}">{{ $lastPage }}</a>
                                </li>
                            @endif

                            {{-- Next Page Link --}}
                            @if ($contactEnquiries->hasMorePages())
                                <li class="page-item">
                                    <a class="page-link rounded-3"
                                        href="{{ $contactEnquiries->appends(request()->query())->nextPageUrl() }}"
                                        rel="next">
                                        <i class="ti ti-chevron-right"></i>
                                    </a>
                                </li>
                            @else
                                <li class="page-item disabled">
                                    <span class="page-link rounded-3"><i class="ti ti-chevron-right"></i></span>
                                </li>
                            @endif
                        </ul>
                    </nav>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
